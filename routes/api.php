<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Certificate;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Signature;
use App\Models\Team;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Storage;

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['csrf' => 'ok']);
});

$resolveUser = function (?Request $request = null) {
    if (Auth::check()) {
        return Auth::user();
    }
    if ($request) {
        $id = $request->input('uploaded_by_id') 
            ?? $request->input('user_id') 
            ?? $request->input('signer_id')
            ?? $request->header('X-User-Id');
        if ($id) {
            $user = User::find($id);
            if ($user) return $user;
        }
        $email = $request->input('uploaded_by_email') 
            ?? $request->input('user_email') 
            ?? $request->input('email')
            ?? $request->header('X-User-Email');
        if ($email) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
            if ($user) return $user;
        }
        $name = $request->input('uploaded_by_name') 
            ?? $request->input('user_name') 
            ?? $request->input('name');
        if ($name) {
            $user = User::whereRaw('LOWER(name) = ?', [strtolower(trim($name))])->first();
            if ($user) return $user;
        }
    }
    return User::first();
};

// ============ AUTH ============

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
        'role' => 'user',
    ]);

    return response()->json(['success' => true, 'user' => $user], 201);
});

Route::post('/auth/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
    }

    $request->session()->regenerate();

    return response()->json(['success' => true, 'user' => Auth::user()]);
});

Route::get('/auth/users', function () {
    return response()->json(User::all());
});

Route::put('/auth/user', function (Request $request) {
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $targetUser = User::where('email', $request->email)->firstOrFail();

    $targetUser->update($request->only([
        'name',
        'email',
        'role',
        'avatar',
        'visual_signature',
    ]));

    return response()->json(['success' => true, 'user' => $targetUser->fresh()]);
});

// ============ DOCUMENTS ============

Route::get('/documents', function () {
    $docs = Document::with(['uploadedBy', 'signatures.signer'])->latest()->get();

    $mapped = $docs->map(function ($doc) {
        $targetSigners = $doc->signatures->map(function ($sig) {
            return [
                'id' => $sig->signer?->id,
                'name' => $sig->signer?->name,
                'email' => $sig->signer?->email,
                'status' => $sig->signed_at ? 'signed' : 'pending',
                'signed_at' => $sig->signed_at,
            ];
        })->values()->toArray();

        $arr = $doc->toArray();
        $arr['uploaded_by_id'] = $doc->uploaded_by_id;
        $arr['uploaded_by_name'] = $doc->uploadedBy?->name ?? 'Pengguna';
        $arr['uploaded_by_email'] = $doc->uploadedBy?->email ?? '';
        $arr['target_signers'] = $targetSigners;
        $arr['target_signer_email'] = $targetSigners[0]['email'] ?? null;
        $arr['target_signer_name'] = $targetSigners[0]['name'] ?? null;
        return $arr;
    });

    return response()->json($mapped);
});

Route::post('/documents', function (Request $request) use ($resolveUser) {
    $request->validate([
        'title' => 'required|string|max:255',
        'type' => 'nullable|string|max:255',
        'file' => 'required|file|mimes:pdf,docx,xlsx|max:10240',
    ]);

    $path = $request->file('file')->store('documents');

    $user = $resolveUser($request);
    $userId = $user?->id ?? User::first()?->id;
    $userName = $user?->name ?? 'Pengguna';

    $doc = Document::create([
        'title' => $request->title,
        'type' => $request->type ?? 'General',
        'status' => 'draft',
        'file_path' => $path,
        'uploaded_by_id' => $userId,
    ]);

    // Kalau ada target signer, langsung buat request tanda tangan
    if ($request->has('target_signer_emails')) {
        $raw = $request->target_signer_emails;
        $emails = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);
        foreach ($emails as $email) {
            $signer = User::where('email', $email)->first();
            if ($signer) {
                Signature::create([
                    'document_id' => $doc->id,
                    'signer_id' => $signer->id,
                    'signed_at' => null,
                    'ip_address' => null,
                ]);
            }
        }
        if (count($emails) > 0) {
            $doc->update(['status' => 'pending']);
        }
    }

    ActivityLog::create([
        'user_id' => $userId,
        'action' => 'upload',
        'description' => $userName . ' mengunggah dokumen baru: ' . $doc->title,
        'ip_address' => $request->ip(),
    ]);

    return response()->json(['success' => true, 'document' => $doc->load('uploadedBy')], 201);
});

