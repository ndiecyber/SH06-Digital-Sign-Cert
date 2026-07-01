<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->useStoragePath('/tmp/storage');

if (!is_dir('/tmp/storage/framework/views')) {
    mkdir('/tmp/storage/framework/views', 0777, true);
}

if (!is_dir('/tmp/storage/framework/cache')) {
    mkdir('/tmp/storage/framework/cache', 0777, true);
}

if (!is_dir('/tmp/storage/framework/sessions')) {
    mkdir('/tmp/storage/framework/sessions', 0777, true);
}

if (getenv('VERCEL')) {
    // Copy SQLite database to /tmp so it's writable
    $dbPath = __DIR__ . '/../database/database.sqlite';
    $tmpDbPath = '/tmp/database.sqlite';
    if (file_exists($dbPath) && !file_exists($tmpDbPath)) {
        copy($dbPath, $tmpDbPath);
        chmod($tmpDbPath, 0666);
    }
    putenv('DB_DATABASE=' . $tmpDbPath);
    $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $tmpDbPath;
}

$app->handleRequest(Request::capture());