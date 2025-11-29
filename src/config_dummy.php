<?php

// ------------------------
// CONFIG for oauth.php
// ------------------------
define("CLIENT_KEY", "<your client key here>");
define("CLIENT_SECRET", "<your client secret here>");
define("REDIRECT_URI", "<your redirect URI here>");   // e.g. http://localhost:8765/callback
define("LOCAL_PORT", parse_url(REDIRECT_URI, PHP_URL_PORT) ?: 8765);
define("LOCAL_PATH", parse_url(REDIRECT_URI, PHP_URL_PATH) ?: '/callback');

// ------------------------
// CONFIG for f2do.php
// ------------------------
define("FOURSQUARE_ACCESS_TOKEN", "<your Foursquare access token here>");
define("MAPBOX_TOKEN", "<your Mapbox token here>");
define("DAYONE_JOURNAL", "<your DayOne journal name here>");
define('PROGRESS_FILE', __DIR__ . '/progress.json');
define('LOG_FILE', __DIR__ . '/foursquare_import.log');
define('FETCH_LIMIT', 250);
define('MAX_RETRIES', 5);
define('INCLUDE_MAPS', TRUE);
define('DEBUG', TRUE);
