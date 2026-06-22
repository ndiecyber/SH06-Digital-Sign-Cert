<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEXA Dashboard - Digital Signature & Certificate Management</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Outfit', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f5f7ff',
                            100: '#ebf0ff',
                            500: '#4f46e5', // Elegant Indigo accent
                            600: '#4338ca',
                            700: '#3730a3',
                            900: '#1e1b4b',
                            950: '#07071f', // Deep dark sidebar background
                        },
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444',
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f8fafc; 
        }
        
        /* Premium Dot Grid Pattern */
        .dot-pattern {
            background-image: radial-gradient(rgba(99, 102, 241, 0.06) 1.2px, transparent 1.2px);
            background-size: 24px 24px;
        }

        .glass-card { 
            background: rgba(255, 255, 255, 0.75); 
            backdrop-filter: blur(24px); 
            -webkit-backdrop-filter: blur(24px); 
            border: 1px solid rgba(255, 255, 255, 0.8); 
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.015), 
                        inset 0 1px 1px rgba(255, 255, 255, 0.8),
                        0 20px 25px -5px rgba(0, 0, 0, 0.01);
        }
        
        .hover-lift { 
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
        }
        .hover-lift:hover { 
            transform: translateY(-4px) scale(1.01); 
            box-shadow: 0 30px 60px -15px rgba(99, 102, 241, 0.08), 
                        0 10px 20px -5px rgba(99, 102, 241, 0.03); 
            border-color: rgba(99, 102, 241, 0.25); 
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

        /* Navigation Active Pill with designer styling */
        .nav-item-active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0.03) 100%);
            border-left: 4px solid #6366f1;
            color: #c7d2fe !important;
            font-weight: 600;
        }

        /* Pulsing dot for pending actions */
        @keyframes pulse-dot {
            0% { transform: scale(0.85); opacity: 0.4; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.85); opacity: 0.4; }
        }
        .pulse-indicator {
            animation: pulse-dot 2.2s infinite ease-in-out;
        }
        
        /* Soft glowing state indicators */
        .status-badge-signed {
            background-color: rgba(16, 185, 129, 0.06);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.18);
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.04);
        }
        .status-badge-pending {
            background-color: rgba(245, 158, 11, 0.06);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.18);
            box-shadow: 0 0 12px rgba(245, 158, 11, 0.04);
        }
        .status-badge-draft {
            background-color: rgba(100, 116, 139, 0.06);
            color: #475569;
            border: 1px solid rgba(100, 116, 139, 0.18);
        }
        .status-badge-rejected {
            background-color: rgba(239, 68, 68, 0.06);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.18);
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.04);
        }

        /* Custom Hover-lift table rows */
        .custom-row {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .custom-row:hover {
            background-color: rgba(248, 250, 252, 0.8) !important;
            transform: translateY(-1.5px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.9), 0 8px 16px -4px rgba(99, 102, 241, 0.04);
        }
    </style>
    <script>
        window.allUsersList = @json($allUsers);
        window.allTeamsList = @json($allTeams);
        window.allApiKeysList = @json($allApiKeys);
        window.allDocsList = @json($allDocuments);
        window.allCertsList = @json($allCertificates);
    </script>
</head>
<body class="text-slate-800 flex h-screen overflow-hidden relative dot-pattern" x-data="{ 
    sidebarOpen: true, 
    searchQuery: '', 
    filterStatus: 'all',
    activeTab: (new URLSearchParams(window.location.search)).get('tab') || 'dashboard',
    uploadModal: false,
    signatureModal: false,
    certModal: false,
    verifyModal: false,
    createTeamModal: false,
    manageTeamMembersModal: false,
    toastShow: false,
    toastMessage: '',
    toastType: 'success',
    fileName: '',
    signerName: '',
    certName: '',
    certHolder: '',
    verifying: false,
    verified: false,
    verifyFileName: '',
    verifyDetails: null,
    selectedTeam: { id: null, name: '', description: '', members: [] },
    teamName: '',
    teamDescription: '',
    newMemberId: '',
    newMemberRole: 'Member',
    allUsersList: window.allUsersList,
    allTeamsList: window.allTeamsList,
    allApiKeysList: window.allApiKeysList,
    apiKeyModal: false,
    newApiKeyName: '',
    generatedKey: '{{ session('generated_api_key') ?? '' }}',
    apiDocTab: 'curl',
    timeFilter: 'month',
    stats: {
        totalDocs: {{ $totalDocs }},
        signedDocs: {{ $signedDocs }},
        pendingDocs: {{ $pendingDocs }},
        draftDocs: {{ $draftDocs }},
        rejectedDocs: {{ $rejectedDocs }},
        totalCerts: {{ $activeCerts }},
        activeCerts: {{ $activeCerts }},
        validCerts: {{ $validCerts }},
        expiringSoonCerts: {{ $expiringSoonCerts }},
        expiredCerts: {{ $expiredCerts }},
    },
    getFilterSubtext() {
        if (this.timeFilter === 'today') return 'hari ini';
        if (this.timeFilter === 'week') return 'dari minggu lalu';
        if (this.timeFilter === 'month') return 'dari bulan lalu';
        if (this.timeFilter === 'year') return 'dari tahun lalu';
        return 'dari bulan lalu';
    },
    updateDashboard() {
        const refDate = new Date();
        const isToday = (dateStr) => {
            const d = new Date(dateStr);
            return d.toDateString() === refDate.toDateString();
        };
        const isThisWeek = (dateStr) => {
            const d = new Date(dateStr);
            const diffTime = Math.abs(refDate - d);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays <= 7;
        };
        const isThisMonth = (dateStr) => {
            const d = new Date(dateStr);
            return d.getMonth() === refDate.getMonth() && d.getFullYear() === refDate.getFullYear();
        };
        const isThisYear = (dateStr) => {
            const d = new Date(dateStr);
            return d.getFullYear() === refDate.getFullYear();
        };
        const filterFn = (dateStr) => {
            if (this.timeFilter === 'today') return isToday(dateStr);
            if (this.timeFilter === 'week') return isThisWeek(dateStr);
            if (this.timeFilter === 'month') return isThisMonth(dateStr);
            if (this.timeFilter === 'year') return isThisYear(dateStr);
            return true;
        };
        const filteredDocs = (window.allDocsList || []).filter(doc => filterFn(doc.created_at));
        const filteredCerts = (window.allCertsList || []).filter(cert => filterFn(cert.created_at || cert.issued_at));

        this.stats.totalDocs = filteredDocs.length;
        this.stats.signedDocs = filteredDocs.filter(d => d.status === 'signed').length;
        this.stats.pendingDocs = filteredDocs.filter(d => d.status === 'pending').length;
        this.stats.draftDocs = filteredDocs.filter(d => d.status === 'draft').length;
        this.stats.rejectedDocs = filteredDocs.filter(d => d.status === 'rejected').length;

        this.stats.totalCerts = filteredCerts.length;
        this.stats.activeCerts = filteredCerts.length;
        this.stats.validCerts = filteredCerts.filter(c => c.status === 'valid').length;
        this.stats.expiringSoonCerts = filteredCerts.filter(c => c.status === 'expiring_soon').length;
        this.stats.expiredCerts = filteredCerts.filter(c => c.status === 'expired').length;

        if (window.donutChartInstance) {
            window.donutChartInstance.data.datasets[0].data = [
                this.stats.signedDocs,
                this.stats.pendingDocs,
                this.stats.draftDocs,
                this.stats.rejectedDocs
            ];
            window.donutChartInstance.update();
        }
        if (window.certDonutChartInstance) {
            window.certDonutChartInstance.data.datasets[0].data = [
                this.stats.validCerts,
                this.stats.expiringSoonCerts,
                this.stats.expiredCerts
            ];
            window.certDonutChartInstance.update();
        }
        if (window.lineChartInstance) {
            let labels = [];
            let dataPoints = [];
            if (this.timeFilter === 'today') {
                labels = ['00:00', '06:00', '12:00', '18:00', '24:00'];
                dataPoints = [
                    filteredDocs.filter(d => new Date(d.created_at).getHours() < 6).length,
                    filteredDocs.filter(d => new Date(d.created_at).getHours() >= 6 && new Date(d.created_at).getHours() < 12).length,
                    filteredDocs.filter(d => new Date(d.created_at).getHours() >= 12 && new Date(d.created_at).getHours() < 18).length,
                    filteredDocs.filter(d => new Date(d.created_at).getHours() >= 18).length,
                    filteredDocs.length
                ];
            } else if (this.timeFilter === 'week') {
                labels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
                dataPoints = [0, 0, 0, 0, 0, 0, 0];
                filteredDocs.forEach(d => {
                    const day = new Date(d.created_at).getDay();
                    const idx = day === 0 ? 6 : day - 1;
                    dataPoints[idx]++;
                });
            } else if (this.timeFilter === 'month') {
                labels = ['1 Jun', '6 Jun', '11 Jun', '16 Jun', '21 Jun'];
                dataPoints = [
                    filteredDocs.filter(d => new Date(d.created_at).getDate() <= 5).length,
                    filteredDocs.filter(d => new Date(d.created_at).getDate() > 5 && new Date(d.created_at).getDate() <= 10).length,
                    filteredDocs.filter(d => new Date(d.created_at).getDate() > 10 && new Date(d.created_at).getDate() <= 15).length,
                    filteredDocs.filter(d => new Date(d.created_at).getDate() > 15 && new Date(d.created_at).getDate() <= 20).length,
                    filteredDocs.length
                ];
            } else if (this.timeFilter === 'year') {
                labels = ['Jan', 'Mar', 'Mei', 'Jul', 'Sep', 'Nov'];
                dataPoints = [
                    filteredDocs.filter(d => new Date(d.created_at).getMonth() <= 1).length,
                    filteredDocs.filter(d => new Date(d.created_at).getMonth() > 1 && new Date(d.created_at).getMonth() <= 3).length,
                    filteredDocs.filter(d => new Date(d.created_at).getMonth() > 3 && new Date(d.created_at).getMonth() <= 5).length,
                    filteredDocs.filter(d => new Date(d.created_at).getMonth() > 5 && new Date(d.created_at).getMonth() <= 7).length,
                    filteredDocs.filter(d => new Date(d.created_at).getMonth() > 7 && new Date(d.created_at).getMonth() <= 9).length,
                    filteredDocs.length
                ];
            }
            window.lineChartInstance.data.labels = labels;
            window.lineChartInstance.data.datasets[0].data = dataPoints;
            window.lineChartInstance.update();
        }
    },
    showToast(msg, type = 'success') {
        this.toastMessage = msg;
        this.toastType = type;
        this.toastShow = true;
        setTimeout(() => { this.toastShow = false; }, 4000);
    },
    simuleVerify() {
        let fName = this.verifyFileName;
        if (!fName) {
            this.showToast('Silakan unggah dokumen PDF untuk diverifikasi.', 'error');
            return;
        }
        this.verifying = true;
        this.verified = false;
        this.verifyDetails = null;

        let formData = new FormData();
        formData.append('file_name', fName);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('/verify', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            this.verifying = false;
            if (data.verified) {
                this.verified = true;
                this.verifyDetails = data;
                this.showToast('Verifikasi stempel digital sukses!', 'success');
            } else {
                this.verified = false;
                this.showToast(data.message, 'error');
            }
        })
        .catch(err => {
            this.verifying = false;
            this.showToast('Koneksi server gagal.', 'error');
        });
    }
}"
x-init="
    @if(session('success'))
        showToast('{{ session('success') }}', 'success');
    @endif
    @if(session('error'))
        showToast('{{ session('error') }}', 'error');
    @endif
    @if(session('generated_api_key'))
        apiKeyModal = true;
    @endif
    document.addEventListener('DOMContentLoaded', () => {
        updateDashboard();
    });
