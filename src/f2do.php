<?php

/**
 * f2do.php
 *
 * Scan through all Foursquare checkins and output them to a DayOne journal.
 *
 * @author     Neil Thompson <neil@spokenlikeageek.com>
 * @copyright  2025 Neil Thompson
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html  GNU General Public License v3.0
 * @link       https://github.com/williamsdb/foursquare-to-dayone
 * @see        https://www.spokenlikeageek.com/2025/12/01/exporting-foursquare-check-ins-to-day-one/ Blog post
 *
 * ARGUMENTS
 * - None
 *
 */

// turn off reporting of notices
error_reporting(E_ALL & ~E_NOTICE);

// ------------------------
// LOAD PARAMETERS
// ------------------------
try {
    require __DIR__ . '/config.php';
} catch (\Throwable $th) {
    die('config.php file not found. Have you renamed from config_dummy.php?');
}

// ------------------------
// LOAD PROGRESS
// ------------------------
$state = [
    'next_fetch_offset' => null,
    'last_imported_id' => null
];

if (file_exists(PROGRESS_FILE)) {
    $raw = file_get_contents(PROGRESS_FILE);
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $state = array_merge($state, $json);
    }
}

// ------------------------
// STEP 1 — INITIAL HISTORICAL IMPORT
// ------------------------
if ($state['next_fetch_offset'] === null) {
    $firstCall = fetchCheckinsWithRetries(FOURSQUARE_ACCESS_TOKEN, 0, 1);
    $totalCheckins = $firstCall['count'] ?? 0;
    if ($totalCheckins <= 0) {
        echo "No check-ins found.\n";
        exit;
    }
    $lastBlock = floor(($totalCheckins - 1) / FETCH_LIMIT) * FETCH_LIMIT;
    $state['next_fetch_offset'] = (int)$lastBlock;
    saveProgress($state);
    logMessage("Initial run: total check-ins = $totalCheckins, starting offset = {$state['next_fetch_offset']}");
}

$doneHistorical = false;
$firstRun = true;

while (!$doneHistorical) {
    $offset = $state['next_fetch_offset'];
    if ($offset > 0) {
        logMessage("Fetching historical block at offset $offset...");
    } else {
        $doneHistorical = true;
        logMessage("Historical import complete, switching to incremental import...");
        break;
    }

    $batch = fetchCheckinsWithRetries(FOURSQUARE_ACCESS_TOKEN, $offset, FETCH_LIMIT);
    $items = array_reverse($batch['items'] ?? []);
    $count = count($items);

    if ($count === 0) {
        $doneHistorical = true;
        break;
    }

    // Determine if we should process items in this block
    if ($state['last_imported_id'] === null || $firstRun === false) {
        $process = true;
    } else {
        $process = false;
    }

    foreach ($items as $checkin) {
        $id = $checkin['id'];

        // If we reached the last imported check-in in this block, stop.
        if ($process) {
            importCheckinToDayOne($checkin);
            $state['last_imported_id'] = $id;
            saveProgress($state);
        }
        if ($state['last_imported_id'] === $id && $process === false) {
            $process = true;
        }
    }

    $firstRun = false;

    // Update offset for next iteration
    $state['next_fetch_offset'] = max($offset - FETCH_LIMIT, 0);
    saveProgress($state);

    if ($state['next_fetch_offset'] === 0) {
        $doneHistorical = true;
    }
}

// ------------------------
// STEP 2 — INCREMENTAL IMPORT (NEW CHECKINS WITH PAGINATION)
// ------------------------
logMessage("Checking for new check-ins...");

$offset = 0;
while (true) {
    $batch = fetchCheckinsWithRetries(FOURSQUARE_ACCESS_TOKEN, $offset, FETCH_LIMIT);
    $items = array_reverse($batch['items'] ?? []);
    $newItems = [];

    foreach ($items as $checkin) {
        if ($checkin['id'] === $state['last_imported_id']) {
            break 2; // stop importing
        }
        $newItems[] = $checkin;
    }

    // Import new items oldest → newest
    $newItems = array_reverse($newItems);
    foreach ($newItems as $checkin) {
        importCheckinToDayOne($checkin);
        $state['last_imported_id'] = $checkin['id'];
        saveProgress($state);
    }

    // If fewer items than limit, no more pages
    if (count($items) < FETCH_LIMIT) {
        break;
    }

    $offset += FETCH_LIMIT;
}

logMessage("Import complete.");

// ------------------------
// FUNCTIONS
// ------------------------

function fetchCheckinsWithRetries($accessToken, $offset, $limit)
{
    $attempt = 0;
    $wait = 1;
    while ($attempt < MAX_RETRIES) {
        try {
            return fetchCheckins($accessToken, $offset, $limit);
        } catch (Exception $e) {
            $attempt++;
            logMessage("Fetch attempt $attempt failed: {$e->getMessage()}. Retrying in {$wait}s...");
            sleep($wait);
            $wait *= 2;
        }
    }
    throw new RuntimeException("Failed to fetch check-ins after " . MAX_RETRIES . " attempts.");
}

function fetchCheckins($accessToken, $offset, $limit = 250)
{
    $url = "https://api.foursquare.com/v2/users/self/checkins"
        . "?oauth_token=" . urlencode($accessToken)
        . "&v=20250922"
        . "&limit=$limit"
        . "&offset=$offset";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'foursquare-to-dayone/1.0',
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new RuntimeException("cURL error: " . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException("HTTP error $httpCode: $response");
    }

    $data = json_decode($response, true);
    if (!isset($data['response']['checkins'])) {
        throw new RuntimeException("Unexpected API response format");
    }
    return $data['response']['checkins'];
}

