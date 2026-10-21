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
use App\Models\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['csrf' => 'ok']);
});

Route::get('/health', function () {
    $dbOk = false;
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbOk = true;
    } catch (\Throwable $e) {
        $dbOk = false;
    }

    $storageOk = false;
    try {
        $testFile = 'health_check.txt';
        \Illuminate\Support\Facades\Storage::put($testFile, 'ok');
        \Illuminate\Support\Facades\Storage::delete($testFile);
        $storageOk = true;
    } catch (\Throwable $e) {
        $storageOk = false;
    }

    $isHealthy = $dbOk && $storageOk;

    return response()->json([
        'status' => $isHealthy ? 'ok' : 'degraded',
        'database' => $dbOk ? 'connected' : 'error',
        'storage' => $storageOk ? 'writable' : 'error',
        'timestamp' => now()->toIso8601String(),
        'app' => config('app.name', 'LEXA'),
        'environment' => config('app.env', 'production'),
    ], $isHealthy ? 200 : 503);
});

$resolveUser = function (?Request $request = null, bool $allowFallback = false) {
    if (Auth::check()) {
        return Auth::user();
    }
    if ($request) {
        // 1. Client authentication headers (prioritas tertinggi)
        $headerId = $request->header('X-User-Id');
        if ($headerId) {
            $user = User::find($headerId);
            if ($user) return $user;
        }

        $headerEmail = $request->header('X-User-Email');
        if ($headerEmail) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($headerEmail))])->first();
            if ($user) return $user;
        }

        // 2. Explicit requester fields in input
        $id = $request->input('uploaded_by_id') 
            ?? $request->input('user_id') 
            ?? $request->input('requester_id')
            ?? $request->input('admin_id');
        if ($id) {
            $user = User::find($id);
            if ($user) return $user;
        }

        $email = $request->input('uploaded_by_email') 
            ?? $request->input('user_email') 
            ?? $request->input('requester_email')
            ?? $request->input('admin_email');
        if ($email) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
            if ($user) return $user;
        }

        // 3. Fallback generic signer_id
        $signerId = $request->input('signer_id');
        if ($signerId) {
            $user = User::find($signerId);
            if ($user) return $user;
        }

        // 4. Generic email HANYA jika bukan route yang parameternya email target (misal request-signer)
        if (!$request->is('*request-signer*')) {
            $genericEmail = $request->input('email');
            if ($genericEmail) {
                $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($genericEmail))])->first();
                if ($user) return $user;
            }
        }

        $name = $request->input('uploaded_by_name') 
            ?? $request->input('user_name') 
            ?? $request->input('name');
        if ($name) {
            $user = User::whereRaw('LOWER(name) = ?', [strtolower(trim($name))])->first();
            if ($user) return $user;
        }
    }
    return $allowFallback ? User::first() : null;
};

$clearCache = function (array $keys = ['api_documents', 'api_certificates', 'api_teams', 'api_keys', 'api_activities', 'api_users']) {
    foreach ($keys as $key) {
        Cache::forget($key);
    }
};

// ============ AUTH ============

Route::post('/auth/register', function (Request $request) use ($clearCache) {
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

    $clearCache(['api_users', 'api_activities']);

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
    $users = Cache::remember('api_users', 60, function () {
        return User::select('id', 'name', 'email', 'role', 'created_at')->get()->toArray();
    });
    return response()->json($users);
});

Route::put('/auth/user', function (Request $request) use ($resolveUser, $clearCache) {
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $currentUser = $resolveUser($request);
    $targetUser = User::where('email', $request->email)->firstOrFail();

    $allowedFields = ['name'];

    // Hanya admin yang diizinkan mengubah role pengguna
    if ($currentUser && $currentUser->role === 'admin') {
        $allowedFields[] = 'role';
    }

    $targetUser->update($request->only($allowedFields));
    $clearCache(['api_users', 'api_activities']);

    return response()->json(['success' => true, 'user' => $targetUser->fresh()]);
});

// ============ DOCUMENTS ============

