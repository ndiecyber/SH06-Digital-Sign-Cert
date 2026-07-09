import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import { Envelope, Lock, Warning } from '@phosphor-icons/react';

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [name, setName] = useState('');
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [isRegistering, setIsRegistering] = useState(false);
    
    const { login, register, loginRememberedUser, removeRememberedUser } = useAuth();
    const navigate = useNavigate();

    const rememberedUsers = JSON.parse(localStorage.getItem('lexa_remembered_users') || '[]');
    const [showClassicForm, setShowClassicForm] = useState(rememberedUsers.length === 0);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setIsLoading(true);
        
        try {
            let res;
            if (isRegistering) {
                res = await register(name, email, password);
            } else {
                res = await login(email, password);
            }

            if (res.success) {
                navigate('/');
            } else {
                setError(res.message);
            }
        } catch (err) {
            setError('Terjadi kesalahan sistem. Silakan coba lagi.');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="min-h-screen text-slate-100 flex items-center justify-center relative overflow-hidden bg-[#07071f] dot-pattern w-full">
            {/* Floating Background Gradient Blobs */}
            <div className="fixed top-[-10%] left-[-10%] w-[45vw] h-[45vw] bg-indigo-500/10 rounded-full blur-[120px] pointer-events-none z-0"></div>
            <div className="fixed bottom-[-10%] right-[-10%] w-[45vw] h-[45vw] bg-purple-500/10 rounded-full blur-[120px] pointer-events-none z-0"></div>

            <div className="relative z-10 w-full max-w-md p-4">
                {/* Logo Header */}
                <div className="flex flex-col items-center mb-8">
                    <img src="/logo.png" alt="LEXA Logo" className="h-28 w-auto mb-3" />
                </div>

                {/* Switch accounts list (Instagram-style) */}
                {!isRegistering && !showClassicForm && rememberedUsers.length > 0 ? (
                    <div className="glass-card-dark rounded-3xl p-8 relative overflow-hidden text-center space-y-6">
                        <div>
                            <h2 className="text-xl font-bold font-outfit text-white mb-1">Masuk ke LEXA</h2>
                            <p className="text-xs text-slate-400">Pilih akun yang ingin Anda gunakan untuk masuk.</p>
                        </div>

                        <div className="space-y-2.5 max-h-64 overflow-y-auto">
                            {rememberedUsers.map((u) => (
                                <div 
                                    key={u.email}
                                    className="flex items-center justify-between p-3 bg-white/5 border border-white/10 rounded-2xl hover:bg-white/10 transition-colors"
                                >
                                    <div className="flex items-center space-x-3 text-left">
                                        <img 
                                            src={u.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(u.name)}&background=0D8ABC&color=fff&size=40`}
                                            alt={u.name}
                                            className="w-9 h-9 rounded-full border border-slate-700 object-cover"
                                        />
                                        <div className="truncate w-36">
                                            <h4 className="text-xs font-semibold text-white truncate">{u.name}</h4>
                                            <span className="text-[9px] text-slate-400 truncate block leading-none font-mono">{u.email}</span>
                                        </div>
                                    </div>
                                    <div className="flex items-center space-x-1.5">
                                        <button 
                                            onClick={() => {
                                                loginRememberedUser(u.email);
                                                navigate('/');
                                            }}
                                            className="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-[10px] font-bold rounded-xl transition-all cursor-pointer font-sans"
                                        >
                                            Masuk
                                        </button>
                                        <button 
                                            onClick={() => {
                                                removeRememberedUser(u.email);
                                                // Trigger state re-check if remembered list is empty
                                                const updatedList = JSON.parse(localStorage.getItem('lexa_remembered_users') || '[]');
                                                if (updatedList.length === 0) {
                                                    setShowClassicForm(true);
                                                }
                                            }}
                                            className="p-1.5 hover:bg-rose-500/10 text-slate-400 hover:text-rose-400 rounded-xl transition-colors cursor-pointer"
                                            title="Hapus Akun"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="pt-2 flex flex-col space-y-3">
                            <button 
                                onClick={() => setShowClassicForm(true)}
                                className="w-full bg-white/5 border border-white/10 hover:bg-white/10 text-slate-200 font-bold py-3.5 rounded-2xl text-xs transition-colors cursor-pointer font-sans"
                            >
                                + Masuk ke Akun Lain
                            </button>
                            <button 
                                onClick={() => {
                                    setIsRegistering(true);
                                    setShowClassicForm(true);
                                }}
                                className="text-xs text-slate-400 hover:text-indigo-400 transition-colors font-sans cursor-pointer"
                            >
                                Belum punya akun? Daftar sekarang
                            </button>
                        </div>
                    </div>
                ) : (
                    /* Classic Login/Register Form */
                    <div className="glass-card-dark rounded-3xl p-8 relative overflow-hidden">
                        <h2 className="text-xl font-bold font-outfit text-white mb-1">
                            {isRegistering ? 'Create Account' : 'Welcome Back'}
                        </h2>
                        <p className="text-xs text-slate-400 mb-6">
                            {isRegistering ? 'Daftar akun baru untuk mulai meminta tanda tangan.' : 'Masuk untuk mengelola dokumen dan sertifikat digital Anda.'}
                        </p>

                        {error && (
                            <div className="bg-rose-500/10 border border-rose-500/20 text-rose-200 text-xs p-3.5 rounded-2xl mb-4 leading-relaxed flex items-start space-x-2">
                                <Warning size={16} weight="bold" className="flex-shrink-0 mt-0.5" />
                                <span>{error}</span>
                            </div>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-4">
                            {isRegistering && (
                                <div>
                                    <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Full Name</label>
                                    <div className="relative">
                                        <input 
                                            type="text" 
                                            value={name}
                                            onChange={(e) => setName(e.target.value)}
                                            required 
                                            className="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white/10 transition-all" 
                                            placeholder="Nama Lengkap"
                                        />
                                    </div>
                                </div>
                            )}

                            <div>
                                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                                <div className="relative">
                                    <span className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <Envelope size={18} />
                                    </span>
                                    <input 
                                        type="email" 
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        required 
                                        className="w-full bg-white/5 border border-white/10 rounded-2xl py-3 pl-11 pr-4 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white/10 transition-all" 
                                        placeholder={isRegistering ? "email@contoh.com" : "admin@lexa.com"}
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                                <div className="relative">
                                    <span className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <Lock size={18} />
                                    </span>
                                    <input 
                                        type="password" 
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                        required 
                                        className="w-full bg-white/5 border border-white/10 rounded-2xl py-3 pl-11 pr-4 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white/10 transition-all" 
                                        placeholder="••••••••"
                                    />
                                </div>
                            </div>

                            <button 
                                type="submit" 
                                disabled={isLoading}
                                className="w-full bg-indigo-600 hover:bg-indigo-500 disabled:bg-indigo-600/50 text-white font-bold py-3.5 rounded-2xl transition-all shadow-lg shadow-indigo-500/25 mt-2 flex items-center justify-center cursor-pointer"
                            >
                                {isLoading ? 'Loading...' : (isRegistering ? 'Daftar Akun' : 'Login Account')}
                            </button>
                        </form>

                        <div className="mt-5 text-center flex flex-col space-y-2">
                            <button 
                                type="button" 
                                onClick={() => {
                                    setIsRegistering(!isRegistering);
                                    setError('');
                                }}
                                className="text-xs text-slate-400 hover:text-indigo-400 transition-colors font-sans cursor-pointer"
                            >
                                {isRegistering ? 'Sudah punya akun? Login di sini' : 'Belum punya akun? Daftar sekarang'}
                            </button>
                            
                            {rememberedUsers.length > 0 && (
                                <button 
                                    type="button" 
                                    onClick={() => {
                                        setShowClassicForm(false);
                                        setIsRegistering(false);
                                        setError('');
                                    }}
                                    className="text-xs text-indigo-400 hover:text-indigo-300 transition-colors font-sans font-semibold cursor-pointer"
                                >
                                    ← Kembali ke Daftar Akun
                                </button>
                            )}
                        </div>
                    </div>
                )}

                {/* Help Info / Demo Credentials */}
                {(!isRegistering && (showClassicForm || rememberedUsers.length === 0)) && (
                    <div className="text-center mt-6">
                        <p className="text-xs text-slate-500 font-sans">Demo Accounts:</p>
                        <div className="flex flex-col sm:flex-row justify-center items-center sm:space-x-4 space-y-1 sm:space-y-0 mt-2 text-[10px] text-slate-400 font-sans">
                            <div>
                                <span className="font-bold text-indigo-400">Admin:</span> admin@lexa.com <span className="text-slate-500">(pw: password)</span>
                            </div>
                            <div>
                                <span className="font-bold text-indigo-400">User:</span> user@lexa.com <span className="text-slate-500">(pw: password)</span>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
