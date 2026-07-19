<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Certificate;
use App\Models\ActivityLog;
use App\Models\Document;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// 1. Register Endpoint
Route::post('/auth/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => 'user', // Default role
    ]);

    return response()->json([
        'success' => true,
        'message' => 'User registered successfully',
        'user' => $user
    ], 201);
});

// 2. Login Endpoint
Route::post('/auth/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'user' => Auth::user(),
    ]);
});

// 3. Protected Routes (Membutuhkan Login)
Route::middleware('auth')->group(function () {
    
    Route::get('/certificates', function () {
        return response()->json(Certificate::latest()->get());
    });

    Route::get('/activities', function () {
        return response()->json(ActivityLog::with('user')->latest()->take(20)->get());
    });

    Route::get('/documents', function () {
        return response()->json(Document::with('uploadedBy')->latest()->get());
    });
    
    // Tambahkan route API lainnya di sini sesuai kebutuhan frontend
});