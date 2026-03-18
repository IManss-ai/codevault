<?php
/**
 * Environment Variable Loader
 * 
 * Reads the .env file line by line and loads each KEY=VALUE
 * pair into PHP's $_ENV superglobal. Ignores comments and blank lines.
 */

$envPath = __DIR__ . '/../.env';

if (!file_exists($envPath)) {
    // On Railway, environment variables are injected directly — no .env file needed
    return;
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    // Skip comments
    if (str_starts_with(trim($line), '#')) {
        continue;
    }

    // Split on the first = sign only
    $parts = explode('=', $line, 2);
    if (count($parts) !== 2) {
        continue;
    }

    $key = trim($parts[0]);
    $value = trim($parts[1]);

    // Remove surrounding quotes if present
    if (
        (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
        (str_starts_with($value, "'") && str_ends_with($value, "'"))
    ) {
        $value = substr($value, 1, -1);
    }

    $_ENV[$key] = $value;
    putenv("$key=$value");
}