Route::get('/documents', function () {
    $mapped = Cache::remember('api_documents', 60, function () {
        $docs = Document::with([
            'uploadedBy:id,name,email,role',
            'signatures' => function ($query) {
                $query->with('signer:id,name,email,role');
            }
        ])->latest()->get();

        return $docs->map(function ($doc) {
            $targetSigners = $doc->signatures->map(function ($sig) use ($doc) {
                $status = $sig->signed_at 
                    ? 'signed' 
                    : ($doc->status === 'rejected' ? 'rejected' : 'pending');
                return [
                    'id' => $sig->signer?->id,
                    'name' => $sig->signer?->name,
                    'email' => $sig->signer?->email,
                    'status' => $status,
                    'signed_at' => $sig->signed_at ? $sig->signed_at->toIso8601String() : null,
                ];
            })->values()->toArray();

            $latestSigned = $doc->signatures->whereNotNull('signed_at')->sortByDesc('signed_at')->first();

            $arr = $doc->toArray();
            $arr['uploaded_by_id'] = $doc->uploaded_by_id;
            $arr['uploaded_by_name'] = $doc->uploadedBy?->name ?? 'Pengguna';
            $arr['uploaded_by_email'] = $doc->uploadedBy?->email ?? '';
            $arr['target_signers'] = $targetSigners;
            $arr['target_signer_email'] = $targetSigners[0]['email'] ?? null;
            $arr['target_signer_name'] = $targetSigners[0]['name'] ?? null;
            $arr['signed_at'] = $latestSigned?->signed_at ? $latestSigned->signed_at->toIso8601String() : null;
            return $arr;
        })->toArray();
    });

    return response()->json($mapped);
});

