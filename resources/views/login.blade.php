<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEXA - Login</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module">
       import '@phosphor-icons/web';
   </script>
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
                            500: '#4f46e5',
                            600: '#4338ca',
                            700: '#3730a3',
                            900: '#1e1b4b',
                            950: '#07071f',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #07071f; 
        }
        .dot-pattern {
            background-image: radial-gradient(rgba(99, 102, 241, 0.12) 1.2px, transparent 1.2px);
            background-size: 24px 24px;
        }
        .glass-card { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(24px); 
            -webkit-backdrop-filter: blur(24px); 
            border: 1px solid rgba(255, 255, 255, 0.08); 
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="text-slate-100 flex items-center justify-center min-h-screen relative overflow-hidden dot-pattern">

    <!-- Floating Background Gradient Blobs -->
    <div class="fixed top-[-10%] left-[-10%] w-[45vw] h-[45vw] bg-indigo-500/10 rounded-full blur-[120px] pointer-events-none z-0"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-[45vw] h-[45vw] bg-purple-500/10 rounded-full blur-[120px] pointer-events-none z-0"></div>

    <div class="relative z-10 w-full max-w-md p-4">
        <!-- Logo Header -->
        <div class="flex flex-col items-center mb-8">
            <div class="bg-indigo-600 p-3 rounded-2xl shadow-lg shadow-indigo-500/35 mb-3">
                <i class="ph-bold ph-pen-nib text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-extrabold tracking-wide font-outfit text-white">LEXA</h1>
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-widest mt-1">Digital Signature System</p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-3xl p-8 relative overflow-hidden">
            <h2 class="text-xl font-bold font-outfit text-white mb-1">Welcome Back</h2>
            <p class="text-xs text-slate-400 mb-6">Masuk untuk mengelola dokumen dan sertifikat digital Anda.</p>

            @if($errors->any())
                <div class="bg-rose-500/10 border border-rose-500/20 text-rose-200 text-xs p-3.5 rounded-2xl mb-4 leading-relaxed flex items-start space-x-2">
                    <i class="ph-bold ph-warning text-base shrink-0 mt-0.5"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if(session('success'))
                <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-200 text-xs p-3.5 rounded-2xl mb-4 leading-relaxed flex items-start space-x-2">
                    <i class="ph-bold ph-check-circle text-base shrink-0 mt-0.5"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="/login" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="ph ph-envelope text-lg"></i>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 pl-11 pr-4 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white/10 transition-all" placeholder="admin@lexa.com">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="ph ph-lock text-lg"></i>
                        </span>
                        <input type="password" name="password" required class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 pl-11 pr-4 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white/10 transition-all" placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3.5 rounded-2xl transition-all shadow-lg shadow-indigo-500/25 mt-2">
                    Login Account
                </button>
            </form>
        </div>

        <!-- Help Info / Demo Credentials -->
        <div class="text-center mt-6">
            <p class="text-xs text-slate-500">Demo Accounts:</p>
            <div class="flex justify-center space-x-4 mt-2 text-[10px] text-slate-400">
                <div>
                    <span class="font-bold text-indigo-400">Admin:</span> admin@lexa.com <span class="text-slate-500">(pw: password)</span>
                </div>
                <div>
                    <span class="font-bold text-indigo-400">User:</span> user@lexa.com <span class="text-slate-500">(pw: password)</span>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