Route::delete('/documents/{id}', function ($id) use ($resolveUser) {
    $doc = Document::findOrFail($id);
    $title = $doc->title;
    $doc->delete();

    $user = $resolveUser();
    ActivityLog::create([
        'user_id' => $user?->id,
        'action' => 'system',
        'description' => ($user?->name ?? 'Pengguna') . ' menghapus dokumen: ' . $title,
        'ip_address' => request()->ip(),
    ]);

    return response()->json(['success' => true]);
});

Route::post('/documents/{id}/request-signer', function (Request $request, $id) use ($resolveUser) {
    $user = $resolveUser($request);
    if ($user && $user->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'Hanya admin yang dapat meminta tanda tangan dari user lain.'], 403);
    }

    $doc = Document::findOrFail($id);

    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $signer = User::where('email', $request->email)->first();

    // Hindari duplikat kalau user yang sama sudah jadi target di dokumen ini
    $existing = Signature::where('document_id', $doc->id)
        ->where('signer_id', $signer->id)
        ->first();

    if (!$existing) {
        Signature::create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'signed_at' => null,
            'ip_address' => null,
        ]);
    }

    if ($doc->status === 'draft') {
        $doc->update(['status' => 'pending']);
    }

    return response()->json(['success' => true, 'document' => $doc->load(['uploadedBy', 'signatures.signer'])]);
});

Route::post('/documents/{id}/sign', function (Request $request, $id) use ($resolveUser) {
    $doc = Document::findOrFail($id);
    $user = $resolveUser($request);

    $signerId = $request->input('signer_id') 
        ?? $request->input('user_id') 
        ?? $user?->id;
    $email = $request->input('email') 
        ?? $request->header('X-User-Email') 
        ?? $user?->email;

    $sig = Signature::where('document_id', $doc->id)
        ->where(function ($query) use ($signerId, $email) {
            if ($signerId) {
                $query->where('signer_id', $signerId);
            }
            if ($email) {
                $query->orWhereHas('signer', function ($q) use ($email) {
                    $q->whereRaw('LOWER(email) = ?', [strtolower(trim($email))]);
                });
            }
        })
        ->whereNull('signed_at')
        ->first();

    if (!$sig) {
        return response()->json(['success' => false, 'message' => 'Anda tidak memiliki permintaan tanda tangan yang pending untuk dokumen ini.'], 403);
    }

    $sig->update(['signed_at' => now(), 'ip_address' => $request->ip()]);

    $pending = Signature::where('document_id', $doc->id)->whereNull('signed_at')->count();
    if ($pending === 0) {
        $doc->update(['status' => 'signed']);
    }

    ActivityLog::create([
        'user_id' => $user?->id ?? $sig->signer_id,
        'action' => 'signed',
        'description' => ($user?->name ?? $sig->signer?->name ?? 'Pengguna') . ' menandatangani dokumen: ' . $doc->title,
        'ip_address' => $request->ip(),
    ]);

    return response()->json(['success' => true, 'document' => $doc->load(['uploadedBy', 'signatures.signer'])]);
});

Route::post('/documents/verify', function (Request $request) {
    $request->validate([
        'file' => 'required|file',
    ]);

    $uploadedHash = hash_file('sha256', $request->file('file')->getPathname());

    $signedDocs = Document::where('status', 'signed')
        ->whereNotNull('file_path')
        ->with('signatures.signer')
        ->get();

    foreach ($signedDocs as $doc) {
        if (!Storage::exists($doc->file_path)) {
            continue;
        }

        $storedHash = hash('sha256', Storage::get($doc->file_path));

        if (hash_equals($storedHash, $uploadedHash)) {
            $signedSignatures = $doc->signatures->whereNotNull('signed_at');

            $signerNames = $signedSignatures->pluck('signer.name')->filter()->implode(', ');
            $signerEmails = $signedSignatures->pluck('signer.email')->filter()->implode(', ');
            $lastSigned = $signedSignatures->sortByDesc('signed_at')->first();

            return response()->json([
                'verified' => true,
                'title' => $doc->title,
                'signer' => $signerNames ?: '-',
                'email' => $signerEmails ?: '-',
                'timestamp' => $lastSigned ? $lastSigned->signed_at->format('d M Y, H:i') . ' WIB' : '-',
                'ca' => 'Balai Sertifikasi Elektronik (BSrE) CA',
                'file_hash' => $uploadedHash,
            ]);
        }
    }

    return response()->json([
        'verified' => false,
        'message' => 'Dokumen tidak terdaftar atau belum ditandatangani secara sah di sistem LEXA.'
    ]);
});