function importCheckinToDayOne($checkin)
{
    $venue = $checkin['venue']['name'] ?? 'Unknown venue';
    $formatted_date = date('Y-m-d H:i:s', $checkin['createdAt']);
    $formattedLongDate = date('F j, Y \a\t g:ia', $checkin['createdAt']);
    $lat = $checkin['venue']['location']['lat'] ?? null;
    $lon = $checkin['venue']['location']['lng'] ?? null;
    $city = $checkin['venue']['location']['city'] ?? '';
    $stateLoc = $checkin['venue']['location']['state'] ?? '';
    $country = $checkin['venue']['location']['country'] ?? '';
    $url = "https://foursquare.com/v/" . ($checkin['venue']['id'] ?? '');

    // Generate map if enabled
    if (INCLUDE_MAPS) {
        $mapFile = __DIR__ . "/maps/checkin_{$checkin['venue']['id']}.png";

        if (!file_exists($mapFile) && $lat && $lon) {
            generateStaticMap($lat, $lon, $mapFile);
        }
    }

    $content = '# ' . $venue . PHP_EOL . PHP_EOL;
    $content .= 'Checked in at [' . $venue . '](' . $url . ') on ' . $formattedLongDate . PHP_EOL . PHP_EOL;
    if (INCLUDE_MAPS) $content .= '[{attachment}]' . PHP_EOL . PHP_EOL;
    $locationParts = array_filter([$city, $stateLoc, $country], fn($p) => !empty($p));
    $content .= 'Location: ' . implode(', ', $locationParts) . PHP_EOL;

    $cmd = 'dayone --journal "' . addslashes(DAYONE_JOURNAL) . '" --date "' . $formatted_date . '"';
    if (INCLUDE_MAPS) $cmd .= ' --attachments ' . escapeshellarg($mapFile) . ' --';
    $cmd .= ' new ' . escapeshellarg($content);

    exec($cmd . ' 2>&1', $output, $return_var);
    if ($return_var != 0) {
        logMessage("Error creating DayOne entry for $venue: " . implode("\n", $output));
        throw new RuntimeException("DayOne import failed");
    }

    logMessage("Imported check-in: $venue at $formatted_date");
}

function saveProgress($state)
{
    file_put_contents(PROGRESS_FILE, json_encode($state), LOCK_EX);
}

function logMessage($msg)
{
    if (!DEBUG) return;
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $msg\n";
    file_put_contents(LOG_FILE, "[$timestamp] $msg\n", FILE_APPEND);
}

/**
 * Generate a static map PNG for a given latitude/longitude.
 *
 * @param float $lat Latitude
 * @param float $lon Longitude
 * @param string $filename File path to save the PNG
 * @param int $zoom Zoom level (default 14)
 * @param int $width Image width in pixels (default 600)
 * @param int $height Image height in pixels (default 400)
 * @return bool True on success, false on failure
 */
function generateStaticMap($lat, $lon, $filename, $zoom = 14, $width = 1200, $height = 800)
{
    $lonLatZoom = "{$lon},{$lat},{$zoom}";
    $size = "{$width}x{$height}";
    $marker = "pin-l+ff0000({$lon},{$lat})"; // red pin at the checkin location

    $url = "https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/{$marker}/{$lonLatZoom}/{$size}?access_token=" . MAPBOX_TOKEN;

    $imageData = @file_get_contents($url);
    if ($imageData === false) {
        error_log("Failed to download map for {$lat},{$lon}");
        return false;
    }

    logMessage("Generated map for $lat,$lon -> $filename");

    return file_put_contents($filename, $imageData) !== false;
}

function array_to_html($val, $var = FALSE)
{
    $do_nothing = true;
    $indent_size = 20;
    $out = '';
    $colors = array(
        "Teal",
        "YellowGreen",
        "Tomato",
        "Navy",
        "MidnightBlue",
        "FireBrick",
        "DarkGreen"
    );

    // Get string structure
    ob_start();
    print_r($val);
    $val = ob_get_contents();
    ob_end_clean();

    // Color counter
    $current = 0;

    // Split the string into character array
    $array = preg_split('//', $val, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($array as $char) {
        if ($char == "[")
            if (!$do_nothing)
                if ($var) {
                    $out .= "</div>";
                } else {
                    echo "</div>";
                }
            else $do_nothing = false;
        if ($char == "[")
            if ($var) {
                $out .= "<div>";
            } else {
                echo "<div>";
            }
        if ($char == ")") {
            if ($var) {
                $out .= "</div></div>";
            } else {
                echo "</div></div>";
            }
            $current--;
        }

        if ($var) {
            $out .= $char;
        } else {
            echo $char;
        }

        if ($char == "(") {
            if ($var) {
                $out .= "<div class='indent' style='padding-left: {$indent_size}px; color: " . ($colors[$current % count($colors)]) . ";'>";
            } else {
                echo "<div class='indent' style='padding-left: {$indent_size}px; color: " . ($colors[$current % count($colors)]) . ";'>";
            }
            $do_nothing = true;
            $current++;
        }
    }

    return $out;
}
