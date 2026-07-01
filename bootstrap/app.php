<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

if (getenv('VERCEL')) {
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

    // Copy SQLite database to /tmp so it's writable
    $dbPath = database_path('database.sqlite');
    $tmpDbPath = '/tmp/database.sqlite';
    if (file_exists($dbPath) && !file_exists($tmpDbPath)) {
        copy($dbPath, $tmpDbPath);
        chmod($tmpDbPath, 0666);
    }
    putenv('DB_DATABASE=' . $tmpDbPath);
    $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $tmpDbPath;
}

return $app;