// ============ CERTIFICATES ============

Route::get('/certificates', function () {
    return response()->json(Certificate::orderBy('valid_until', 'asc')->get());
});

Route::post('/certificates', function (Request $request) use ($resolveUser) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'holder' => 'required|string|max:255',
        'validityDays' => 'nullable|integer',
    ]);

    $days = $validated['validityDays'] ?? 365;

    $cert = Certificate::create([
        'name' => $validated['name'],
        'holder' => $validated['holder'],
        'status' => 'valid', // sesuai check constraint DB, JANGAN pakai 'active'
        'issued_at' => now()->toDateString(),
        'valid_until' => now()->addDays($days)->toDateString(),
    ]);

    $user = $resolveUser($request);
    ActivityLog::create([
        'user_id' => $user?->id,
        'action' => 'system',
        'description' => ($user?->name ?? 'Pengguna') . ' menerbitkan sertifikat: ' . $cert->name,
        'ip_address' => $request->ip(),
    ]);

    return response()->json(['success' => true, 'certificate' => $cert], 201);
});

Route::delete('/certificates/{id}', function ($id) use ($resolveUser) {
    $cert = Certificate::findOrFail($id);
    $cert->delete();

    $user = $resolveUser();
    ActivityLog::create([
        'user_id' => $user?->id,
        'action' => 'system',
        'description' => ($user?->name ?? 'Pengguna') . ' mencabut sertifikat: ' . $cert->name,
        'ip_address' => request()->ip(),
    ]);

    return response()->json(['success' => true]);
});

// ============ TEAMS ============

Route::get('/teams', function () {
    return response()->json(Team::with('members')->latest()->get());
});

Route::post('/teams', function (Request $request) use ($resolveUser) {
    $user = $resolveUser($request);

    // If ID is passed, update existing team and its members
    if ($request->has('id') && $request->id) {
        $team = Team::find($request->id);
        if ($team) {
            if ($request->has('name')) $team->update(['name' => $request->name]);
            if ($request->has('description')) $team->update(['description' => $request->description]);
            if ($request->has('members') && is_array($request->members)) {
                $syncData = [];
                foreach ($request->members as $m) {
                    $mId = is_array($m) ? ($m['id'] ?? null) : $m;
                    $role = is_array($m) ? ($m['pivot']['role'] ?? $m['role'] ?? 'Member') : 'Member';
                    if ($mId) $syncData[$mId] = ['role' => $role];
                }
                $team->members()->sync($syncData);
            }
            return response()->json(['success' => true, 'team' => $team->load('members')]);
        }
    }

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);

    // If team with same name exists, update it instead of creating duplicate!
    $existing = Team::where('name', $validated['name'])->first();
    if ($existing) {
        if ($request->has('members') && is_array($request->members)) {
            $syncData = [];
            foreach ($request->members as $m) {
                $mId = is_array($m) ? ($m['id'] ?? null) : $m;
                $role = is_array($m) ? ($m['pivot']['role'] ?? $m['role'] ?? 'Member') : 'Member';
                if ($mId) $syncData[$mId] = ['role' => $role];
            }
            $existing->members()->sync($syncData);
        }
        return response()->json(['success' => true, 'team' => $existing->load('members')]);
    }

    $team = Team::create([
        ...$validated,
        'created_by_id' => $user?->id,
    ]);

    if ($user) {
        $team->members()->attach($user->id, ['role' => 'Leader']);
    }

    ActivityLog::create([
        'user_id' => $user?->id,
        'action' => 'system',
        'description' => ($user?->name ?? 'Pengguna') . ' membuat tim baru: ' . $team->name,
        'ip_address' => $request->ip(),
    ]);

    return response()->json(['success' => true, 'team' => $team->load('members')], 201);
});