Route::post('/documents', function (Request $request) use ($resolveUser, $clearCache) {
    $request->validate([
        'title' => 'required|string|max:255',
        'type' => 'nullable|string|max:255',
        'file' => 'required|file|mimes:pdf,docx,xlsx|max:10240',
    ]);

    $path = $request->file('file')->store('documents');

    $user = $resolveUser($request, true);
    $userId = $user?->id ?? User::first()?->id;
    $userName = $user?->name ?? 'Pengguna';

    $doc = Document::create([
        'title' => $request->title,
        'type' => $request->type ?? 'General',
        'status' => 'draft',
        'file_path' => $path,
        'uploaded_by_id' => $userId,
    ]);

    // Kalau ada target signer, langsung buat request tanda tangan (case-insensitive & cegah duplikat)
    if ($request->has('target_signer_emails')) {
        $raw = $request->target_signer_emails;
        $emails = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);
        foreach ($emails as $email) {
            $cleanEmail = strtolower(trim((string)$email));
            if (!$cleanEmail) continue;
            $signer = User::whereRaw('LOWER(email) = ?', [$cleanEmail])->first();
            if ($signer) {
                Signature::firstOrCreate([
                    'document_id' => $doc->id,
                    'signer_id' => $signer->id,
                ], [
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

    $clearCache(['api_documents', 'api_activities', 'api_dashboard_summary']);

    return response()->json(['success' => true, 'document' => $doc->load('uploadedBy')], 201);
});

Route::post('/documents/use-template', function (Request $request) use ($resolveUser, $clearCache) {
    $request->validate(['template_name' => 'required|string']);
    
    $user = $resolveUser($request, true);
    $userId = $user?->id ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id ?? 1;
    $userName = $user?->name ?? 'Pengguna';

    $templateName = $request->template_name;
    $cleanName = str_replace('Template ', '', $templateName);
    $title = 'Draft - ' . $cleanName . '.pdf';
    $type = 'General';
    if (stripos($templateName, 'NDA') !== false) $type = 'Kontrak';
    if (stripos($templateName, 'PKS') !== false) $type = 'Kontrak';
    if (stripos($templateName, 'SOP') !== false) $type = 'SOP';
    
    $doc = Document::create([
        'title' => $title,
        'type' => $type,
        'status' => 'draft',
        'file_path' => null,
        'uploaded_by_id' => $userId,
    ]);
    
    ActivityLog::create([
        'user_id' => $userId,
        'action' => 'upload',
        'description' => $userName . ' membuat dokumen baru dari template: ' . $templateName,
        'ip_address' => $request->ip(),
    ]);

    $clearCache(['api_documents', 'api_activities', 'api_dashboard_summary']);

    return response()->json(['success' => true, 'document' => $doc->load('uploadedBy')], 201);
});

Route::delete('/documents/{id}', function (Request $request, $id) use ($resolveUser, $clearCache) {
    $doc = Document::findOrFail($id);
    $user = $resolveUser($request, true);
    $actorId = $user?->id ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id ?? 1;

    // Proteksi Otorisasi: Hanya pemilik dokumen atau admin yang dapat menghapus
    if ($user && $user->role !== 'admin' && $doc->uploaded_by_id !== $user->id) {
        return response()->json([
            'success' => false, 
            'message' => 'Anda tidak memiliki hak untuk menghapus dokumen ini.'
        ], 403);
    }

    // Integritas Dokumen: Dokumen yang telah sah ditandatangani dilindungi dari penghapusan sembarangan
    if ($doc->status === 'signed' && (!$user || $user->role !== 'admin')) {
        return response()->json([
            'success' => false, 
            'message' => 'Dokumen yang telah ditandatangani secara sah tidak dapat dihapus.'
        ], 403);
    }

    $title = $doc->title;
    $filePath = $doc->file_path;

    $doc->delete();

    // Bersihkan file fisik di storage agar tidak terjadi orphaned storage
    if ($filePath && Storage::exists($filePath)) {
        Storage::delete($filePath);
    }

    ActivityLog::create([
        'user_id' => $actorId,
        'action' => 'delete',
        'description' => ($user?->name ?? 'Pengguna') . ' menghapus dokumen: ' . $title,
        'ip_address' => $request->ip(),
    ]);

    $clearCache(['api_documents', 'api_activities', 'api_dashboard_summary']);

    return response()->json(['success' => true]);
});

Route::post('/documents/{id}/request-signer', function (Request $request, $id) use ($resolveUser, $clearCache) {
    $doc = Document::findOrFail($id);
    $user = $resolveUser($request);

    $isOwner = $user && ($user->id === $doc->uploaded_by_id);
    $isAdmin = $user && ($user->role === 'admin');

    // Izinkan jika user adalah admin ATAU pemilik dokumen
    if ($user && !$isAdmin && !$isOwner) {
        return response()->json(['success' => false, 'message' => 'Hanya admin atau pemilik dokumen yang dapat meminta tanda tangan dari user lain.'], 403);
    }

    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $signer = User::whereRaw('LOWER(email) = ?', [strtolower(trim($request->email))])->first();
    if (!$signer) {
        return response()->json(['success' => false, 'message' => 'User penanda tangan tidak ditemukan.'], 404);
    }

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

    if (in_array($doc->status, ['draft', 'rejected'])) {
        $doc->update(['status' => 'pending']);
    }

    $clearCache(['api_documents', 'api_activities', 'api_dashboard_summary']);

    return response()->json(['success' => true, 'document' => $doc->load(['uploadedBy', 'signatures.signer'])]);
});

Route::post('/documents/{id}/sign', function (Request $request, $id) use ($resolveUser, $clearCache) {
    $doc = Document::findOrFail($id);
    $user = $resolveUser($request);

    if ($doc->status === 'rejected') {
        return response()->json(['success' => false, 'message' => 'Dokumen ini telah berstatus ditolak dan tidak dapat ditandatangani.'], 400);
    }

    if ($doc->status === 'signed') {
        return response()->json(['success' => false, 'message' => 'Dokumen ini telah selesai ditandatangani.'], 400);
    }

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

    $clearCache(['api_documents', 'api_activities', 'api_dashboard_summary']);

    return response()->json(['success' => true, 'document' => $doc->load(['uploadedBy', 'signatures.signer'])]);
});

Route::post('/documents/{id}/reject', function (Request $request, $id) use ($resolveUser, $clearCache) {
    $doc = Document::findOrFail($id);
    $user = $resolveUser($request);

    if ($doc->status === 'signed') {
        return response()->json([
            'success' => false, 
            'message' => 'Dokumen yang sudah ditandatangani tidak dapat ditolak.'
        ], 400);
    }

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

    $isOwner = $user && ($user->id === $doc->uploaded_by_id);
    $isAdmin = $user && ($user->role === 'admin');

    if (!$sig && !$isOwner && !$isAdmin) {
        return response()->json([
            'success' => false, 
            'message' => 'Anda tidak memiliki wewenang atau permintaan pending untuk menolak dokumen ini.'
        ], 403);
    }

    $reason = $request->input('reason');
    $reasonText = $reason ? trim($reason) : 'Tidak ada alasan yang disertakan.';

    $doc->update(['status' => 'rejected']);

    $actorName = $user?->name ?? $sig?->signer?->name ?? 'Pengguna';
    $actorId = $user?->id ?? $sig?->signer_id ?? $doc->uploaded_by_id;

    ActivityLog::create([
        'user_id' => $actorId,
        'action' => 'rejected',
        'description' => $actorName . ' menolak dokumen: ' . $doc->title . ' (Alasan: ' . $reasonText . ')',
        'ip_address' => $request->ip(),
    ]);

    if ($doc->uploaded_by_id) {
        Notification::create([
            'user_id' => $doc->uploaded_by_id,
            'title' => 'Dokumen Ditolak',
            'message' => $actorName . ' menolak dokumen "' . $doc->title . '". Alasan: ' . $reasonText,
            'type' => 'warning',
            'link' => 'documents',
        ]);
    }

    $clearCache(['api_documents', 'api_activities', 'api_dashboard_summary']);

    return response()->json([
        'success' => true,
        'message' => 'Dokumen berhasil ditolak.',
        'document' => $doc->load(['uploadedBy', 'signatures.signer'])
    ]);
});

Route::post('/signatures/{id}/reject', function (Request $request, $id) use ($resolveUser, $clearCache) {
    $sig = Signature::with('document')->findOrFail($id);
    $doc = $sig->document;
    $user = $resolveUser($request);

    if ($sig->signer_id !== $user?->id && $user?->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin untuk menolak tanda tangan ini.'], 403);
    }

    $reason = $request->input('reason');
    $reasonText = $reason ? trim($reason) : 'Tidak ada alasan yang disertakan.';

    $doc->update(['status' => 'rejected']);

    $actorName = $user?->name ?? $sig->signer?->name ?? 'Pengguna';
    ActivityLog::create([
        'user_id' => $user?->id ?? $sig->signer_id,
        'action' => 'rejected',
        'description' => $actorName . ' menolak dokumen: ' . $doc->title . ' (Alasan: ' . $reasonText . ')',
        'ip_address' => $request->ip(),
    ]);

    if ($doc->uploaded_by_id) {
        Notification::create([
            'user_id' => $doc->uploaded_by_id,
            'title' => 'Dokumen Ditolak',
            'message' => $actorName . ' menolak dokumen "' . $doc->title . '". Alasan: ' . $reasonText,
            'type' => 'warning',
            'link' => 'documents',
        ]);
    }

    $clearCache(['api_documents', 'api_activities', 'api_dashboard_summary']);

    return response()->json([
        'success' => true,
        'message' => 'Dokumen berhasil ditolak.',
        'document' => $doc->load(['uploadedBy', 'signatures.signer'])
    ]);
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
    $certs = Cache::remember('api_certificates', 60, function () {
        return Certificate::orderBy('valid_until', 'asc')->get()->toArray();
    });
    return response()->json($certs);
});

Route::post('/certificates', function (Request $request) use ($resolveUser, $clearCache) {
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

    $user = $resolveUser($request, true);
    $actorId = $user?->id ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id ?? 1;

    ActivityLog::create([
        'user_id' => $actorId,
        'action' => 'system',
        'description' => ($user?->name ?? 'Pengguna') . ' menerbitkan sertifikat: ' . $cert->name,
        'ip_address' => $request->ip(),
    ]);

    $clearCache(['api_certificates', 'api_activities', 'api_dashboard_summary']);

    return response()->json(['success' => true, 'certificate' => $cert], 201);
});

Route::delete('/certificates/{id}', function (Request $request, $id) use ($resolveUser, $clearCache) {
    $cert = Certificate::findOrFail($id);
    $certName = $cert->name;
    $cert->delete();

    $user = $resolveUser($request, true);
    $actorId = $user?->id ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id ?? 1;

    ActivityLog::create([
        'user_id' => $actorId,
        'action' => 'system',
        'description' => ($user?->name ?? 'Pengguna') . ' mencabut sertifikat: ' . $certName,
        'ip_address' => $request->ip(),
    ]);

    $clearCache(['api_certificates', 'api_activities', 'api_dashboard_summary']);

    return response()->json(['success' => true]);
});

// ============ TEAMS ============

Route::get('/teams', function () {
    $teams = Cache::remember('api_teams', 60, function () {
        return Team::with('members')->latest()->get()->toArray();
    });
    return response()->json($teams);
});

Route::post('/teams', function (Request $request) use ($resolveUser, $clearCache) {
    $user = $resolveUser($request, true);
    $userId = $user?->id ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id ?? 1;

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
            $clearCache(['api_teams', 'api_activities']);
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
        $clearCache(['api_teams', 'api_activities']);
        return response()->json(['success' => true, 'team' => $existing->load('members')]);
    }

    $team = Team::create([
        ...$validated,
        'created_by_id' => $userId,
    ]);

    $team->members()->attach($userId, ['role' => 'Leader']);

    ActivityLog::create([
        'user_id' => $userId,
        'action' => 'system',
        'description' => ($user?->name ?? 'Pengguna') . ' membuat tim baru: ' . $team->name,
        'ip_address' => $request->ip(),
    ]);

    $clearCache(['api_teams', 'api_activities']);

    return response()->json(['success' => true, 'team' => $team->load('members')], 201);
});

Route::delete('/teams/{id}', function (Request $request, $id) use ($resolveUser, $clearCache) {
    $team = Team::findOrFail($id);
    $name = $team->name;
    $team->delete();

    $user = $resolveUser($request, true);
    $actorId = $user?->id ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id ?? 1;

    ActivityLog::create([
        'user_id' => $actorId,
        'action' => 'system',
        'description' => ($user?->name ?? 'Pengguna') . ' menghapus tim: ' . $name,
        'ip_address' => $request->ip(),
    ]);

    $clearCache(['api_teams', 'api_activities']);

    return response()->json(['success' => true]);
});

Route::post('/teams/{id}/members', function (Request $request, $id) use ($resolveUser, $clearCache) {
    $team = Team::findOrFail($id);

    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'role' => 'required|string',
    ]);

    if ($team->members()->where('user_id', $validated['user_id'])->exists()) {
        return response()->json(['success' => false, 'message' => 'User sudah terdaftar di tim ini.'], 422);
    }

    $team->members()->attach($validated['user_id'], ['role' => $validated['role']]);

    $user = $resolveUser($request, true);
    $actorId = $user?->id ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id ?? 1;

    ActivityLog::create([
        'user_id' => $actorId,
        'action' => 'update',
        'description' => ($user?->name ?? 'Pengguna') . ' menambahkan anggota ke tim: ' . $team->name,
        'ip_address' => $request->ip(),
    ]);

    $clearCache(['api_teams', 'api_activities']);

    return response()->json(['success' => true, 'team' => $team->load('members')]);
});

Route::delete('/teams/{id}/members/{userId}', function (Request $request, $id, $userId) use ($resolveUser, $clearCache) {
    $team = Team::findOrFail($id);
    $team->members()->detach($userId);

    $user = $resolveUser($request, true);
    $actorId = $user?->id ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id ?? 1;

    ActivityLog::create([
        'user_id' => $actorId,
        'action' => 'update',
        'description' => ($user?->name ?? 'Pengguna') . ' mengeluarkan anggota dari tim: ' . $team->name,
        'ip_address' => $request->ip(),
    ]);

    $clearCache(['api_teams', 'api_activities']);

    return response()->json(['success' => true, 'team' => $team->load('members')]);
});

// ============ API KEYS ============

Route::get('/api-keys', function () {
    $keys = Cache::remember('api_keys', 60, function () {
        return ApiKey::latest()->get()->toArray();
    });
    return response()->json($keys);
});

Route::post('/api-keys', function (Request $request) use ($resolveUser, $clearCache) {
    $validated = $request->validate(['name' => 'required|string|max:255']);

    $rawKey = 'lx_live_' . bin2hex(random_bytes(20));

    $apiKey = ApiKey::create([
        'name' => $validated['name'],
        'key' => $rawKey,
        'status' => 'active',
        'last_used_at' => null,
    ]);

    $user = $resolveUser($request, true);
    $actorId = $user?->id ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id ?? 1;

    ActivityLog::create([
        'user_id' => $actorId,
        'action' => 'system',
        'description' => ($user?->name ?? 'Pengguna') . ' membuat API Key baru: ' . $apiKey->name,
        'ip_address' => $request->ip(),
    ]);

    $clearCache(['api_keys', 'api_activities']);

    // key mentah cuma dikirim sekali saat create
    return response()->json(['success' => true, 'api_key' => $apiKey, 'raw_key' => $rawKey], 201);
});

Route::post('/api-keys/{id}/toggle', function ($id) use ($clearCache) {
    $key = ApiKey::findOrFail($id);
    $key->update(['status' => $key->status === 'active' ? 'inactive' : 'active']);
    $clearCache(['api_keys', 'api_activities']);
    return response()->json(['success' => true, 'api_key' => $key]);
});

Route::delete('/api-keys/{id}', function ($id) use ($clearCache) {
    $key = ApiKey::findOrFail($id);
    $key->delete();
    $clearCache(['api_keys', 'api_activities']);
    return response()->json(['success' => true]);
});

// ============ ACTIVITIES ============

Route::get('/activities', function () {
    $mapped = Cache::remember('api_activities', 30, function () {
        $logs = ActivityLog::with('user:id,name,email')->latest()->limit(50)->get();

        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user_name' => $log->user->name ?? 'System',
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
            ];
        })->toArray();
    });

    return response()->json($mapped);
});