">

    <!-- Floating Background Gradient Blobs -->
    <div class="fixed top-[-10%] left-[-10%] w-[45vw] h-[45vw] bg-indigo-300/20 rounded-full blur-[120px] pointer-events-none z-0"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-[45vw] h-[45vw] bg-pink-200/15 rounded-full blur-[120px] pointer-events-none z-0"></div>
    <div class="fixed top-[40%] right-[15%] w-[30vw] h-[30vw] bg-violet-200/15 rounded-full blur-[100px] pointer-events-none z-0"></div>


    <!-- Sidebar -->
    <aside class="bg-primary-950 text-white w-64 flex flex-col h-full transition-all duration-300 relative z-20" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full absolute'">
        <!-- Logo -->
        <div class="p-6 flex items-center space-x-3">
            <div class="bg-blue-600 p-1.5 rounded-lg">
                <i class="ph-bold ph-pen-nib text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-wide">LEXA</h1>
                <p class="text-[0.6rem] text-slate-400 font-medium uppercase tracking-wider">Software House</p>
            </div>
        </div>

        <div class="px-6 pb-4">
            <p class="text-[0.65rem] text-slate-400 font-semibold uppercase tracking-wider mb-2 leading-relaxed">
                Digital Signature &<br>Certificate Management System
            </p>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-4 space-y-1">
            <a href="#" @click="activeTab = 'dashboard'; searchQuery = ''" :class="activeTab === 'dashboard' ? 'nav-item-active text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/5'" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium transition-all duration-300">
                <i class="ph ph-squares-four text-xl"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" @click="activeTab = 'documents'; searchQuery = ''" :class="activeTab === 'documents' ? 'nav-item-active text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/5'" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium transition-all duration-200">
                <i class="ph ph-file-text text-xl"></i>
                <span>Documents</span>
            </a>
            <a href="#" @click="activeTab = 'signatures'; searchQuery = ''" :class="activeTab === 'signatures' ? 'nav-item-active text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/5'" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium transition-all duration-200">
                <i class="ph ph-signature text-xl"></i>
                <span>Signatures</span>
            </a>
            <a href="#" @click="activeTab = 'certificates'; searchQuery = ''" :class="activeTab === 'certificates' ? 'nav-item-active text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/5'" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium transition-all duration-200">
                <i class="ph ph-certificate text-xl"></i>
                <span>Certificates</span>
            </a>
            <a href="#" @click="activeTab = 'templates'; searchQuery = ''" :class="activeTab === 'templates' ? 'nav-item-active text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/5'" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium transition-all duration-200">
                <i class="ph ph-layout text-xl"></i>
                <span>Templates</span>
            </a>
            @if($currentUser->role === 'admin')
            <a href="#" @click="activeTab = 'users'; searchQuery = ''" :class="activeTab === 'users' ? 'nav-item-active text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/5'" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium transition-all duration-200">
                <i class="ph ph-users text-xl"></i>
                <span>Users & Roles</span>
            </a>
            @endif
            <a href="#" @click="activeTab = 'teams'; searchQuery = ''" :class="activeTab === 'teams' ? 'nav-item-active text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/5'" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium transition-all duration-200">
                <i class="ph ph-users-three text-xl"></i>
                <span>Teams</span>
            </a>
            @if($currentUser->role === 'admin')
            <a href="#" @click="activeTab = 'audit'; searchQuery = ''" :class="activeTab === 'audit' ? 'nav-item-active text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/5'" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium transition-all duration-200">
                <i class="ph ph-clock-counter-clockwise text-xl"></i>
                <span>Audit Trail</span>
            </a>
            <a href="#" @click="activeTab = 'integrations'; searchQuery = ''" :class="activeTab === 'integrations' ? 'nav-item-active text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/5'" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium transition-all duration-200">
                <i class="ph ph-plugs-connected text-xl"></i>
                <span>Integrations</span>
            </a>
            @endif
            <a href="#" @click="activeTab = 'settings'; searchQuery = ''" :class="activeTab === 'settings' ? 'nav-item-active text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/5'" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium transition-all duration-200">
                <i class="ph ph-gear text-xl"></i>
                <span>Settings</span>
            </a>
        </nav>

        <!-- Upgrade Box -->
        <div class="px-4 py-4">
            <div class="bg-gradient-to-br from-blue-900 to-primary-950 border border-blue-800 rounded-2xl p-4 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-blue-500 rounded-full blur-3xl opacity-20"></div>
                <div class="flex items-center space-x-2 mb-2">
                    <i class="ph-fill ph-crown text-yellow-400 text-xl"></i>
                    <h4 class="font-bold text-sm text-white">LEXA Secure Plan</h4>
                </div>
                <p class="text-xs text-slate-300 mb-3 leading-relaxed">Tingkatkan ke plan premium untuk fitur lebih lengkap dan penyimpanan lebih besar.</p>
                <button class="w-full bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold py-2 rounded-lg transition-colors">Upgrade Plan</button>
            </div>
        </div>

        <!-- User Profile -->
        <div class="border-t border-white/10 p-4">
            <div class="flex items-center justify-between cursor-pointer group px-2 py-1 rounded-lg hover:bg-white/5 transition">
                <div class="flex items-center space-x-3">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($currentUser->name) }}&background=bfdbfe&color=1e3a8a" alt="User" class="w-9 h-9 rounded-full ring-2 ring-transparent group-hover:ring-blue-500 transition-all">
                    <div>
                        <p class="text-sm font-semibold text-white">{{ $currentUser->name }}</p>
                        <p class="text-xs text-slate-400">{{ $currentUser->role === 'admin' ? 'Administrator' : 'Staff Member' }}</p>
                    </div>
                </div>
                <i class="ph ph-caret-down text-slate-400"></i>
            </div>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="mt-4 flex items-center space-x-2 text-slate-400 hover:text-white px-2 py-1 transition-colors text-sm font-medium">
                <i class="ph ph-sign-out text-lg"></i>
                <span>Logout</span>
            </a>
            <form id="logout-form" action="/logout" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#f8fafc]">
        
        <!-- Top Header -->
        <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 px-8 py-4 flex items-center justify-between z-10 sticky top-0">
            <div class="flex items-center">
                <button @click="sidebarOpen = !sidebarOpen" class="mr-4 text-slate-500 hover:text-slate-800 focus:outline-none md:hidden">
                    <i class="ph ph-list text-2xl"></i>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-slate-800 flex items-center">
                        Welcome back, {{ explode(' ', $currentUser->name ?? 'Rizky')[0] }}! <span class="ml-2 text-2xl">👋</span>
                    </h2>
                    <p class="text-sm text-slate-500 mt-0.5">Kelola dokumen, tanda tangan digital, dan sertifikat Anda dengan aman.</p>
                </div>
            </div>

            <div class="flex items-center space-x-6">
                <!-- Search -->
                <div class="relative hidden md:block w-72">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ph ph-magnifying-glass text-slate-400 text-lg"></i>
                    </div>
                    <input type="text" x-model="searchQuery" class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-full leading-5 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-all" placeholder="Cari dokumen, tipe, status...">
                </div>

                <!-- Notifications -->
                <button @click="showToast('Anda memiliki 3 dokumen baru yang memerlukan tanda tangan.', 'info')" class="relative text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="ph ph-bell text-2xl"></i>
                    <span class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-indigo-600 text-white text-[0.6rem] font-bold leading-4 text-center ring-2 ring-white">3</span>
                </button>

                <!-- Action Button -->
                <button @click="uploadModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-full font-medium flex items-center space-x-2 transition-all shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:-translate-y-0.5">
                    <i class="ph ph-plus text-lg"></i>
                    <span>New Document</span>
                </button>
            </div>
        </header>

        <!-- Dashboard Content Scrollable Area -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- PANEL: Dashboard -->
                <div x-show="activeTab === 'dashboard'" class="space-y-6" x-transition>

                <!-- Stats Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Documents -->
                    <div class="glass-card rounded-3xl p-6 hover-lift relative overflow-hidden group border border-white/60">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl group-hover:bg-indigo-500/10 transition-colors"></div>
                        <div class="flex items-start space-x-4 relative z-10">
                            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl group-hover:scale-110 transition-all duration-300 border border-indigo-100/40">
                                <i class="ph-bold ph-file-text text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-[0.75rem] font-semibold tracking-wider text-slate-400 uppercase font-outfit">Total Documents</p>
                                <h3 class="text-3xl font-extrabold text-slate-900 mt-1 tracking-tight font-outfit" x-text="stats.totalDocs">{{ $totalDocs }}</h3>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs relative z-10 justify-between">
                            <span class="flex items-center text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full font-bold">
                                <i class="ph-bold ph-arrow-up mr-0.5 font-outfit"></i>18%
                            </span>
                            <span class="text-slate-400 font-medium" x-text="getFilterSubtext()">dari bulan lalu</span>
                        </div>
                        <!-- Mini Chart (Simulated) -->
                        <div class="absolute bottom-0 left-0 right-0 h-10 opacity-30">
                            <svg viewBox="0 0 100 20" preserveAspectRatio="none" class="w-full h-full text-indigo-500/10 fill-current stroke-indigo-500/30 stroke-[0.75px]">
                                <path d="M0,20 L0,10 C10,12 20,5 30,8 C40,11 50,2 60,6 C70,10 80,4 90,7 C100,10 100,20 100,20 Z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Signed Documents -->
                    <div class="glass-card rounded-3xl p-6 hover-lift relative overflow-hidden group border border-white/60">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/10 transition-colors"></div>
                        <div class="flex items-start space-x-4 relative z-10">
                            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl group-hover:scale-110 transition-all duration-300 border border-emerald-100/40">
                                <i class="ph-bold ph-file-arrow-down text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-[0.75rem] font-semibold tracking-wider text-slate-400 uppercase font-outfit">Signed Documents</p>
                                <h3 class="text-3xl font-extrabold text-slate-900 mt-1 tracking-tight font-outfit" x-text="stats.signedDocs">{{ $signedDocs }}</h3>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs relative z-10 justify-between">
                            <span class="flex items-center text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full font-bold">
                                <i class="ph-bold ph-arrow-up mr-0.5 font-outfit"></i>22%
                            </span>
                            <span class="text-slate-400 font-medium" x-text="getFilterSubtext()">dari bulan lalu</span>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 h-10 opacity-30">
                            <svg viewBox="0 0 100 20" preserveAspectRatio="none" class="w-full h-full text-emerald-500/10 fill-current stroke-emerald-500/30 stroke-[0.75px]">
                                <path d="M0,20 L0,15 C10,12 20,18 30,14 C40,10 50,15 60,11 C70,7 80,12 90,9 C100,11 100,20 100,20 Z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Active Certificates -->
                    <div class="glass-card rounded-3xl p-6 hover-lift relative overflow-hidden group border border-white/60">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-violet-500/5 rounded-full blur-2xl group-hover:bg-violet-500/10 transition-colors"></div>
                        <div class="flex items-start space-x-4 relative z-10">
                            <div class="p-3 bg-violet-50 text-violet-600 rounded-2xl group-hover:scale-110 transition-all duration-300 border border-violet-100/40">
                                <i class="ph-bold ph-shield-check text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-[0.75rem] font-semibold tracking-wider text-slate-400 uppercase font-outfit">Active Certificates</p>
                                <h3 class="text-3xl font-extrabold text-slate-900 mt-1 tracking-tight font-outfit" x-text="stats.activeCerts">{{ $activeCerts }}</h3>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs relative z-10 justify-between">
                            <span class="flex items-center text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full font-bold">
                                <i class="ph-bold ph-arrow-up mr-0.5 font-outfit"></i>12%
                            </span>
                            <span class="text-slate-400 font-medium" x-text="getFilterSubtext()">dari bulan lalu</span>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 h-10 opacity-30">
                            <svg viewBox="0 0 100 20" preserveAspectRatio="none" class="w-full h-full text-violet-500/10 fill-current stroke-violet-500/30 stroke-[0.75px]">
                                <path d="M0,20 L0,12 C10,15 20,8 30,11 C40,14 50,9 60,13 C70,17 80,10 90,12 C100,14 100,20 100,20 Z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Expired Certificates -->
                    <div class="glass-card rounded-3xl p-6 hover-lift relative overflow-hidden group border border-white/60">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-rose-500/5 rounded-full blur-2xl group-hover:bg-rose-500/10 transition-colors"></div>
                        <div class="flex items-start space-x-4 relative z-10">
                            <div class="p-3 bg-rose-50 text-rose-500 rounded-2xl group-hover:scale-110 transition-all duration-300 border border-rose-100/40">
                                <i class="ph-bold ph-clipboard-text text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-[0.75rem] font-semibold tracking-wider text-slate-400 uppercase font-outfit">Expired Certificates</p>
                                <h3 class="text-3xl font-extrabold text-slate-900 mt-1 tracking-tight font-outfit" x-text="stats.expiredCerts">{{ $expiredCerts }}</h3>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs relative z-10 justify-between">
                            <span class="flex items-center text-red-600 bg-red-50 px-2 py-0.5 rounded-full font-bold">
                                <i class="ph-bold ph-arrow-down mr-0.5 font-outfit"></i>2
                            </span>
                            <span class="text-slate-400 font-medium" x-text="getFilterSubtext()">dari bulan lalu</span>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 h-10 opacity-30">
                            <svg viewBox="0 0 100 20" preserveAspectRatio="none" class="w-full h-full text-rose-500/10 fill-current stroke-rose-500/30 stroke-[0.75px]">
                                <path d="M0,20 L0,8 C10,10 20,5 30,8 C40,11 50,6 60,9 C70,12 80,7 90,10 C100,12 100,20 100,20 Z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Charts & Quick Actions Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Main Chart Area (Spans 2 cols) -->
                    <div class="lg:col-span-2 glass-card rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-bold text-slate-800">Documents Overview</h3>
                            <select x-model="timeFilter" @change="updateDashboard()" class="text-sm text-slate-500 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 hover:bg-slate-100 cursor-pointer">
                                <option value="today">Hari Ini</option>
                                <option value="week">Minggu Ini</option>
                                <option value="month">Bulan Ini</option>
                                <option value="year">Tahun Ini</option>
                            </select>
                        </div>
                        
                        <div class="flex flex-col md:flex-row gap-8 items-center h-64">
                            <!-- Donut Chart Canvas Container -->
                            <div class="relative w-48 h-48 flex-shrink-0">
                                <canvas id="donutChart"></canvas>
                                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-2xl font-bold text-slate-800" x-text="stats.totalDocs">{{ $totalDocs }}</span>
                                    <span class="text-xs text-slate-500 font-medium">Total</span>
                                </div>
                            </div>
                            
                            <!-- Legend & Line Chart -->
                            <div class="flex-1 w-full flex flex-col h-full justify-between">
                                <div class="flex flex-wrap gap-x-6 gap-y-2 mb-4">
                                    <div class="flex items-center text-sm">
                                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 mr-2 shadow-sm shadow-indigo-500/20"></span>
                                        <span class="text-slate-500 font-medium w-16">Signed</span>
                                        <span class="font-bold text-slate-800 font-outfit" x-text="stats.signedDocs + ' (' + Math.round((stats.signedDocs / (stats.totalDocs || 1)) * 100) + '%)'">{{ $signedDocs }} <span class="text-slate-400 font-normal text-xs">({{ round(($signedDocs / ($totalDocs ?: 1)) * 100) }}%)</span></span>
                                    </div>
                                    <div class="flex items-center text-sm">
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-2 shadow-sm shadow-amber-500/20"></span>
                                        <span class="text-slate-500 font-medium w-16">Pending</span>
                                        <span class="font-bold text-slate-800 font-outfit" x-text="stats.pendingDocs + ' (' + Math.round((stats.pendingDocs / (stats.totalDocs || 1)) * 100) + '%)'">{{ $pendingDocs }} <span class="text-slate-400 font-normal text-xs">({{ round(($pendingDocs / ($totalDocs ?: 1)) * 100) }}%)</span></span>
                                    </div>
                                    <div class="flex items-center text-sm">
                                        <span class="w-2.5 h-2.5 rounded-full bg-slate-400 mr-2 shadow-sm shadow-slate-400/20"></span>
                                        <span class="text-slate-500 font-medium w-16">Draft</span>
                                        <span class="font-bold text-slate-800 font-outfit" x-text="stats.draftDocs + ' (' + Math.round((stats.draftDocs / (stats.totalDocs || 1)) * 100) + '%)'">{{ $draftDocs }} <span class="text-slate-400 font-normal text-xs">({{ round(($draftDocs / ($totalDocs ?: 1)) * 100) }}%)</span></span>
                                    </div>
                                    <div class="flex items-center text-sm">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 mr-2 shadow-sm shadow-rose-500/20"></span>
                                        <span class="text-slate-500 font-medium w-16">Rejected</span>
                                        <span class="font-bold text-slate-800 font-outfit" x-text="stats.rejectedDocs + ' (' + Math.round((stats.rejectedDocs / (stats.totalDocs || 1)) * 100) + '%)'">{{ $rejectedDocs }} <span class="text-slate-400 font-normal text-xs">({{ round(($rejectedDocs / ($totalDocs ?: 1)) * 100) }}%)</span></span>
                                    </div>
                                </div>
                                <div class="h-32 w-full mt-auto">
                                    <p class="text-xs text-slate-400 mb-2 font-medium">Document Status</p>
                                    <canvas id="lineChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="glass-card rounded-2xl p-6">
                        <h3 class="font-bold text-slate-800 mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-2 gap-3 h-[calc(100%-2rem)]">
                            <button @click="uploadModal = true" class="bg-blue-50 hover:bg-blue-100 border border-blue-100 p-4 rounded-xl flex flex-col items-start justify-center transition-colors group text-left">
                                <i class="ph ph-upload-simple text-2xl text-blue-600 mb-2 group-hover:-translate-y-1 transition-transform"></i>
                                <span class="text-sm font-semibold text-slate-800">Upload Document</span>
                                <span class="text-[0.65rem] text-slate-500 mt-1">Unggah dokumen baru</span>
                            </button>
                            <button @click="signatureModal = true" class="bg-emerald-50 hover:bg-emerald-100 border border-emerald-100 p-4 rounded-xl flex flex-col items-start justify-center transition-colors group text-left">
                                <i class="ph ph-pen text-2xl text-emerald-600 mb-2 group-hover:-translate-y-1 transition-transform"></i>
                                <span class="text-sm font-semibold text-slate-800">Request Signature</span>
                                <span class="text-[0.65rem] text-slate-500 mt-1">Minta tanda tangan</span>
                            </button>
                            <button @click="activeTab = 'templates'; searchQuery = ''" class="bg-purple-50 hover:bg-purple-100 border border-purple-100 p-4 rounded-xl flex flex-col items-start justify-center transition-colors group text-left">
                                <i class="ph ph-file-dashed text-2xl text-purple-600 mb-2 group-hover:-translate-y-1 transition-transform"></i>
                                <span class="text-sm font-semibold text-slate-800">Create Template</span>
                                <span class="text-[0.65rem] text-slate-500 mt-1">Gunakan template dokumen</span>
                            </button>
                            <button @click="certModal = true" class="bg-orange-50 hover:bg-orange-100 border border-orange-100 p-4 rounded-xl flex flex-col items-start justify-center transition-colors group text-left">
                                <i class="ph ph-shield-check text-2xl text-orange-500 mb-2 group-hover:-translate-y-1 transition-transform"></i>
                                <span class="text-sm font-semibold text-slate-800">Create Certificate</span>
                                <span class="text-[0.65rem] text-slate-500 mt-1">Buat sertifikat baru</span>
                            </button>
                            <button @click="verifyModal = true" class="col-span-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 p-3 rounded-xl flex items-center space-x-3 transition-colors group">
                                <i class="ph ph-check-circle text-xl text-slate-600 group-hover:scale-110 transition-transform"></i>
                                <div class="text-left">
                                    <span class="text-sm font-semibold text-slate-800 block">Verify Document</span>
                                    <span class="text-[0.65rem] text-slate-500">Verifikasi keaslian dokumen</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tables Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Documents Table -->
                    <div class="lg:col-span-2 glass-card rounded-2xl p-6">
                        <div class="flex flex-wrap items-center justify-between gap-y-2 mb-4 border-b border-slate-100 pb-3.5">
                            <h3 class="font-bold text-slate-800 font-outfit">Recent Documents</h3>
                            <div class="flex items-center space-x-1 text-xs">
                                <button @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-500 hover:bg-slate-100 font-medium'" class="px-2.5 py-1 rounded-lg transition-all">All</button>
                                <button @click="filterStatus = 'signed'" :class="filterStatus === 'signed' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-500 hover:bg-slate-100 font-medium'" class="px-2.5 py-1 rounded-lg transition-all">Signed</button>
                                <button @click="filterStatus = 'pending'" :class="filterStatus === 'pending' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-500 hover:bg-slate-100 font-medium'" class="px-2.5 py-1 rounded-lg transition-all">Pending</button>
                                <button @click="filterStatus = 'draft'" :class="filterStatus === 'draft' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-500 hover:bg-slate-100 font-medium'" class="px-2.5 py-1 rounded-lg transition-all">Draft</button>
                                <button @click="filterStatus = 'rejected'" :class="filterStatus === 'rejected' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-500 hover:bg-slate-100 font-medium'" class="px-2.5 py-1 rounded-lg transition-all">Rejected</button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead class="text-xs text-slate-400 bg-slate-50/50 border-b border-slate-200/60 uppercase">
                                    <tr>
                                        <th class="px-4 py-3.5 font-semibold rounded-l-xl">Document Name</th>
                                        <th class="px-4 py-3.5 font-semibold">Status</th>
                                        <th class="px-4 py-3.5 font-semibold">Signers</th>
                                        <th class="px-4 py-3.5 font-semibold">Last Updated</th>
                                        <th class="px-4 py-3.5 font-semibold text-center rounded-r-xl">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($recentDocuments as $doc)
                                        @php
                                            $extension = strtoupper(pathinfo($doc->title, PATHINFO_EXTENSION)) ?: 'PDF';
                                            $badgeColor = match($extension) {
                                                'PDF' => 'bg-rose-50 text-rose-600 border border-rose-100',
                                                'DOCX' => 'bg-indigo-50 text-indigo-600 border border-indigo-100',
                                                'XLSX' => 'bg-emerald-50 text-emerald-600 border border-emerald-100',
                                                default => 'bg-slate-50 text-slate-600 border border-slate-100'
                                            };
                                            $statusBadge = match($doc->status) {
                                                'signed' => 'status-badge-signed',
                                                'pending' => 'status-badge-pending',
                                                'draft' => 'status-badge-draft',
                                                'rejected' => 'status-badge-rejected',
                                                default => 'status-badge-draft'
                                            };
                                        @endphp
                                        <tr class="custom-row" x-show="(filterStatus === 'all' || '{{ $doc->status }}' === filterStatus) && (searchQuery === '' || '{{ strtolower($doc->title) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($doc->type) }}'.includes(searchQuery.toLowerCase()))">
                                            <td class="px-4 py-4">
                                                <div class="flex items-center space-x-3">
                                                    <div class="{{ $badgeColor }} text-[9px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider">{{ $extension }}</div>
                                                    <div>
                                                        <p class="font-bold text-slate-800 text-[0.9rem]">{{ $doc->title }}</p>
                                                        <p class="text-[0.7rem] text-slate-400 font-medium tracking-wide">{{ $doc->type }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="{{ $statusBadge }} text-[0.7rem] font-bold px-2.5 py-1 rounded-full inline-flex items-center tracking-wide">
                                                    @if($doc->status === 'pending')
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 pulse-indicator"></span>
                                                    @endif
                                                    {{ ucfirst($doc->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex -space-x-1.5">
                                                    @foreach($doc->signatures->take(3) as $sig)
                                                        @if($sig->signer)
                                                            <img class="w-6.5 h-6.5 rounded-full border-2 border-white shadow-sm ring-1 ring-slate-100" src="https://ui-avatars.com/api/?name={{ urlencode($sig->signer->name) }}&background=random&color=fff&bold=true" title="{{ $sig->signer->name }}" alt="Signer">
                                                        @endif
                                                    @endforeach
                                                    @if($doc->signatures->count() > 3)
                                                        <div class="w-6.5 h-6.5 rounded-full border-2 border-white bg-slate-100 ring-1 ring-slate-100 flex items-center justify-center text-[9px] font-bold text-slate-500 shadow-sm">+{{ $doc->signatures->count() - 3 }}</div>
                                                    @endif
                                                    @if($doc->signatures->count() == 0)
                                                        <span class="text-xs text-slate-400">-</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-xs text-slate-500 font-medium">
                                                {{ $doc->updated_at->translatedFormat('d M Y') }}<br>
                                                <span class="text-[10px] text-slate-400">{{ $doc->updated_at->format('H:i') }} WIB</span>
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <div class="flex items-center justify-center space-x-1">
                                                    @if($doc->status === 'signed')
                                                        <button @click="verifyModal = true; verifyFileName = '{{ $doc->title }}'; simuleVerify();" class="text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100/80 p-2 rounded-lg transition-colors" title="Verifikasi Tanda Tangan"><i class="ph ph-shield-check text-base"></i></button>
                                                    @endif
                                                    <form action="/documents/{{ $doc->id }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100/80 p-2 rounded-lg transition-colors" title="Hapus Dokumen"><i class="ph ph-trash text-base"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Audit Trail (Condensed) below Recent Docs to match image layout -->
                        <div class="mt-8 flex items-center justify-between mb-4">
                            <h3 class="font-bold text-slate-800 font-outfit">Audit Trail</h3>
                            <a href="#" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100/80 px-3 py-1.5 rounded-lg transition-colors">View All Activities</a>
                        </div>
                        <div class="space-y-2">
                            @foreach($recentActivities as $activity)
                                @php
                                    $iconClass = match($activity->action) {
                                        'signed' => 'ph ph-pencil-simple text-emerald-600 bg-emerald-50 border border-emerald-100',
                                        'upload' => 'ph ph-upload-simple text-indigo-600 bg-indigo-50 border border-indigo-100',
                                        'update' => 'ph ph-pencil text-purple-600 bg-purple-50 border border-purple-100',
                                        default => 'ph ph-shield-check text-slate-600 bg-slate-50 border border-slate-100'
                                    };
                                    $actionLabel = match($activity->action) {
                                        'signed' => 'Signed',
                                        'upload' => 'Uploaded',
                                        'update' => 'Updated',
                                        default => 'System'
                                    };
                                    $badgeClass = match($activity->action) {
                                        'signed' => 'text-emerald-600 bg-emerald-50 border border-emerald-100/55',
                                        'upload' => 'text-indigo-600 bg-indigo-50 border border-indigo-100/55',
                                        'update' => 'text-purple-600 bg-purple-50 border border-purple-100/55',
                                        default => 'text-slate-600 bg-slate-50 border border-slate-100/55'
                                    };
                                @endphp
                                <div class="flex flex-col md:flex-row md:items-center justify-between p-3.5 bg-white/40 hover:bg-white/95 border border-slate-100 rounded-2xl transition-all duration-200 group">
                                    <div class="flex items-center space-x-3.5">
                                        <div class="p-2 rounded-xl {{ $iconClass }} transition-transform group-hover:scale-105"></div>
                                        <div>
                                            <p class="text-sm text-slate-700 leading-relaxed font-medium">{!! preg_replace('/^([^:]+):/', '<span class="font-bold text-slate-800">$1</span>', e($activity->description)) !!}</p>
                                            <div class="flex items-center space-x-2 mt-1 text-[0.7rem] text-slate-400">
                                                <span class="font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">{{ $activity->ip_address ?? '127.0.0.1' }}</span>
                                                <span>&bull;</span>
                                                <span>LEXA Secure Server</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between md:justify-end space-x-3.5 mt-2 md:mt-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                                        <span class="{{ $badgeClass }} text-[0.65rem] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider">{{ $actionLabel }}</span>
                                        <span class="text-xs text-slate-400 font-medium">{{ $activity->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Side Column (Certificate Overview & Recent Activity) -->
                    <div class="space-y-6">
                        <!-- Certificate Overview -->
                        <div class="glass-card rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-slate-800 font-outfit">Certificate Overview</h3>
                                <a href="#" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100/80 px-3 py-1.5 rounded-lg transition-colors">View All</a>
                            </div>
                            <div class="flex items-center space-x-6">
                                <div class="relative w-24 h-24 flex-shrink-0">
                                    <canvas id="certDonutChart"></canvas>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                        <span class="text-xl font-bold text-slate-800 font-outfit">{{ $activeCerts }}</span>
                                        <span class="text-[0.55rem] text-slate-400 font-semibold uppercase tracking-wider">Active</span>
                                    </div>
                                </div>
                                <div class="flex-1 space-y-2.5">
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center">
                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-2 shadow-sm shadow-emerald-500/20"></span>
                                            <span class="text-slate-600 font-medium">Valid</span>
                                        </div>
                                        <span class="font-bold text-slate-800 text-xs font-outfit">{{ $validCerts }} ({{ round(($validCerts / ($activeCerts ?: 1)) * 100) }}%)</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center">
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 mr-2 shadow-sm shadow-amber-400/20"></span>
                                            <span class="text-slate-600 font-medium">Expiring Soon</span>
                                        </div>
                                        <span class="font-bold text-slate-800 text-xs font-outfit">{{ $expiringSoonCerts }} ({{ round(($expiringSoonCerts / ($activeCerts ?: 1)) * 100) }}%)</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center">
                                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 mr-2 shadow-sm shadow-rose-500/20"></span>
                                            <span class="text-slate-600 font-medium">Expired</span>
                                        </div>
                                        <span class="font-bold text-slate-800 text-xs font-outfit">{{ $expiredCerts }} ({{ round(($expiredCerts / ($activeCerts ?: 1)) * 100) }}%)</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6 pt-4 border-t border-slate-100">
                                <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wider mb-2.5">Next Expiry</p>
                                <div class="flex items-center justify-between p-2.5 bg-slate-50 border border-slate-100 rounded-xl">
                                    @if($nextExpiry)
                                        <div class="flex items-center space-x-2.5 min-w-0">
                                            <div class="p-1.5 bg-amber-50 border border-amber-100 text-amber-600 rounded-lg">
                                                <i class="ph ph-certificate text-lg"></i>
                                            </div>
                                            <span class="text-xs font-bold text-slate-700 truncate" title="{{ $nextExpiry->name }}">{{ $nextExpiry->name }}</span>
                                        </div>
                                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-100/60 px-2 py-1 rounded-md shrink-0 ml-2 shadow-sm">{{ \Carbon\Carbon::parse($nextExpiry->valid_until)->translatedFormat('d M Y') }}</span>
                                    @else
                                        <div class="flex items-center space-x-2">
                                            <i class="ph ph-certificate text-slate-400 text-lg"></i>
                                            <span class="text-xs font-medium text-slate-400">Tidak ada sertifikat terdekat</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-1 rounded">-</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity List -->
                        <div class="glass-card rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-slate-800 font-outfit">Recent Activity</h3>
                                <a href="#" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100/80 px-3 py-1.5 rounded-lg transition-colors">View All</a>
                            </div>
                            
                            <div class="space-y-4 relative before:absolute before:inset-y-0 before:left-2.5 before:w-0.5 before:bg-slate-100">
                                @foreach($recentActivities as $activity)
                                    @php
                                        $parts = explode(':', $activity->description, 2);
                                        $title = trim($parts[0]);
                                        $subtitle = isset($parts[1]) ? trim($parts[1]) : '';
                                        
                                        $dotColor = match($activity->action) {
                                            'signed' => 'bg-emerald-500 ring-4 ring-emerald-500/10',
                                            'expired' => 'bg-rose-500 ring-4 ring-rose-500/10',
                                            'upload' => 'bg-indigo-500 ring-4 ring-indigo-500/10',
                                            'update' => 'bg-purple-500 ring-4 ring-purple-500/10',
                                            default => 'bg-slate-500 ring-4 ring-slate-500/10'
                                        };
                                    @endphp
                                    <!-- Activity Item -->
                                    <div class="relative flex items-start space-x-3.5 pl-0.5">
                                        <div class="w-4 h-4 rounded-full {{ $dotColor }} border-2 border-white z-10 shrink-0 mt-1"></div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs font-bold text-slate-700 leading-snug">{{ $title }}</div>
                                            @if($subtitle)
                                                <div class="text-[11px] {{ $activity->action == 'signed' ? 'text-indigo-600 cursor-pointer hover:underline font-medium' : 'text-slate-400 font-medium' }} mt-0.5">{{ $subtitle }}</div>
                                            @endif
                                            <div class="text-[10px] text-slate-400 mt-1 flex items-center space-x-1.5">
                                                <span>{{ $activity->created_at->diffForHumans() }}</span>
                                                <span>&bull;</span>
                                                <span class="font-mono text-[9px] bg-slate-50 px-1 rounded">{{ $activity->ip_address ?? '127.0.0.1' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <!-- PANEL: Documents -->
                <div x-show="activeTab === 'documents'" class="space-y-6" x-transition style="display: none;">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-y-4">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 font-outfit">All Documents</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Kelola dan telusuri seluruh arsip dokumen digital Anda.</p>
                        </div>
                        <button @click="uploadModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-full font-semibold flex items-center space-x-2 transition-all shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/35">
                            <i class="ph ph-plus text-lg"></i>
                            <span>Upload Document</span>
                        </button>
                    </div>

                    <!-- Filter & Search Toolbar -->
                    <div class="glass-card rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                        <!-- Filters -->
                        <div class="flex flex-wrap gap-1.5 text-xs">
                            <button @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-500 hover:bg-slate-100 font-medium'" class="px-3.5 py-2 rounded-xl transition-all">All Documents</button>
                            <button @click="filterStatus = 'signed'" :class="filterStatus === 'signed' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-500 hover:bg-slate-100 font-medium'" class="px-3.5 py-2 rounded-xl transition-all">Signed</button>
                            <button @click="filterStatus = 'pending'" :class="filterStatus === 'pending' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-500 hover:bg-slate-100 font-medium'" class="px-3.5 py-2 rounded-xl transition-all">Pending</button>
                            <button @click="filterStatus = 'draft'" :class="filterStatus === 'draft' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-500 hover:bg-slate-100 font-medium'" class="px-3.5 py-2 rounded-xl transition-all">Draft</button>
                            <button @click="filterStatus = 'rejected'" :class="filterStatus === 'rejected' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-500 hover:bg-slate-100 font-medium'" class="px-3.5 py-2 rounded-xl transition-all">Rejected</button>
                        </div>
                        <!-- Inner search bar -->
                        <div class="relative w-full md:w-72">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="ph ph-magnifying-glass text-slate-400 text-lg"></i>
                            </div>
                            <input type="text" x-model="searchQuery" class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl leading-5 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 sm:text-xs transition-all" placeholder="Cari judul dokumen...">
                        </div>
                    </div>

                    <!-- Documents Grid/Table -->
                    <div class="glass-card rounded-2xl p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead class="text-xs text-slate-400 bg-slate-50/50 border-b border-slate-200/60 uppercase">
                                    <tr>
                                        <th class="px-4 py-3.5 font-semibold rounded-l-xl">Document Name</th>
                                        <th class="px-4 py-3.5 font-semibold">Status</th>
                                        <th class="px-4 py-3.5 font-semibold">Uploaded By</th>
                                        <th class="px-4 py-3.5 font-semibold">Signers</th>
                                        <th class="px-4 py-3.5 font-semibold">Last Updated</th>
                                        <th class="px-4 py-3.5 font-semibold text-center rounded-r-xl">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($allDocuments as $doc)
                                        @php
                                            $extension = strtoupper(pathinfo($doc->title, PATHINFO_EXTENSION)) ?: 'PDF';
                                            $badgeColor = match($extension) {
                                                'PDF' => 'bg-rose-50 text-rose-600 border border-rose-100',
                                                'DOCX' => 'bg-indigo-50 text-indigo-600 border border-indigo-100',
                                                'XLSX' => 'bg-emerald-50 text-emerald-600 border border-emerald-100',
                                                default => 'bg-slate-50 text-slate-600 border border-slate-100'
                                            };
                                            $statusBadge = match($doc->status) {
                                                'signed' => 'status-badge-signed',
                                                'pending' => 'status-badge-pending',
                                                'draft' => 'status-badge-draft',
                                                'rejected' => 'status-badge-rejected',
                                                default => 'status-badge-draft'
                                            };
                                        @endphp
                                        <tr class="custom-row" x-show="(filterStatus === 'all' || '{{ $doc->status }}' === filterStatus) && (searchQuery === '' || '{{ strtolower($doc->title) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($doc->type) }}'.includes(searchQuery.toLowerCase()))">
                                            <td class="px-4 py-4">
                                                <div class="flex items-center space-x-3">
                                                    <div class="{{ $badgeColor }} text-[9px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider">{{ $extension }}</div>
                                                    <div>
                                                        <p class="font-bold text-slate-800 text-[0.9rem]">{{ $doc->title }}</p>
                                                        <p class="text-[0.7rem] text-slate-400 font-medium tracking-wide">{{ $doc->type }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="{{ $statusBadge }} text-[0.7rem] font-bold px-2.5 py-1 rounded-full inline-flex items-center tracking-wide">
                                                    @if($doc->status === 'pending')
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 pulse-indicator"></span>
                                                    @endif
                                                    {{ ucfirst($doc->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-xs text-slate-600 font-semibold">
                                                {{ $doc->uploadedBy->name ?? 'System' }}
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex -space-x-1.5">
                                                    @foreach($doc->signatures as $sig)
                                                        @if($sig->signer)
                                                            <img class="w-6.5 h-6.5 rounded-full border-2 border-white shadow-sm ring-1 ring-slate-100" src="https://ui-avatars.com/api/?name={{ urlencode($sig->signer->name) }}&background=random&color=fff&bold=true" title="{{ $sig->signer->name }}" alt="Signer">
                                                        @endif
                                                    @endforeach
                                                    @if($doc->signatures->count() == 0)
                                                        <span class="text-xs text-slate-400">-</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-xs text-slate-500 font-medium">
                                                {{ $doc->updated_at->translatedFormat('d M Y') }}<br>
                                                <span class="text-[10px] text-slate-400">{{ $doc->updated_at->format('H:i') }} WIB</span>
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <div class="flex items-center justify-center space-x-1">
                                                    @if($doc->status === 'signed')
                                                        <button @click="verifyModal = true; verifyFileName = '{{ $doc->title }}'; simuleVerify();" class="text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100/80 p-2 rounded-lg transition-colors" title="Verifikasi Tanda Tangan"><i class="ph ph-shield-check text-base"></i></button>
                                                    @endif
                                                    <form action="/documents/{{ $doc->id }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100/80 p-2 rounded-lg transition-colors" title="Hapus Dokumen"><i class="ph ph-trash text-base"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PANEL: Signatures -->
                <div x-show="activeTab === 'signatures'" class="space-y-6" x-transition style="display: none;">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-y-4">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 font-outfit">Signature Requests</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Kelola status penandatanganan dokumen secara kriptografis.</p>
                        </div>
                        <button @click="signatureModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-full font-semibold flex items-center space-x-2 transition-all shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/35">
                            <i class="ph ph-pen text-lg"></i>
                            <span>Request Signature</span>
                        </button>
                    </div>

                    <!-- Toolbar -->
                    <div class="glass-card rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex flex-wrap gap-1.5 text-xs">
                            <span class="text-xs text-slate-500 font-bold px-3 py-2">Daftar Otorisasi Tanda Tangan</span>
                        </div>
                        <div class="relative w-full md:w-72">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="ph ph-magnifying-glass text-slate-400 text-lg"></i>
                            </div>
                            <input type="text" x-model="searchQuery" class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl leading-5 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 sm:text-xs transition-all" placeholder="Cari penandatangan...">
                        </div>
                    </div>

                    <!-- Signatures list -->
                    <div class="glass-card rounded-2xl p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead class="text-xs text-slate-400 bg-slate-50/50 border-b border-slate-200/60 uppercase">
                                    <tr>
                                        <th class="px-4 py-3.5 font-semibold rounded-l-xl">Document Name</th>
                                        <th class="px-4 py-3.5 font-semibold">Signer</th>
                                        <th class="px-4 py-3.5 font-semibold">Signature Status</th>
                                        <th class="px-4 py-3.5 font-semibold">Signed Date</th>
                                        <th class="px-4 py-3.5 font-semibold">IP Address</th>
                                        <th class="px-4 py-3.5 font-semibold text-center rounded-r-xl">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($allSignatures as $sig)
                                        @php
                                            $isSigned = !is_null($sig->signed_at);
                                        @endphp
                                        <tr class="custom-row" x-show="searchQuery === '' || '{{ strtolower($sig->signer->name ?? '') }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($sig->document->title ?? '') }}'.includes(searchQuery.toLowerCase())">
                                            <td class="px-4 py-4">
                                                <span class="font-bold text-slate-800 text-[0.9rem]">{{ $sig->document->title ?? '-' }}</span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center space-x-2.5">
                                                    <img class="w-7 h-7 rounded-full border-2 border-white ring-1 ring-slate-100" src="https://ui-avatars.com/api/?name={{ urlencode($sig->signer->name ?? 'User') }}&background=random&color=fff&bold=true" alt="Signer">
                                                    <div>
                                                        <p class="font-semibold text-slate-800 text-xs">{{ $sig->signer->name ?? 'User' }}</p>
                                                        <p class="text-[0.65rem] text-slate-400">{{ $sig->signer->email ?? 'email' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                @if($isSigned)
                                                    <span class="status-badge-signed text-[0.7rem] font-bold px-2.5 py-1 rounded-full inline-flex items-center tracking-wide">
                                                        Signed
                                                    </span>
                                                @else
                                                    <span class="status-badge-pending text-[0.7rem] font-bold px-2.5 py-1 rounded-full inline-flex items-center tracking-wide">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 pulse-indicator"></span>
                                                        Pending
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-xs text-slate-500 font-medium">
                                                @if($isSigned)
                                                    {{ \Carbon\Carbon::parse($sig->signed_at)->translatedFormat('d M Y') }}<br>
                                                    <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($sig->signed_at)->format('H:i') }} WIB</span>
                                                @else
                                                    <span class="text-slate-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-xs text-slate-500 font-mono font-semibold">
                                                 {{ $sig->ip_address ?? '-' }}
                                             </td>
                                             <td class="px-4 py-4 text-center">
                                                 @if(!$isSigned)
                                                     <form action="/signatures/{{ $sig->id }}/sign" method="POST" class="inline">
                                                         @csrf
                                                         <button type="submit" class="text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100/80 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors">
                                                             Sign Now
                                                         </button>
                                                     </form>
                                                 @else
                                                     <span class="text-slate-400 text-xs">-</span>
                                                 @endif
                                             </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PANEL: Certificates -->
                <div x-show="activeTab === 'certificates'" class="space-y-6" x-transition style="display: none;">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-y-4">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 font-outfit">Digital Certificates</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Kelola kunci publik, SSL, dan sertifikat elektronik tanda tangan digital Anda.</p>
                        </div>
                        <button @click="certModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-full font-semibold flex items-center space-x-2 transition-all shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/35">
                            <i class="ph ph-shield-check text-lg"></i>
                            <span>Create Certificate</span>
                        </button>
                    </div>

                    <!-- Stats overview row inside certificates tab -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="glass-card rounded-2xl p-5 border border-white/60">
                            <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wider font-outfit">Valid Certificates</p>
                            <h4 class="text-2xl font-extrabold text-slate-800 mt-1 font-outfit">{{ $validCerts }}</h4>
                        </div>
                        <div class="glass-card rounded-2xl p-5 border border-white/60">
                            <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wider font-outfit">Expiring Soon</p>
                            <h4 class="text-2xl font-extrabold text-amber-600 mt-1 font-outfit">{{ $expiringSoonCerts }}</h4>
                        </div>
                        <div class="glass-card rounded-2xl p-5 border border-white/60">
                            <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wider font-outfit">Expired Certificates</p>
                            <h4 class="text-2xl font-extrabold text-rose-600 mt-1 font-outfit">{{ $expiredCerts }}</h4>
                        </div>
                    </div>

                    <!-- Certificates Table -->
                    <div class="glass-card rounded-2xl p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead class="text-xs text-slate-400 bg-slate-50/50 border-b border-slate-200/60 uppercase">
                                    <tr>
                                        <th class="px-4 py-3.5 font-semibold rounded-l-xl">Certificate Name</th>
                                        <th class="px-4 py-3.5 font-semibold">Holder</th>
                                        <th class="px-4 py-3.5 font-semibold">Status</th>
                                        <th class="px-4 py-3.5 font-semibold">Issued At</th>
                                        <th class="px-4 py-3.5 font-semibold">Valid Until</th>
                                        <th class="px-4 py-3.5 font-semibold text-center rounded-r-xl">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($allCertificates as $cert)
                                        @php
                                            $certBadge = match($cert->status) {
                                                'valid' => 'status-badge-signed',
                                                'expiring_soon' => 'status-badge-pending',
                                                'expired' => 'status-badge-rejected',
                                                default => 'status-badge-draft'
                                            };
                                            $statusLabel = match($cert->status) {
                                                'valid' => 'Valid',
                                                'expiring_soon' => 'Expiring Soon',
                                                'expired' => 'Expired',
                                                default => 'Draft'
                                            };
                                        @endphp
                                        <tr class="custom-row" x-show="searchQuery === '' || '{{ strtolower($cert->name) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($cert->holder) }}'.includes(searchQuery.toLowerCase())">
                                            <td class="px-4 py-4">
                                                <div class="flex items-center space-x-3">
                                                    <div class="p-1.5 bg-indigo-50 border border-indigo-100 text-indigo-600 rounded-lg">
                                                        <i class="ph ph-certificate text-lg"></i>
                                                    </div>
                                                    <span class="font-bold text-slate-800 text-[0.85rem] leading-relaxed">{{ $cert->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-xs text-slate-600 font-semibold">
                                                {{ $cert->holder }}
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="{{ $certBadge }} text-[0.7rem] font-bold px-2.5 py-1 rounded-full inline-flex items-center tracking-wide">
                                                    @if($cert->status === 'expiring_soon')
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 pulse-indicator"></span>
                                                    @endif
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-xs text-slate-500 font-medium">
                                                {{ \Carbon\Carbon::parse($cert->issued_at)->translatedFormat('d M Y') }}
                                            </td>
                                            <td class="px-4 py-4 text-xs text-slate-500 font-medium">
                                                {{ \Carbon\Carbon::parse($cert->valid_until)->translatedFormat('d M Y') }}
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <form action="/certificates/{{ $cert->id }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus sertifikat ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100/80 p-2 rounded-lg transition-colors" title="Hapus Sertifikat"><i class="ph ph-trash text-base"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PANEL: Templates -->
                <div x-show="activeTab === 'templates'" class="space-y-6" x-transition style="display: none;">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 font-outfit font-outfit">Document Templates</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Sederhanakan alur kerja dengan template dokumen pra-desain.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="glass-card rounded-3xl p-6 border border-white/60 hover-lift relative overflow-hidden flex flex-col justify-between">
                            <div>
                                <i class="ph ph-file-text text-3xl text-indigo-500 mb-3"></i>
                                <h4 class="text-base font-bold text-slate-800 font-outfit">Template NDA / Perjanjian Kerahasiaan</h4>
                                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Template standar untuk kemitraan bisnis dan perpanjangan vendor.</p>
                            </div>
                            <form action="/documents/use-template" method="POST" class="mt-4">
                                @csrf
                                <input type="hidden" name="template_name" value="NDA Perjanjian Kerahasiaan">
                                <button type="submit" class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-semibold py-2 rounded-xl transition-colors">Gunakan Template</button>
                            </form>
                        </div>
                        <div class="glass-card rounded-3xl p-6 border border-white/60 hover-lift relative overflow-hidden flex flex-col justify-between">
                            <div>
                                <i class="ph ph-briefcase text-3xl text-indigo-500 mb-3"></i>
                                <h4 class="text-base font-bold text-slate-800 font-outfit">Template PKS Layanan IT</h4>
                                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Template hukum perjanjian kerja sama penyediaan jasa komputasi awan dan support.</p>
                            </div>
                            <form action="/documents/use-template" method="POST" class="mt-4">
                                @csrf
                                <input type="hidden" name="template_name" value="PKS Layanan IT">
                                <button type="submit" class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-semibold py-2 rounded-xl transition-colors">Gunakan Template</button>
                            </form>
                        </div>
                        <div class="glass-card rounded-3xl p-6 border border-white/60 hover-lift relative overflow-hidden flex flex-col justify-between">
                            <div>
                                <i class="ph ph-shield-check text-3xl text-indigo-500 mb-3"></i>
                                <h4 class="text-base font-bold text-slate-800 font-outfit">Template SOP Internal</h4>
                                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Standar prosedur operasional kepatuhan data ISO27001 dan audit privasi.</p>
                            </div>
                            <form action="/documents/use-template" method="POST" class="mt-4">
                                @csrf
                                <input type="hidden" name="template_name" value="SOP Internal">
                                <button type="submit" class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-semibold py-2 rounded-xl transition-colors">Gunakan Template</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- PANEL: Users -->
                <div x-show="activeTab === 'users'" class="space-y-6" x-transition style="display: none;">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 font-outfit">Users & Roles</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Kelola keanggotaan tim, otorisasi tanda tangan, dan hak akses.</p>
                    </div>

                    <div class="glass-card rounded-2xl p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead class="text-xs text-slate-400 bg-slate-50/50 border-b border-slate-200/60 uppercase">
                                    <tr>
                                        <th class="px-4 py-3.5 font-semibold rounded-l-xl">User Profile</th>
                                        <th class="px-4 py-3.5 font-semibold">Email Address</th>
                                        <th class="px-4 py-3.5 font-semibold">System Role</th>
                                        <th class="px-4 py-3.5 font-semibold">Sign Authority</th>
                                        <th class="px-4 py-3.5 rounded-r-xl">Joined Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($allUsers as $user)
                                        <tr class="custom-row">
                                            <td class="px-4 py-4">
                                                <div class="flex items-center space-x-2.5">
                                                    <img class="w-8 h-8 rounded-full border-2 border-white ring-1 ring-slate-100 shadow-sm" src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=bfdbfe&color=1e3a8a&bold=true" alt="User">
                                                    <span class="font-bold text-slate-800 text-sm leading-normal">{{ $user->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-xs text-slate-500 font-semibold font-mono">
                                                {{ $user->email }}
                                            </td>
                                            <td class="px-4 py-4 text-xs">
                                                <span class="bg-indigo-50 border border-indigo-100/60 text-indigo-700 font-bold px-2.5 py-1 rounded-md">
                                                    {{ $user->name === 'Rizky Pratama' ? 'Owner / Administrator' : 'Staff Member' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-xs text-slate-500 font-medium">
                                                <span class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 font-bold">BSrE Verified</span>
                                            </td>
                                            <td class="px-4 py-4 text-xs text-slate-400 font-medium">
                                                {{ $user->created_at->translatedFormat('d M Y') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PANEL: Teams -->
                <div x-show="activeTab === 'teams'" class="space-y-6" x-transition style="display: none;">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 font-outfit">Teams & Collaboration</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Kelola tim kolaborasi, bagikan dokumen, dan atur anggota tim secara real-time.</p>
                        </div>
                        <button @click="createTeamModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-full font-semibold flex items-center space-x-2 transition-all shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/35">
                            <i class="ph ph-plus text-lg"></i>
                            <span>Create Team</span>
                        </button>
                    </div>

                    <!-- Teams Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($allTeams as $team)
                            <div class="glass-card rounded-2xl p-6 hover-lift relative overflow-hidden border border-white/60 flex flex-col justify-between h-full">
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl border border-indigo-100/40">
                                                <i class="ph-bold ph-users-three text-xl"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-slate-800 text-base leading-snug">{{ $team->name }}</h4>
                                                <p class="text-xs text-slate-400 font-medium font-outfit">Dibuat oleh {{ $team->creator->name ?? 'System' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-sm text-slate-500 line-clamp-2 leading-relaxed mb-6">{{ $team->description ?: 'Tidak ada deskripsi.' }}</p>
                                </div>
                                
                                <div class="space-y-4 pt-4 border-t border-slate-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-400 font-semibold font-outfit uppercase tracking-wider">Members ({{ $team->members->count() }})</span>
                                        <div class="flex -space-x-1.5">
                                            @foreach($team->members->take(5) as $m)
                                                <img class="w-6.5 h-6.5 rounded-full border-2 border-white shadow-sm ring-1 ring-slate-150" src="https://ui-avatars.com/api/?name={{ urlencode($m->name) }}&background=bfdbfe&color=1e3a8a&bold=true" title="{{ $m->name }} ({{ $m->pivot->role }})" alt="Member">
                                            @endforeach
                                            @if($team->members->count() > 5)
                                                <div class="w-6.5 h-6.5 rounded-full border-2 border-white bg-slate-100 ring-1 ring-slate-100 flex items-center justify-center text-[9px] font-bold text-slate-500 shadow-sm">+{{ $team->members->count() - 5 }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between space-x-2 pt-2">
                                        <button @click="selectedTeam = allTeamsList.find(t => t.id === {{ $team->id }}); manageTeamMembersModal = true;" class="flex-1 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold py-2 px-3 rounded-xl border border-slate-200 text-xs transition-colors flex items-center justify-center space-x-1">
                                            <i class="ph ph-user-list text-sm"></i>
                                            <span>Manage Members</span>
                                        </button>
                                        <form action="/teams/{{ $team->id }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tim ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold p-2.5 rounded-xl border border-rose-100 text-xs transition-colors" title="Delete Team">
                                                <i class="ph ph-trash text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        
                        @if($allTeams->isEmpty())
                            <div class="col-span-2 glass-card rounded-2xl p-12 text-center border border-white/60">
                                <i class="ph ph-users-three text-5xl text-slate-300 mb-3"></i>
                                <h3 class="font-bold text-slate-700 text-lg">Belum Ada Tim</h3>
                                <p class="text-sm text-slate-400 mt-1 mb-4">Buat tim kolaborasi pertama untuk membagikan akses dokumen dan kelola bersama.</p>
                                <button @click="createTeamModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-full font-semibold inline-flex items-center space-x-1.5 transition-all shadow-md shadow-indigo-500/20">
                                    <i class="ph ph-plus text-sm"></i>
                                    <span>Create Team</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- PANEL: Audit Trail -->
                <div x-show="activeTab === 'audit'" class="space-y-6" x-transition style="display: none;">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 font-outfit">Audit Trail & Logs</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Catatan riwayat aktivitas operasional sistem terenkripsi dan tidak dapat diubah (immutable).</p>
                    </div>

                    <div class="glass-card rounded-2xl p-6">
                        <div class="space-y-3">
                            @foreach($allActivities as $activity)
                                @php
                                    $iconClass = match($activity->action) {
                                        'signed' => 'ph ph-pencil-simple text-emerald-600 bg-emerald-50 border border-emerald-100',
                                        'upload' => 'ph ph-upload-simple text-indigo-600 bg-indigo-50 border border-indigo-100',
                                        'update' => 'ph ph-pencil text-purple-600 bg-purple-50 border border-purple-100',
                                        default => 'ph ph-shield-check text-slate-600 bg-slate-50 border border-slate-100'
                                    };
                                    $actionLabel = match($activity->action) {
                                        'signed' => 'Signed',
                                        'upload' => 'Uploaded',
                                        'update' => 'Updated',
                                        default => 'System'
                                    };
                                    $badgeClass = match($activity->action) {
                                        'signed' => 'text-emerald-600 bg-emerald-50 border border-emerald-100/55',
                                        'upload' => 'text-indigo-600 bg-indigo-50 border border-indigo-100/55',
                                        'update' => 'text-purple-600 bg-purple-50 border border-purple-100/55',
                                        default => 'text-slate-600 bg-slate-50 border border-slate-100/55'
                                    };
                                @endphp
                                <div class="flex flex-col md:flex-row md:items-center justify-between p-3.5 bg-white/40 hover:bg-white/95 border border-slate-100 rounded-2xl transition-all duration-200 group">
                                    <div class="flex items-center space-x-3.5">
                                        <div class="p-2 rounded-xl {{ $iconClass }} transition-transform group-hover:scale-105"></div>
                                        <div>
                                            <p class="text-sm text-slate-700 leading-relaxed font-medium">{!! preg_replace('/^([^:]+):/', '<span class="font-bold text-slate-800">$1</span>', e($activity->description)) !!}</p>
                                            <div class="flex items-center space-x-2 mt-1 text-[0.7rem] text-slate-400">
                                                <span class="font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">{{ $activity->ip_address ?? '127.0.0.1' }}</span>
                                                <span>&bull;</span>
                                                <span>LEXA Secure Server SHA-256</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between md:justify-end space-x-3.5 mt-2 md:mt-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                                        <span class="{{ $badgeClass }} text-[0.65rem] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider">{{ $actionLabel }}</span>
                                        <span class="text-xs text-slate-400 font-medium">{{ $activity->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- PANEL: Integrations -->
                <div x-show="activeTab === 'integrations'" class="space-y-6" x-transition style="display: none;">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 font-outfit">API & Integrations</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Integrasikan platform digital signature LEXA ke dalam aplikasi Anda menggunakan REST API.</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left 2 Cols: API Keys Management -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="glass-card rounded-2xl p-6">
                                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                                    <h3 class="font-bold text-slate-800 font-outfit text-base">API Keys</h3>
                                    <!-- Simple Form to Trigger Modal -->
                                    <form @submit.prevent="apiKeyModal = true; generatedKey = ''" class="inline">
                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs font-semibold flex items-center space-x-1 transition-all shadow-md shadow-indigo-500/20">
                                            <i class="ph ph-plus"></i>
                                            <span>Create API Key</span>
                                        </button>
                                    </form>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left border-collapse">
                                        <thead class="text-xs text-slate-400 bg-slate-50/50 border-b border-slate-200/60 uppercase">
                                            <tr>
                                                <th class="px-4 py-3 font-semibold rounded-l-xl">Name</th>
                                                <th class="px-4 py-3 font-semibold">API Key</th>
                                                <th class="px-4 py-3 font-semibold">Status</th>
                                                <th class="px-4 py-3 font-semibold">Last Used</th>
                                                <th class="px-4 py-3 font-semibold text-center rounded-r-xl">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($allApiKeys as $key)
                                                <tr class="custom-row">
                                                    <td class="px-4 py-3.5 font-bold text-slate-800 text-xs">
                                                        {{ $key->name }}
                                                    </td>
                                                    <td class="px-4 py-3.5 text-xs font-mono text-slate-500">
                                                        <code>{{ substr($key->key, 0, 12) }}••••••••{{ substr($key->key, -4) }}</code>
                                                    </td>
                                                    <td class="px-4 py-3.5">
                                                        <form action="/api-keys/{{ $key->id }}/toggle" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center">
                                                                <span class="relative inline-flex items-center cursor-pointer">
                                                                    <!-- Custom Toggle Styling -->
                                                                    <span class="w-8 h-4 rounded-full transition-colors duration-200 ease-in-out {{ $key->status === 'active' ? 'bg-indigo-600' : 'bg-slate-200' }}"></span>
                                                                    <span class="absolute left-0.5 top-0.5 bg-white w-3 h-3 rounded-full transition-transform duration-200 ease-in-out transform {{ $key->status === 'active' ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                                                </span>
                                                                <span class="ml-2 text-xs font-bold font-outfit uppercase tracking-wider {{ $key->status === 'active' ? 'text-indigo-600' : 'text-slate-400' }}">
                                                                    {{ ucfirst($key->status) }}
                                                                </span>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td class="px-4 py-3.5 text-xs text-slate-400 font-medium">
                                                        {{ $key->last_used_at ? $key->last_used_at->translatedFormat('d M Y, H:i') . ' WIB' : 'Never' }}
                                                    </td>
                                                    <td class="px-4 py-3.5 text-center">
                                                        <form action="/api-keys/{{ $key->id }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus API Key ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 p-2 rounded-lg transition-colors" title="Delete API Key">
                                                                <i class="ph ph-trash text-sm"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            @if($allApiKeys->isEmpty())
                                                <tr>
                                                    <td colspan="5" class="px-4 py-8 text-center text-xs text-slate-400">
                                                        Belum ada API Key aktif. Silakan buat API Key baru untuk memulai integrasi.
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Right Col: API Specs Documentation -->
                        <div class="glass-card rounded-2xl p-6 flex flex-col h-full bg-slate-900 border border-slate-800 text-slate-100">
                            <div class="flex items-center space-x-2 mb-4">
                                <i class="ph-bold ph-code text-indigo-400 text-xl"></i>
                                <h3 class="font-bold font-outfit text-base">API Documentation</h3>
                            </div>
                            
                            <!-- Language Switcher Tabs -->
                            <div class="flex space-x-1 bg-slate-800 p-1 rounded-xl mb-4 text-xs font-semibold">
                                <button @click="apiDocTab = 'curl'" :class="apiDocTab === 'curl' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'" class="flex-1 py-1.5 rounded-lg transition-all">cURL</button>
                                <button @click="apiDocTab = 'node'" :class="apiDocTab === 'node' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'" class="flex-1 py-1.5 rounded-lg transition-all">Node.js</button>
                                <button @click="apiDocTab = 'python'" :class="apiDocTab === 'python' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'" class="flex-1 py-1.5 rounded-lg transition-all">Python</button>
                            </div>

                            <!-- Code Blocks -->
                            <div class="flex-1 overflow-y-auto space-y-4 pr-1">
                                <div x-show="apiDocTab === 'curl'" class="space-y-3" x-transition>
                                    <p class="text-xs text-slate-400 font-medium leading-relaxed">Panggil API untuk memverifikasi dokumen secara terprogram:</p>
                                    <pre class="bg-black/50 p-3 rounded-xl text-[10px] font-mono overflow-x-auto text-emerald-400 leading-normal border border-slate-800">curl -X POST http://localhost:8000/api/v1/verify \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"file_name": "Kontrak.pdf"}'</pre>
                                </div>
                                
                                <div x-show="apiDocTab === 'node'" class="space-y-3" x-transition style="display: none;">
                                    <p class="text-xs text-slate-400 font-medium leading-relaxed">Contoh fetch request di Node.js:</p>
                                    <pre class="bg-black/50 p-3 rounded-xl text-[10px] font-mono overflow-x-auto text-emerald-400 leading-normal border border-slate-800">const axios = require('axios');

axios.post('http://localhost:8000/api/v1/verify', {
  file_name: 'Kontrak.pdf'
}, {
  headers: {
    'Authorization': 'Bearer YOUR_API_KEY'
  }
})
.then(res => console.log(res.data))
.catch(err => console.error(err));</pre>
                                </div>

                                <div x-show="apiDocTab === 'python'" class="space-y-3" x-transition style="display: none;">
                                    <p class="text-xs text-slate-400 font-medium leading-relaxed">Contoh request menggunakan library Python requests:</p>
                                    <pre class="bg-black/50 p-3 rounded-xl text-[10px] font-mono overflow-x-auto text-emerald-400 leading-normal border border-slate-800">import requests

url = "http://localhost:8000/api/v1/verify"
headers = {
    "Authorization": "Bearer YOUR_API_KEY"
}
payload = {
    "file_name": "Kontrak.pdf"
}

response = requests.post(url, json=payload, headers=headers)
print(response.json())</pre>
                                </div>

                                <div class="border-t border-slate-800 pt-3.5 space-y-2.5">
                                    <div class="flex items-center space-x-2 text-xs text-indigo-400 font-bold">
                                        <i class="ph ph-info"></i>
                                        <span>Authentication</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 leading-relaxed">
                                        Semua request API membutuhkan header <code>Authorization: Bearer &lt;YOUR_API_KEY&gt;</code>. Key yang dinonaktifkan akan mengembalikan respon <code>401 Unauthorized</code>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL: Settings -->
                <div x-show="activeTab === 'settings'" class="space-y-6" x-transition style="display: none;">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 font-outfit">System Settings</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Konfigurasi otoritas sertifikasi, enkripsi, dan preferensi akun Anda.</p>
                    </div>

                    <div class="glass-card rounded-2xl p-6 space-y-6">
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-3">Certification Authority (CA) Integration</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Primary CA Provider</label>
                                    <select class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none">
                                        <option>BSrE (Badan Siber dan Sandi Negara)</option>
                                        <option>Kemenkominfo Root CA</option>
                                        <option>DigiCert Enterprise CA</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">TSA Server Endpoint</label>
                                    <input type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none font-mono" value="https://tsa.bsre.go.id/rfc3161">
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-6">
                            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-3">Security & Keys</h4>
                            <div class="flex items-center justify-between p-3.5 bg-slate-50 border border-slate-200/60 rounded-xl text-xs">
                                <div class="flex items-center space-x-2.5">
                                    <i class="ph ph-key text-xl text-indigo-500"></i>
                                    <div>
                                        <p class="font-bold text-slate-800">Symmetric App Key</p>
                                        <p class="text-slate-400 font-mono">base64:{{ substr(env('APP_KEY', 'default_key_value_string_here_example'), 7, 24) }}...</p>
                                    </div>
                                </div>
                                <button @click="showToast('Aplikasi tidak boleh meregenerasi kunci dalam sesi demo.', 'error')" class="bg-white hover:bg-slate-50 border border-slate-200 font-semibold px-3 py-1.5 rounded-lg transition-colors">Rotate Key</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Chart Configuration Script -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Main Donut Chart
            const ctxDonut = document.getElementById('donutChart').getContext('2d');
            window.donutChartInstance = new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: ['Signed', 'Pending', 'Draft', 'Rejected'],
                    datasets: [{
                        data: [{{ $signedDocs }}, {{ $pendingDocs }}, {{ $draftDocs }}, {{ $rejectedDocs }}],
                        backgroundColor: ['#6366f1', '#f59e0b', '#94a3b8', '#f43f5e'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    cutout: '78%',
                    plugins: { 
                        legend: { display: false }, 
                        tooltip: { 
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                            bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: true
                        } 
                    },
                    maintainAspectRatio: false
                }
            });

            // Mini Donut Chart (Certificates)
            const ctxCertDonut = document.getElementById('certDonutChart').getContext('2d');
            window.certDonutChartInstance = new Chart(ctxCertDonut, {
                type: 'doughnut',
                data: {
                    labels: ['Valid', 'Expiring Soon', 'Expired'],
                    datasets: [{
                        data: [{{ $validCerts }}, {{ $expiringSoonCerts }}, {{ $expiredCerts }}],
                        backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    cutout: '78%',
                    plugins: { legend: { display: false } },
                    maintainAspectRatio: false
                }
            });

            // Line Chart
            const ctxLine = document.getElementById('lineChart').getContext('2d');
            
            // Create Gradient for Line Chart Area
            let gradient = ctxLine.createLinearGradient(0, 0, 0, 150);
            gradient.addColorStop(0, 'rgba(99, 102, 241, 0.22)');
            gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

            window.lineChartInstance = new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: ['1 Jun', '6 Jun', '11 Jun', '16 Jun', '21 Jun'],
                    datasets: [{
                        label: 'Aliran Dokumen',
                        data: [15, 28, 22, 48, 60],
                        borderColor: '#6366f1',
                        backgroundColor: gradient,
                        borderWidth: 2,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#6366f1',
                        pointBorderWidth: 2.5,
                        pointRadius: 4.5,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            max: 60,
                            ticks: { stepSize: 20, color: '#94a3b8', font: { size: 10, family: 'Plus Jakarta Sans' } },
                            border: { display: false },
                            grid: { color: 'rgba(226, 232, 240, 0.6)' }
                        },
                        x: { 
                            ticks: { color: '#94a3b8', font: { size: 10, family: 'Plus Jakarta Sans' } },
                            border: { display: false },
                            grid: { display: false }
                        }
                    }
                }
            });
        });
    </script>

    <!-- Modal: Upload Document -->
    <div x-show="uploadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;" x-transition>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100" @click.away="uploadModal = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800 font-outfit">Upload New Document</h3>
                <button @click="uploadModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50"><i class="ph ph-x text-lg"></i></button>
            </div>
            <form action="/documents" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-indigo-200 hover:border-indigo-400 rounded-2xl p-6 text-center cursor-pointer bg-indigo-50/20 transition-all relative">
                    <input type="file" name="file" @change="fileName = $event.target.files[0].name" class="absolute inset-0 opacity-0 cursor-pointer" required>
                    <i class="ph ph-upload-simple text-3xl text-indigo-500 mb-2"></i>
                    <p class="text-sm font-semibold text-slate-700">Drag & Drop or Click to browse</p>
                    <p class="text-xs text-slate-400 mt-1">Supports PDF, DOCX, XLSX up to 10MB</p>
                </div>
                <template x-if="fileName">
                    <div class="flex items-center space-x-2 bg-indigo-50 border border-indigo-100 p-2.5 rounded-xl text-xs text-indigo-700">
                        <i class="ph ph-file-text text-lg"></i>
                        <span class="font-bold truncate" x-text="fileName"></span>
                    </div>
                </template>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Document Type</label>
                    <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="Kontrak">Kontrak</option>
                        <option value="Proposal">Proposal</option>
                        <option value="SOP">SOP</option>
                        <option value="Laporan">Laporan</option>
                    </select>
                </div>
                <button type="submit" :disabled="!fileName" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl transition-all shadow-md shadow-indigo-500/20">
                    Upload & Save
                </button>
            </form>
        </div>
    </div>

    <!-- Modal: Request Signature -->
    <div x-show="signatureModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;" x-transition>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100" @click.away="signatureModal = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800 font-outfit">Request Digital Signature</h3>
                <button @click="signatureModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50"><i class="ph ph-x text-lg"></i></button>
            </div>
            <form action="/signatures" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Select Document</label>
                    <select name="document_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none">
                        @foreach($allDocuments as $doc)
                            <option value="{{ $doc->id }}">{{ $doc->title }} ({{ ucfirst($doc->status) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Signer Name</label>
                    <select name="signer_id" x-model="signerName" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none">
                        <option value="">-- Pilih Signer --</option>
                        @foreach($allUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Signer Message (Optional)</label>
                    <textarea name="message" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none h-20" placeholder="Silakan tanda tangani kontrak ini untuk kelanjutan project..."></textarea>
                </div>
                <button type="submit" :disabled="!signerName" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl transition-all shadow-md shadow-indigo-500/20">
                    Send Request
                </button>
            </form>
        </div>
    </div>

    <!-- Modal: Create Certificate -->
    <div x-show="certModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;" x-transition>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100" @click.away="certModal = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800 font-outfit">Issue Digital Certificate</h3>
                <button @click="certModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50"><i class="ph ph-x text-lg"></i></button>
            </div>
            <form action="/certificates" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Certificate Type</label>
                    <select name="name" x-model="certName" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none">
                        <option value="">-- Pilih Jenis Sertifikat --</option>
                        <option value="SSL Wildcard Certificate">SSL Wildcard Certificate</option>
                        <option value="Code Signing Certificate">Code Signing Certificate</option>
                        <option value="E-Sign Enterprise Certificate">E-Sign Enterprise Certificate</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Holder (Company/Individual)</label>
                    <input type="text" name="holder" x-model="certHolder" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none" placeholder="PT Lexa Teknologi Indonesia" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Validity Period</label>
                    <select name="validity" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none">
                        <option value="1 Tahun">1 Tahun (365 hari)</option>
                        <option value="2 Tahun">2 Tahun (730 hari)</option>
                        <option value="90 Hari">90 Hari (Percobaan)</option>
                    </select>
                </div>
                <button type="submit" :disabled="!certName || !certHolder" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl transition-all shadow-md shadow-indigo-500/20">
                    Issue Certificate
                </button>
            </form>
        </div>
    </div>

    <!-- Modal: Create Team -->
    <div x-show="createTeamModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;" x-transition>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100" @click.away="createTeamModal = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800 font-outfit">Create New Team</h3>
                <button @click="createTeamModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50"><i class="ph ph-x text-lg"></i></button>
            </div>
            <form action="/teams" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Team Name</label>
                    <input type="text" name="name" x-model="teamName" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. Finance Division" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Description (Optional)</label>
                    <textarea name="description" x-model="teamDescription" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 h-24" placeholder="Brief explanation of the team's scope or purpose..."></textarea>
                </div>
                <button type="submit" :disabled="!teamName" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl transition-all shadow-md shadow-indigo-500/20">
                    Create Team
                </button>
            </form>
        </div>
    </div>

    <!-- Modal: Manage Team Members -->
    <div x-show="manageTeamMembersModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;" x-transition>
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100" @click.away="manageTeamMembersModal = false">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 font-outfit" x-text="'Manage Members - ' + selectedTeam.name"></h3>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="selectedTeam.description || 'Tidak ada deskripsi.'"></p>
                </div>
                <button @click="manageTeamMembersModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50"><i class="ph ph-x text-lg"></i></button>
            </div>
            
            <div class="space-y-6">
                <!-- Add Member Form -->
                <form :action="'/teams/' + selectedTeam.id + '/members'" method="POST" class="bg-slate-50 p-4 rounded-2xl border border-slate-150 space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Select User</label>
                            <select name="user_id" x-model="newMemberId" class="w-full bg-white border border-slate-200 rounded-xl p-2 text-xs text-slate-700 focus:outline-none" required>
                                <option value="">-- Pilih User --</option>
                                <template x-for="user in allUsersList.filter(u => !selectedTeam.members.some(m => m.id === u.id))" :key="user.id">
                                    <option :value="user.id" x-text="user.name + ' (' + user.email + ')'"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Team Role</label>
                            <select name="role" x-model="newMemberRole" class="w-full bg-white border border-slate-200 rounded-xl p-2 text-xs text-slate-700 focus:outline-none" required>
                                <option value="Member">Member</option>
                                <option value="Leader">Leader</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" :disabled="!newMemberId" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold py-1.5 rounded-xl text-xs transition-all shadow-sm">
                        Add to Team
                    </button>
                </form>

                <!-- Current Members List -->
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2" x-text="'Current Members (' + selectedTeam.members.length + ')'"></h4>
                    <div class="divide-y divide-slate-100 max-h-56 overflow-y-auto pr-1">
                        <template x-for="member in selectedTeam.members" :key="member.id">
                            <div class="flex items-center justify-between py-2.5">
                                <div class="flex items-center space-x-2.5">
                                    <img class="w-8 h-8 rounded-full border border-slate-200" :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(member.name) + '&background=bfdbfe&color=1e3a8a&bold=true'" alt="Member Avatar">
                                    <div>
                                        <p class="text-xs font-bold text-slate-800" x-text="member.name"></p>
                                        <p class="text-[10px] text-slate-400 font-mono" x-text="member.email"></p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-md" :class="member.pivot.role === 'Leader' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-slate-50 text-slate-600 border border-slate-100'" x-text="member.pivot.role"></span>
                                    
                                    <!-- Delete member form -->
                                    <form :action="'/teams/' + selectedTeam.id + '/members/' + member.id" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin mengeluarkan anggota ini dari tim?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 p-1.5 rounded-lg border border-rose-100 transition-colors" title="Remove Member">
                                            <i class="ph ph-user-minus text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </template>
                        <template x-if="selectedTeam.members.length === 0">
                            <p class="text-xs text-slate-400 text-center py-4">Belum ada anggota di tim ini.</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Create / Display API Key -->
    <div x-show="apiKeyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;" x-transition>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100" @click.away="apiKeyModal = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800 font-outfit" x-text="generatedKey ? 'API Token Generated' : 'Create API Key'"></h3>
                <button @click="apiKeyModal = false; generatedKey = ''" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50"><i class="ph ph-x text-lg"></i></button>
            </div>
            
            <!-- Step 1: Input name to create key -->
            <template x-if="!generatedKey">
                <form action="/api-keys" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Key Description / Name</label>
                        <input type="text" name="name" x-model="newApiKeyName" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g., Prod Integration Server" required>
                    </div>
                    <button type="submit" :disabled="!newApiKeyName" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl transition-all shadow-md shadow-indigo-500/20">
                        Generate Token
                    </button>
                </form>
            </template>

            <!-- Step 2: Show generated key (one-time display) -->
            <template x-if="generatedKey">
                <div class="space-y-4">
                    <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-2xl text-xs text-amber-800 leading-relaxed flex items-start space-x-2.5">
                        <i class="ph ph-warning-octagon text-xl text-amber-600 flex-shrink-0 mt-0.5"></i>
                        <span>
                            <strong>Perhatian:</strong> Salin token API di bawah ini sekarang. Demi keamanan, token ini tidak akan ditampilkan kembali setelah Anda menutup modal ini.
                        </span>
                    </div>

                    <div class="relative bg-slate-50 border border-slate-200 rounded-2xl p-3 flex items-center justify-between">
                        <span class="text-xs font-mono font-bold text-slate-700 select-all truncate mr-2" x-text="generatedKey"></span>
                        <button @click="
                            navigator.clipboard.writeText(generatedKey); 
                            showToast('Token berhasil disalin ke clipboard!', 'success');
                        " class="bg-indigo-50 hover:bg-indigo-100 border border-indigo-150 text-indigo-600 font-bold p-2 rounded-xl text-xs flex items-center justify-center transition-colors" title="Copy to clipboard">
                            <i class="ph ph-copy text-base"></i>
                        </button>
                    </div>

                    <button @click="apiKeyModal = false; generatedKey = ''" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded-xl transition-all">
                        Saya Sudah Menyalin Key Ini
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Modal: Verify Document -->
    <div x-show="verifyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;" x-transition>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100" @click.away="verifyModal = false; verified = false; verifying = false;">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800 font-outfit">Verify Digital Signature</h3>
                <button @click="verifyModal = false; verified = false; verifying = false;" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50"><i class="ph ph-x text-lg"></i></button>
            </div>
            <div class="space-y-4">
                <div class="border-2 border-dashed border-indigo-200 hover:border-indigo-400 rounded-2xl p-6 text-center cursor-pointer bg-indigo-50/20 transition-all relative">
                    <input type="file" @change="verifyFileName = $event.target.files[0].name" class="absolute inset-0 opacity-0 cursor-pointer">
                    <i class="ph ph-shield-check text-3xl text-indigo-500 mb-2"></i>
                    <p class="text-sm font-semibold text-slate-700">Drag & Drop or Click to browse</p>
                    <p class="text-xs text-slate-400 mt-1">Pilih file PDF yang sudah ditandatangani</p>
                </div>
                <template x-if="verifyFileName">
                    <div class="flex items-center space-x-2 bg-indigo-50 border border-indigo-100 p-2.5 rounded-xl text-xs text-indigo-700">
                        <i class="ph ph-file-text text-lg"></i>
                        <span class="font-bold truncate" x-text="verifyFileName"></span>
                    </div>
                </template>
                
                <template x-if="verifying">
                    <div class="flex flex-col items-center justify-center py-4 space-y-2">
                        <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-xs text-slate-500 font-medium">Sedang memverifikasi stempel digital...</p>
                    </div>
                </template>

                <template x-if="verified && verifyDetails">
                    <div class="bg-emerald-50 border border-emerald-150 p-4 rounded-2xl text-xs text-emerald-850 space-y-2">
                        <div class="flex items-center space-x-2 text-sm font-bold text-emerald-900">
                            <i class="ph ph-check-square-offset text-xl"></i>
                            <span>Tanda Tangan Valid & Terverifikasi</span>
                        </div>
                        <p><strong>Dokumen:</strong> <span x-text="verifyDetails.title"></span></p>
                        <p><strong>Penerbit Sertifikat:</strong> <span x-text="verifyDetails.ca"></span></p>
                        <p><strong>Penandatangan:</strong> <span x-text="verifyDetails.signer"></span> ( <span x-text="verifyDetails.email"></span> )</p>
                        <p><strong>Stempel Waktu:</strong> <span x-text="verifyDetails.timestamp"></span></p>
                    </div>
                </template>

                <button @click="simuleVerify()" x-show="!verifying && !verified" :disabled="!verifyFileName" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl transition-all shadow-md shadow-indigo-500/20">
                    Mulai Verifikasi
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="toastShow" 
         x-transition:enter="transition ease-out duration-350 transform"
         x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-5 right-5 z-50 flex items-center p-4 w-full max-w-xs text-slate-800 bg-white/95 backdrop-blur rounded-2xl border border-slate-100 shadow-xl"
         role="alert" style="display: none;">
        <div class="inline-flex flex-shrink-0 justify-center items-center w-8 h-8 rounded-xl"
             :class="{
                 'bg-emerald-50 text-emerald-600 border border-emerald-100': toastType === 'success',
                 'bg-blue-50 text-blue-600 border border-blue-100': toastType === 'info',
                 'bg-rose-50 text-rose-600 border border-rose-100': toastType === 'error'
             }">
            <i class="text-xl" :class="{
                'ph ph-check-circle': toastType === 'success',
                'ph ph-info-bold': toastType === 'info',
                'ph ph-x-circle': toastType === 'error'
            }"></i>
        </div>
        <div class="ml-3 text-xs font-bold text-slate-600" x-text="toastMessage"></div>
        <button type="button" @click="toastShow = false" class="ml-auto -mx-1.5 -my-1.5 bg-white text-slate-400 hover:text-slate-900 rounded-lg p-1.5 inline-flex h-8 w-8 transition">
            <i class="ph ph-x text-lg"></i>
        </button>
    </div>

</body>
</html>