Route::delete('/teams/{id}', function ($id) use ($resolveUser) {
    $team = Team::findOrFail($id);
    $name = $team->name;
    $team->delete();

    $user = $resolveUser();
    ActivityLog::create([
        'user_id' => $user?->id,
        'action' => 'system',
        'description' => ($user?->name ?? 'Pengguna') . ' menghapus tim: ' . $name,
        'ip_address' => request()->ip(),
    ]);

    return response()->json(['success' => true]);
});

Route::post('/teams/{id}/members', function (Request $request, $id) use ($resolveUser) {
    $team = Team::findOrFail($id);

    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'role' => 'required|string',
    ]);

    if ($team->members()->where('user_id', $validated['user_id'])->exists()) {
        return response()->json(['success' => false, 'message' => 'User sudah terdaftar di tim ini.'], 422);
    }

    $team->members()->attach($validated['user_id'], ['role' => $validated['role']]);

    $user = $resolveUser($request);
    ActivityLog::create([
        'user_id' => $user?->id,
        'action' => 'update',
        'description' => ($user?->name ?? 'Pengguna') . ' menambahkan anggota ke tim: ' . $team->name,
        'ip_address' => $request->ip(),
    ]);

    return response()->json(['success' => true, 'team' => $team->load('members')]);
});

Route::delete('/teams/{id}/members/{userId}', function ($id, $userId) use ($resolveUser) {
    $team = Team::findOrFail($id);
    $team->members()->detach($userId);

    $user = $resolveUser();
    ActivityLog::create([
        'user_id' => $user?->id,
        'action' => 'update',
        'description' => ($user?->name ?? 'Pengguna') . ' mengeluarkan anggota dari tim: ' . $team->name,
        'ip_address' => request()->ip(),
    ]);

    return response()->json(['success' => true, 'team' => $team->load('members')]);
});

// ============ API KEYS ============

Route::get('/api-keys', function () {
    return response()->json(ApiKey::latest()->get());
});

Route::post('/api-keys', function (Request $request) use ($resolveUser) {
    $validated = $request->validate(['name' => 'required|string|max:255']);

    $rawKey = 'lx_live_' . bin2hex(random_bytes(20));

    $apiKey = ApiKey::create([
        'name' => $validated['name'],
        'key' => $rawKey,
        'status' => 'active',
        'last_used_at' => null,
    ]);

    $user = $resolveUser($request);
    ActivityLog::create([
        'user_id' => $user?->id,
        'action' => 'system',
        'description' => ($user?->name ?? 'Pengguna') . ' membuat API Key baru: ' . $apiKey->name,
        'ip_address' => $request->ip(),
    ]);

    // key mentah cuma dikirim sekali saat create
    return response()->json(['success' => true, 'api_key' => $apiKey, 'raw_key' => $rawKey], 201);
});

Route::post('/api-keys/{id}/toggle', function ($id) {
    $key = ApiKey::findOrFail($id);
    $key->update(['status' => $key->status === 'active' ? 'inactive' : 'active']);
    return response()->json(['success' => true, 'api_key' => $key]);
});

Route::delete('/api-keys/{id}', function ($id) {
    $key = ApiKey::findOrFail($id);
    $key->delete();
    return response()->json(['success' => true]);
});

// ============ ACTIVITIES ============

Route::get('/activities', function () {
    $logs = ActivityLog::with('user')->latest()->get();

    $mapped = $logs->map(function ($log) {
        return [
            'id' => $log->id,
            'action' => $log->action,
            'description' => $log->description,
            'user_name' => $log->user->name ?? 'System',
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at,
        ];
    });

    return response()->json($mapped);
});

Route::post('/activities', function (Request $request) use ($resolveUser) {
    $user = $resolveUser($request);
    $log = ActivityLog::create([
        'user_id' => $user?->id,
        'action' => $request->input('action', 'system'),
        'description' => $request->input('description', ''),
        'ip_address' => $request->ip(),
    ]);
    return response()->json(['success' => true, 'activity' => $log], 201);
});