Route::post('/activities', function (Request $request) use ($resolveUser, $clearCache) {
    $user = $resolveUser($request, true);
    $actorId = $user?->id ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id ?? 1;
    $log = ActivityLog::create([
        'user_id' => $actorId,
        'action' => $request->input('action', 'system'),
        'description' => $request->input('description', ''),
        'ip_address' => $request->ip(),
    ]);
    $clearCache(['api_activities', 'api_dashboard_summary']);
    return response()->json(['success' => true, 'activity' => $log], 201);
});

// ============ AGGREGATED DASHBOARD (ULTRA FAST) ============

Route::get('/dashboard-summary', function () {
    $summary = Cache::remember('api_dashboard_summary', 30, function () {
        $docs = Document::with([
            'uploadedBy:id,name,email,role',
            'signatures' => function ($query) {
                $query->with('signer:id,name,email,role');
            }
        ])->latest()->get();

        $mappedDocs = $docs->map(function ($doc) {
            $targetSigners = $doc->signatures->map(function ($sig) use ($doc) {
                $status = $sig->signed_at 
                    ? 'signed' 
                    : ($doc->status === 'rejected' ? 'rejected' : 'pending');
                return [
                    'id' => $sig->signer?->id,
                    'name' => $sig->signer?->name,
                    'email' => $sig->signer?->email,
                    'status' => $status,
                    'signed_at' => $sig->signed_at ? $sig->signed_at->toIso8601String() : null,
                ];
            })->values()->toArray();

            $latestSigned = $doc->signatures->whereNotNull('signed_at')->sortByDesc('signed_at')->first();

            $arr = $doc->toArray();
            $arr['uploaded_by_id'] = $doc->uploaded_by_id;
            $arr['uploaded_by_name'] = $doc->uploadedBy?->name ?? 'Pengguna';
            $arr['uploaded_by_email'] = $doc->uploadedBy?->email ?? '';
            $arr['target_signers'] = $targetSigners;
            $arr['target_signer_email'] = $targetSigners[0]['email'] ?? null;
            $arr['target_signer_name'] = $targetSigners[0]['name'] ?? null;
            $arr['signed_at'] = $latestSigned?->signed_at ? $latestSigned->signed_at->toIso8601String() : null;
            return $arr;
        })->toArray();

        $certs = Certificate::orderBy('valid_until', 'asc')->get()->toArray();

        $logs = ActivityLog::with('user:id,name,email')->latest()->limit(50)->get();
        $mappedLogs = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user_name' => $log->user->name ?? 'System',
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
            ];
        })->toArray();

        return [
            'documents' => $mappedDocs,
            'certificates' => $certs,
            'activities' => $mappedLogs,
        ];
    });

    return response()->json($summary);
});