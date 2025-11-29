<?php

/**
 * oauth.php
 *
 * Go through the Foursquare OAuth process.
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

// Load parameters
try {
    require __DIR__ . '/config.php';
} catch (\Throwable $th) {
    die('config.php file not found. Have you renamed from config_dummy.php?');
}

// -----------------------------------------------------------
// Build login URL
// -----------------------------------------------------------

$authUrl = "https://foursquare.com/oauth2/authenticate?" . http_build_query([
    "client_id"     => CLIENT_KEY,
    "response_type" => "code",
    "redirect_uri"  => REDIRECT_URI
]);

echo "== Foursquare OAuth CLI Tool ==\n";
echo "Starting local listener on port " . LOCAL_PORT . " ...\n";

// -----------------------------------------------------------
// Start local webserver in background to capture code
// -----------------------------------------------------------

$tmpFile = sys_get_temp_dir() . "/fsq_oauth_code_" . getmypid();
file_put_contents($tmpFile, ""); // empty file acts as storage

$cmd = sprintf(
    'php -S localhost:%d -t %s %s > /dev/null 2>&1 & echo $!',
    LOCAL_PORT,
    escapeshellarg(__DIR__),
    escapeshellarg(__FILE__ . ".router.php")
);
$pid = shell_exec($cmd);
$pid = trim($pid);

// -----------------------------------------------------------
// Create router file for handling callback
// -----------------------------------------------------------

$routerCode = <<<ROUTER
<?php
// Load parameters
try {
    require __DIR__ . '/config.php';
} catch (\Throwable \$th) {
    die('config.php file not found. Have you renamed from config_dummy.php?');
}

if (strpos(\$_SERVER['REQUEST_URI'], LOCAL_PATH) === 0) {
    parse_str(parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_QUERY), \$q);
    if (!empty(\$q['code'])) {
        file_put_contents("$tmpFile", \$q['code']);
        echo "Authentication successful. You may close this window.";
    } else {
        echo "No code found in callback.";
    }
    return true;
}
return false;
ROUTER;

file_put_contents(__FILE__ . ".router.php", $routerCode);

// -----------------------------------------------------------
// Launch browser
// -----------------------------------------------------------

echo "Opening browser for authentication...\n";
exec("open " . escapeshellarg($authUrl));

// -----------------------------------------------------------
// Poll for the code from callback
// -----------------------------------------------------------

echo "Waiting for OAuth callback...\n";

$code = null;
for ($i = 0; $i < 120; $i++) {   // wait up to 120 seconds
    $code = trim(file_get_contents($tmpFile));
    if ($code) break;
    sleep(1);
}

if (!$code) {
    echo "Timed out waiting for OAuth callback.\n";
    posix_kill((int)$pid, 9);
    unlink(__FILE__ . ".router.php");
    unlink($tmpFile);
    exit(1);
}

echo "Received code: $code\n";

// -----------------------------------------------------------
// Exchange code for access token
// -----------------------------------------------------------

$tokenUrl = "https://foursquare.com/oauth2/access_token?" . http_build_query([
    "client_id"     => CLIENT_KEY,
    "client_secret" => CLIENT_SECRET,
    "grant_type"    => "authorization_code",
    "redirect_uri"  => REDIRECT_URI,
    "code"          => $code
]);

$resp = json_decode(file_get_contents($tokenUrl), true);

posix_kill((int)$pid, 9);
unlink(__FILE__ . ".router.php");
unlink($tmpFile);

if (!isset($resp['access_token'])) {
    echo "Failed to obtain access token:\n";
    print_r($resp);
    exit(1);
}

echo "\n== SUCCESS! ==\n";
echo "Access Token: " . $resp['access_token'] . "\n";
echo "Store this token securely.\n";
