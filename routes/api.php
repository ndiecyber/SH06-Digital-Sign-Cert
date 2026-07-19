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
    
    // === ROUTE YANG SUDAH ADA ===
    Route::get('/certificates', function () {
        return response()->json(Certificate::latest()->get());
    });

    Route::get('/activities', function () {
        return response()->json(ActivityLog::with('user')->latest()->take(20)->get());
    });

    Route::get('/documents', function () {
        return response()->json(Document::with('uploadedBy')->latest()->get());
    });

    // === ROUTE BARU YANG DITAMBAHKAN ===

    // Auth Users
    Route::get('/auth/users', function () {
        return response()->json(
            User::select('id', 'name', 'email', 'role', 'plan', 'avatar', 'created_at')
                ->get()
        );
    });

    // Documents - Create
    Route::post('/documents', function (Request $request) {
        // Logika ini masih sederhana, nanti bisa dipindah ke Controller
        // Sesuaikan dengan kebutuhan tabel Document kamu
        return response()->json([
            'success' => true,
            'message' => 'Document created successfully',
            'document' => [] // isi data document nanti
        ], 201);
    });

    // Documents - Request Signer
    Route::post('/documents/{id}/request-signer', function (Request $request, $id) {
        return response()->json([
            'success' => true,
            'message' => 'Signer requested'
        ]);
    });

    // Documents - Sign
    Route::post('/documents/{id}/sign', function (Request $request, $id) {
        return response()->json([
            'success' => true,
            'message' => 'Document signed successfully'
        ]);
    });

    // Documents - Delete
    Route::delete('/documents/{id}', function ($id) {
        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully'
        ]);
    });

    // Certificates - Create
    Route::post('/certificates', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Certificate created successfully',
            'certificate' => []
        ], 201);
    });

    // Certificates - Delete
    Route::delete('/certificates/{id}', function ($id) {
        return response()->json([
            'success' => true,
            'message' => 'Certificate deleted successfully'
        ]);
    });

    // Teams
    Route::get('/teams', function () {
        return response()->json([]); // isi sesuai model Team nanti
    });

    Route::post('/teams', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Team saved successfully'
        ]);
    });

    Route::delete('/teams/{id}', function ($id) {
        return response()->json([
            'success' => true,
            'message' => 'Team deleted successfully'
        ]);
    });

    // Activities - Create (Log Activity)
    Route::post('/activities', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Activity logged successfully'
        ]);
    });

    // Tambahan lain jika diperlukan
    // Route::put('/auth/user', ...);
    // Route::put('/auth/upgrade-plan', ...);

});