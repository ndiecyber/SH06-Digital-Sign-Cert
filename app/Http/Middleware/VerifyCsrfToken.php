<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        '*',                    // Disable CSRF SEMUA route (development)
        'api/*',
        '/api/*',
        'sanctum/csrf-cookie',
    ];
}