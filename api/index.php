<?php

// Vercel serverless environment adjustments
if (getenv('VERCEL')) {
    putenv('LOG_CHANNEL=stderr');
    putenv('VIEW_COMPILED_PATH=/tmp/views');
    putenv('SESSION_DRIVER=cookie');
    putenv('APP_DEBUG=true');
    
    $_ENV['LOG_CHANNEL'] = $_SERVER['LOG_CHANNEL'] = 'stderr';
    $_ENV['VIEW_COMPILED_PATH'] = $_SERVER['VIEW_COMPILED_PATH'] = '/tmp/views';
    $_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'cookie';
    $_ENV['APP_DEBUG'] = $_SERVER['APP_DEBUG'] = 'true';
    
    if (!is_dir('/tmp/views')) {
        mkdir('/tmp/views', 0777, true);
    }

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

require __DIR__ . '/../public/index.php';
