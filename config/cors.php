<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
    'https://lexash06.vercel.app',           // Frontend React di Vercel
    'https://sh06-digital-sign-cert-production.up.railway.app', // Backend Railway
    'http://localhost:3000',                  // React development (jika pakai Create React App)
    'http://localhost:5173',                  // React development (jika pakai Vite)
    'http://localhost:5174',                  // Vite port alternatif
    'http://127.0.0.1:8000',
    'http://localhost:8000',                  // Laravel local development
   ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
