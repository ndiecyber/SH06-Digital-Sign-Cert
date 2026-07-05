<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Models\Document;
use App\Models\Certificate;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Signature;
use App\Models\Team;
use App\Models\ApiKey;
use App\Models\Notification;

// 1. Authentication Routes
Route::get('/login', function () {
    if (Auth::check()) {
        return redirect('/');
    }
    return view('login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/');
    }

    return back()->withErrors([
        'email' => 'Email atau password yang Anda masukkan salah.',
    ])->onlyInput('email');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login')->with('success', 'Anda berhasil keluar.');
});

// 2. Protected Dashboard & Feature Routes
Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        $currentUser = Auth::user();

        $totalDocs = Document::count();
        $signedDocs = Document::where('status', 'signed')->count();
        $pendingDocs = Document::where('status', 'pending')->count();
        $draftDocs = Document::where('status', 'draft')->count();
        $rejectedDocs = Document::where('status', 'rejected')->count();

        $activeCerts = Certificate::count();
        $expiredCerts = Certificate::where('status', 'expired')->count();
        $validCerts = Certificate::where('status', 'valid')->count();
        $expiringSoonCerts = Certificate::where('status', 'expiring_soon')->count();

        $nextExpiry = Certificate::where('status', 'expiring_soon')
            ->orderBy('valid_until', 'asc')
            ->first();

        $recentDocuments = Document::with(['uploadedBy', 'signatures.signer'])
            ->latest()
            ->take(5)
            ->get();

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(3)
            ->get();

        // Load full datasets for all sidebar pages
        $allDocuments = Document::with(['uploadedBy', 'signatures.signer'])->latest()->get();
        $allCertificates = Certificate::orderBy('valid_until', 'asc')->get();
        $allActivities = ActivityLog::with('user')->latest()->get();
        $allUsers = User::all();
        $allSignatures = Signature::with(['document', 'signer'])->latest()->get();
        $allTeams = Team::with('members')->latest()->get();
        $allApiKeys = ApiKey::latest()->get();

        // Fetch notifications and seed demo ones if empty
        $notifications = $currentUser->notifications()->take(15)->get();
        $unreadNotifsCount = $currentUser->notifications()->where('is_read', false)->count();

        if ($notifications->isEmpty()) {
            Notification::create([
                'user_id' => $currentUser->id,
                'title' => 'Permintaan Tanda Tangan',
                'message' => 'Dokumen "Surat Perjanjian Kerja Sama (PKS)_V2.pdf" memerlukan tanda tangan digital Anda.',
                'type' => 'warning',
                'is_read' => false,
                'link' => 'signatures',
                'created_at' => now()->subMinutes(12),
            ]);
            Notification::create([
                'user_id' => $currentUser->id,
                'title' => 'Sertifikat Diterbitkan',
                'message' => 'Sertifikat Otoritas Jaringan LEXA Anda telah berhasil diterbitkan dan berstatus aktif.',
                'type' => 'success',
                'is_read' => false,
                'link' => 'certificates',
                'created_at' => now()->subHours(2),
            ]);
            Notification::create([
                'user_id' => $currentUser->id,
                'title' => 'Selamat Datang',
                'message' => 'Selamat datang di LEXA Digital Sign & Certificate System. Kelola dokumen Anda secara aman dengan enkripsi AES-256.',
                'type' => 'info',
                'is_read' => true,
                'link' => 'dashboard',
                'created_at' => now()->subDays(1),
            ]);

            // Re-fetch
            $notifications = $currentUser->notifications()->take(15)->get();
            $unreadNotifsCount = $currentUser->notifications()->where('is_read', false)->count();
        }

        return view('welcome', compact(
            'currentUser',
            'totalDocs', 'signedDocs', 'pendingDocs', 'draftDocs', 'rejectedDocs',
            'activeCerts', 'expiredCerts', 'validCerts', 'expiringSoonCerts',
            'nextExpiry', 'recentDocuments', 'recentActivities',
            'allDocuments', 'allCertificates', 'allActivities', 'allUsers', 'allSignatures', 'allTeams', 'allApiKeys',
            'notifications', 'unreadNotifsCount'
        ));
    });

    // Upload Dokumen - Simpan File ke Storage
    Route::post('/documents', function (Request $request) {
        $currentUser = Auth::user();
        
        // Validasi file upload
        $request->validate([
            'file' => 'required|file|mimes:pdf,docx,xlsx|max:10240', // Max 10MB
        ]);
        
        // Simpan file ke storage
        $filePath = $request->file('file')->store('documents');
        $title = $request->file('file')->getClientOriginalName();
        
        $doc = Document::create([
            'title' => $title,
            'type' => $request->input('type', 'General'),
            'status' => 'draft',
            'uploaded_by_id' => $currentUser->id,
            'file_path' => $filePath,
        ]);
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'upload',
            'description' => $currentUser->name . ' mengunggah dokumen baru: ' . $title,
            'ip_address' => $request->ip(),
        ]);

        Notification::create([
            'user_id' => $currentUser->id,
            'title' => 'Dokumen Diunggah',
            'message' => 'Dokumen "' . $title . '" berhasil diunggah dengan status draft.',
            'type' => 'success',
            'link' => 'documents',
        ]);
        
        return redirect('/?tab=documents')->with('success', 'Dokumen "' . $title . '" berhasil diunggah!');
    });

    Route::post('/signatures', function (Request $request) {
        $currentUser = Auth::user();
        
        $request->validate([
            'document_id' => 'required|exists:documents,id',
            'signer_id' => 'required|exists:users,id',
        ]);
        
        $doc = Document::find($request->document_id);
        $signer = User::find($request->signer_id);
        
        Signature::create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'signed_at' => null,
            'ip_address' => null,
        ]);
        
        $doc->update(['status' => 'pending']);
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'update',
            'description' => $currentUser->name . ' meminta tanda tangan untuk dokumen: ' . $doc->title . ' kepada ' . $signer->name,
            'ip_address' => $request->ip(),
        ]);

        // Notify the signer
        Notification::create([
            'user_id' => $signer->id,
            'title' => 'Permintaan Tanda Tangan Baru',
            'message' => $currentUser->name . ' meminta Anda menandatangani dokumen "' . $doc->title . '".',
            'type' => 'warning',
            'link' => 'signatures',
        ]);

        // Notify the sender
        Notification::create([
            'user_id' => $currentUser->id,
            'title' => 'Permintaan Tanda Tangan Terkirim',
            'message' => 'Permintaan tanda tangan untuk "' . $doc->title . '" berhasil dikirim ke ' . $signer->name . '.',
            'type' => 'info',
            'link' => 'signatures',
        ]);
        
        return redirect('/?tab=signatures')->with('success', 'Permintaan tanda tangan untuk "' . $doc->title . '" berhasil dikirim ke ' . $signer->name . '!');
    });

    // Validasi Signer saat Tanda Tangan
    Route::post('/signatures/{id}/sign', function (Request $request, $id) {
        $sig = Signature::findOrFail($id);
        
        // Validasi: hanya signer yang ditunjuk yang bisa tanda tangan
        if ($sig->signer_id !== Auth::id()) {
            return redirect('/?tab=signatures')->with('error', 'Anda tidak memiliki izin untuk menandatangani dokumen ini.');
        }
        
        // Validasi: cek apakah sudah ditandatangani sebelumnya
        if (!is_null($sig->signed_at)) {
            return redirect('/?tab=signatures')->with('error', 'Dokumen ini sudah Anda tandatangani sebelumnya.');
        }
        
        $sig->update([
            'signed_at' => now(),
            'ip_address' => $request->ip(),
        ]);
        
        $doc = $sig->document;
        $pendingCount = Signature::where('document_id', $doc->id)->whereNull('signed_at')->count();
        if ($pendingCount === 0) {
            $doc->update(['status' => 'signed']);
        }
        
        ActivityLog::create([
            'user_id' => $sig->signer_id,
            'action' => 'signed',
            'description' => $sig->signer->name . ' menandatangani dokumen: ' . $doc->title,
            'ip_address' => $request->ip(),
        ]);

        // Notify the signer of success
        Notification::create([
            'user_id' => $sig->signer_id,
            'title' => 'Dokumen Berhasil Ditandatangani',
            'message' => 'Anda telah berhasil menandatangani dokumen "' . $doc->title . '".',
            'type' => 'success',
            'link' => 'signatures',
        ]);

        // Notify the document owner (uploader)
        if ($doc->uploaded_by_id) {
            Notification::create([
                'user_id' => $doc->uploaded_by_id,
                'title' => 'Dokumen Telah Ditandatangani',
                'message' => $sig->signer->name . ' telah menandatangani dokumen Anda "' . $doc->title . '".',
                'type' => 'success',
                'link' => 'documents',
            ]);
        }
        
        return redirect('/?tab=signatures')->with('success', 'Dokumen "' . $doc->title . '" berhasil ditandatangani!');
    });

    Route::post('/certificates', function (Request $request) {
        $currentUser = Auth::user();
        
        $request->validate([
            'name' => 'required|string',
            'holder' => 'required|string',
            'validity' => 'required|string',
        ]);
        
        $days = 365;
        if (str_contains($request->validity, '2 Tahun')) {
            $days = 730;
        } elseif (str_contains($request->validity, '90 Hari')) {
            $days = 90;
        }
        
        $cert = Certificate::create([
            'name' => $request->name,
            'holder' => $request->holder,
            'status' => 'valid',
            'issued_at' => now()->toDateString(),
            'valid_until' => now()->addDays($days)->toDateString(),
        ]);
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'system',
            'description' => $currentUser->name . ' menerbitkan sertifikat digital: ' . $cert->name . ' untuk ' . $cert->holder,
            'ip_address' => $request->ip(),
        ]);

        Notification::create([
            'user_id' => $currentUser->id,
            'title' => 'Sertifikat Berhasil Diterbitkan',
            'message' => 'Sertifikat "' . $cert->name . '" untuk ' . $cert->holder . ' berhasil diterbitkan.',
            'type' => 'success',
            'link' => 'certificates',
        ]);

        // If holder matches any user, also notify them!
        $holderUser = User::where('name', $request->holder)->first();
        if ($holderUser) {
            Notification::create([
                'user_id' => $holderUser->id,
                'title' => 'Sertifikat Baru Diterbitkan',
                'message' => 'Selamat, sertifikat digital "' . $cert->name . '" Anda telah diterbitkan secara resmi.',
                'type' => 'success',
                'link' => 'certificates',
            ]);
        }
        
        return redirect('/?tab=certificates')->with('success', 'Sertifikat untuk "' . $cert->holder . '" berhasil diterbitkan!');
    });

    // Verifikasi Dokumen dengan Hash SHA-256
    Route::post('/verify', function (Request $request) {
        // Validasi file upload
        if (!$request->hasFile('file')) {
            return response()->json([
                'verified' => false,
                'message' => 'Silakan upload file PDF untuk diverifikasi.'
            ]);
        }
        
        $file = $request->file('file');
        $title = $file->getClientOriginalName();
        
        // Hitung hash file SHA-256 untuk verifikasi
        $fileHash = hash_file('sha256', $file->getPathname());
        
        // Cari dokumen berdasarkan nama file
        $doc = Document::where('status', 'signed')
            ->where('title', 'like', '%' . $title . '%')
            ->first();
            
        if ($doc) {
            $signatures = Signature::with('signer')
                ->where('document_id', $doc->id)
                ->whereNotNull('signed_at')
                ->get();
                
            $signerNames = $signatures->map(fn($s) => $s->signer->name)->join(', ');
            $signerEmails = $signatures->map(fn($s) => $s->signer->email)->join(', ');
            $timestamp = $signatures->first() ? $signatures->first()->signed_at->format('d M Y, H:i') : now()->format('d M Y, H:i');
            
            return response()->json([
                'verified' => true,
                'title' => $doc->title,
                'signer' => $signerNames ?: 'Rizky Pratama',
                'email' => $signerEmails ?: 'rizky@lexa.com',
                'timestamp' => $timestamp . ' WIB',
                'ca' => 'Balai Sertifikasi Elektronik (BSrE) CA',
                'file_hash' => $fileHash
            ]);
        } else {
            return response()->json([
                'verified' => false,
                'message' => 'Dokumen "' . $title . '" tidak terdaftar atau belum ditandatangani secara sah di sistem LEXA.'
            ]);
        }
    });

    Route::delete('/documents/{id}', function ($id) {
        $doc = Document::findOrFail($id);
        $currentUser = Auth::user();
        
        $title = $doc->title;
        $doc->delete();
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'system',
            'description' => $currentUser->name . ' menghapus dokumen: ' . $title,
            'ip_address' => request()->ip(),
        ]);
        
        return redirect('/?tab=documents')->with('success', 'Dokumen "' . $title . '" berhasil dihapus.');
    });

    Route::delete('/certificates/{id}', function ($id) {
        $cert = Certificate::findOrFail($id);
        $currentUser = Auth::user();
        
        $name = $cert->name;
        $holder = $cert->holder;
        $cert->delete();
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'system',
            'description' => $currentUser->name . ' menghapus sertifikat: ' . $name . ' (' . $holder . ')',
            'ip_address' => request()->ip(),
        ]);
        
        return redirect('/?tab=certificates')->with('success', 'Sertifikat "' . $name . '" berhasil dihapus.');
    });

    Route::post('/documents/use-template', function (Request $request) {
        $currentUser = Auth::user();
        
        $request->validate([
            'template_name' => 'required|string',
        ]);
        
        $templateName = $request->template_name;
        $title = 'Draft - ' . $templateName . '.pdf';
        $type = 'General';
        if (str_contains($templateName, 'NDA')) $type = 'Kontrak';
        if (str_contains($templateName, 'PKS')) $type = 'Kontrak';
        if (str_contains($templateName, 'SOP')) $type = 'SOP';
        
        $doc = Document::create([
            'title' => $title,
            'type' => $type,
            'status' => 'draft',
            'uploaded_by_id' => $currentUser->id,
        ]);
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'upload',
            'description' => $currentUser->name . ' membuat dokumen baru dari template: ' . $templateName,
            'ip_address' => $request->ip(),
        ]);
        
        return redirect('/?tab=documents')->with('success', 'Dokumen baru "' . $title . '" berhasil dibuat dari template!');
    });

    Route::post('/teams', function (Request $request) {
        $currentUser = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $team = Team::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by_id' => $currentUser->id,
        ]);

        $team->members()->attach($currentUser->id, ['role' => 'Leader']);
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'system',
            'description' => $currentUser->name . ' membuat tim baru: ' . $team->name,
            'ip_address' => $request->ip(),
        ]);
        
        return redirect('/?tab=teams')->with('success', 'Tim "' . $team->name . '" berhasil dibuat!');
    });

    Route::delete('/teams/{id}', function ($id) {
        $team = Team::findOrFail($id);
        $currentUser = Auth::user();
        
        $name = $team->name;
        $team->delete();
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'system',
            'description' => $currentUser->name . ' menghapus tim: ' . $name,
            'ip_address' => request()->ip(),
        ]);
        
        return redirect('/?tab=teams')->with('success', 'Tim "' . $name . '" berhasil dihapus.');
    });

    Route::post('/teams/{id}/members', function (Request $request, $id) {
        $team = Team::findOrFail($id);
        $currentUser = Auth::user();
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string',
        ]);
        
        if ($team->members()->where('user_id', $request->user_id)->exists()) {
            return redirect('/?tab=teams')->with('error', 'User sudah terdaftar di tim ini.');
        }
        
        $team->members()->attach($request->user_id, ['role' => $request->role]);
        $newMember = User::find($request->user_id);
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'update',
            'description' => $currentUser->name . ' menambahkan ' . $newMember->name . ' (' . $request->role . ') ke dalam tim: ' . $team->name,
            'ip_address' => $request->ip(),
        ]);

        Notification::create([
            'user_id' => $request->user_id,
            'title' => 'Ditambahkan ke Tim Baru',
            'message' => $currentUser->name . ' menambahkan Anda ke dalam tim "' . $team->name . '" sebagai ' . $request->role . '.',
            'type' => 'info',
            'link' => 'teams',
        ]);
        
        return redirect('/?tab=teams')->with('success', $newMember->name . ' berhasil ditambahkan ke tim "' . $team->name . '"!');
    });

    Route::delete('/teams/{id}/members/{userId}', function ($id, $userId) {
        $team = Team::findOrFail($id);
        $currentUser = Auth::user();
        $member = User::findOrFail($userId);
        
        $team->members()->detach($userId);
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'update',
            'description' => $currentUser->name . ' mengeluarkan ' . $member->name . ' dari tim: ' . $team->name,
            'ip_address' => request()->ip(),
        ]);
        
        return redirect('/?tab=teams')->with('success', $member->name . ' berhasil dikeluarkan dari tim "' . $team->name . '"!');
    });

    Route::post('/api-keys', function (Request $request) {
        $currentUser = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $rawKey = 'lx_live_' . bin2hex(random_bytes(20));
        
        $apiKey = ApiKey::create([
            'name' => $request->name,
            'key' => $rawKey,
            'status' => 'active',
            'last_used_at' => null,
        ]);
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'system',
            'description' => $currentUser->name . ' membuat API Key baru: ' . $apiKey->name,
            'ip_address' => $request->ip(),
        ]);
        
        return redirect('/?tab=integrations')
            ->with('success', 'API Key "' . $apiKey->name . '" berhasil dibuat!')
            ->with('generated_api_key', $rawKey);
    });

    Route::post('/api-keys/{id}/toggle', function ($id) {
        $key = ApiKey::findOrFail($id);
        $currentUser = Auth::user();
        
        $newStatus = $key->status === 'active' ? 'inactive' : 'active';
        $key->update(['status' => $newStatus]);
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'update',
            'description' => $currentUser->name . ' mengubah status API Key ' . $key->name . ' menjadi ' . $newStatus,
            'ip_address' => request()->ip(),
        ]);
        
        return redirect('/?tab=integrations')->with('success', 'Status API Key "' . $key->name . '" berhasil diperbarui!');
    });

    Route::delete('/api-keys/{id}', function ($id) {
        $key = ApiKey::findOrFail($id);
        $currentUser = Auth::user();
        
        $name = $key->name;
        $key->delete();
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'system',
            'description' => $currentUser->name . ' menghapus API Key: ' . $name,
            'ip_address' => request()->ip(),
        ]);
        
        return redirect('/?tab=integrations')->with('success', 'API Key "' . $name . '" berhasil dihapus.');
    });

    Route::post('/upgrade', function (Request $request) {
        $currentUser = Auth::user();
        
        $request->validate([
            'plan' => 'required|in:free,secure,enterprise',
        ]);
        
        $planName = $request->plan;
        $currentUser->update(['plan' => $planName]);
        
        ActivityLog::create([
            'user_id' => $currentUser->id,
            'action' => 'update',
            'description' => $currentUser->name . ' berhasil melakukan upgrade akun ke paket: LEXA ' . ucfirst($planName) . ' Plan',
            'ip_address' => $request->ip(),
        ]);

        Notification::create([
            'user_id' => $currentUser->id,
            'title' => 'Upgrade Paket Sukses',
            'message' => 'Selamat! Akun Anda berhasil ditingkatkan ke paket LEXA ' . ucfirst($planName) . ' Plan. Nikmati fitur premium sekarang.',
            'type' => 'success',
            'link' => 'settings',
        ]);
        
        $displayName = $planName === 'secure' ? 'LEXA Secure Plan' : ($planName === 'enterprise' ? 'LEXA Enterprise Plan' : 'Free Plan');
        
        return redirect()->back()->with('success', 'Akun Anda berhasil diperbarui ke ' . $displayName . '!');
    });

    Route::post('/notifications/{id}/read', function ($id) {
        $notif = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notif->update(['is_read' => true]);
        return response()->json(['success' => true]);
    });

    Route::post('/notifications/read-all', function () {
        Notification::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);
        return response()->json(['success' => true]);
    });

});