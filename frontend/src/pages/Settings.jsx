import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { 
    Key, 
    ShieldCheck, 
    ShieldWarning,
    CreditCard, 
    CheckCircle, 
    Warning
} from '@phosphor-icons/react';


export default function Settings() {
    const { user, upgradePlan, updateUser } = useAuth();
    const [caProvider, setCaProvider] = useState('BSrE (Badan Siber dan Sandi Negara)');
    const [tsaEndpoint, setTsaEndpoint] = useState('https://tsa.bsre.go.id/rfc3161');
    const [status, setStatus] = useState(null);
    const [avatar, setAvatar] = useState(user?.avatar || '');

    useEffect(() => {
        if (user?.avatar) {
            setAvatar(user.avatar);
        }
    }, [user]);


    const handleRotateKey = () => {
        setStatus({ type: 'error', msg: 'Aplikasi tidak boleh meregenerasi kunci dalam sesi demo.' });
        setTimeout(() => setStatus(null), 4000);
    };

    const getStorageText = () => {
        if (user?.plan === 'secure') return '10 GB (AES-256)';
        if (user?.plan === 'enterprise') return 'Unlimited Dedicated';
        return '1 GB Standar';
    };

    const getPaymentMethod = () => {
        if (user?.plan === 'free') return 'Tidak ada';
        return 'Mastercard •••• 4820';
    };

    return (
        <div className="p-8 space-y-6">
            <div>
                <h2 className="text-2xl font-bold text-slate-800 font-outfit">System Settings</h2>
                <p className="text-sm text-slate-500 mt-0.5 font-sans">Konfigurasi otoritas sertifikasi, enkripsi, dan preferensi akun Anda.</p>
            </div>

            {status && (
                <div className={`p-4 rounded-2xl flex items-center space-x-3 text-xs font-sans ${
                    status.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'
                }`}>
                    {status.type === 'success' ? <CheckCircle size={18} /> : <Warning size={18} />}
                    <span>{status.msg}</span>
                </div>
            )}

            {/* Profile Settings */}
            <div className="bg-white/80 backdrop-blur border border-slate-200/60 rounded-3xl p-6 space-y-6 font-sans shadow-sm">
                <div className="border-b border-slate-100 pb-4">
                    <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider font-outfit">Profile Settings</h4>
                    <p className="text-[11px] text-slate-500 mt-0.5">Perbarui nama lengkap, email, dan peran Anda dalam perusahaan.</p>
                </div>
                
                {/* Profile Picture Uploader */}
                <div className="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6 pb-4 border-b border-slate-100/60">
                    <div className="relative group">
                        <img 
                            src={avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&background=0D8ABC&color=fff&size=100`}
                            alt="Profile Avatar"
                            className="w-24 h-24 rounded-full border-2 border-slate-200 object-cover shadow-sm"
                        />
                        <label className="absolute inset-0 bg-black/40 hover:bg-black/60 rounded-full flex flex-col items-center justify-center text-white text-[10px] font-bold opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-center p-2">
                            <span>Ubah Foto</span>
                            <input 
                                type="file" 
                                accept="image/*"
                                onChange={(e) => {
                                    const file = e.target.files[0];
                                    if (file) {
                                        const reader = new FileReader();
                                        reader.onload = (event) => {
                                            setAvatar(event.target.result);
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                }}
                                className="hidden"
                            />
                        </label>
                    </div>
                    <div className="text-center sm:text-left space-y-1">
                        <h4 className="text-sm font-bold text-slate-800 font-outfit">{user?.name}</h4>
                        <p className="text-xs text-slate-400 font-mono">{user?.email}</p>
                        <p className="text-[10px] font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-lg px-2.5 py-0.5 inline-block capitalize">
                            {user?.role === 'admin' ? 'Administrator' : 'Staff Member'}
                        </p>
                    </div>
                </div>

                <form onSubmit={(e) => {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    const name = formData.get('name');
                    const email = formData.get('email');
                    const role = formData.get('role');
                    updateUser({ name, email, role, avatar });
                    setStatus({ type: 'success', msg: 'Profil dan foto berhasil diperbarui!' });
                    setTimeout(() => setStatus(null), 3000);
                }} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-[11px] font-bold text-slate-400 uppercase mb-1.5">Nama Lengkap</label>
                            <input 
                                type="text" 
                                name="name"
                                defaultValue={user?.name || ''}
                                required
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                        </div>
                        <div>
                            <label className="block text-[11px] font-bold text-slate-400 uppercase mb-1.5">Email Address</label>
                            <input 
                                type="email" 
                                name="email"
                                defaultValue={user?.email || ''}
                                required
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                        </div>
                        <div>
                            <label className="block text-[11px] font-bold text-slate-400 uppercase mb-1.5">Peran Sistem (Role)</label>
                            <select 
                                name="role"
                                defaultValue={user?.role || 'user'}
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer"
                            >
                                <option value="admin">Owner / Administrator</option>
                                <option value="user">Staff Member</option>
                            </select>
                        </div>
                    </div>
                    <div className="flex justify-end pt-2">
                        <button 
                            type="submit"
                            className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-colors cursor-pointer"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            <div className="bg-white/80 backdrop-blur border border-slate-200/60 rounded-3xl p-6 space-y-6 font-sans shadow-sm">
                <div>
                    <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider mb-4 font-outfit">Certification Authority (CA) Integration</h4>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-[11px] font-bold text-slate-400 uppercase mb-1.5">Primary CA Provider</label>
                            <select 
                                value={caProvider} 
                                onChange={(e) => setCaProvider(e.target.value)}
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer"
                            >
                                <option>BSrE (Badan Siber dan Sandi Negara)</option>
                                <option>Kemenkominfo Root CA</option>
                                <option>DigiCert Enterprise CA</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-[11px] font-bold text-slate-400 uppercase mb-1.5">TSA Server Endpoint</label>
                            <input 
                                type="text" 
                                value={tsaEndpoint} 
                                onChange={(e) => setTsaEndpoint(e.target.value)}
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono" 
                            />
                        </div>
                    </div>
                </div>

                <div className="border-t border-slate-100 pt-6">
                    <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider mb-4 font-outfit">Security & Keys</h4>
                    <div className="flex items-center justify-between p-3.5 bg-slate-50 border border-slate-200/60 rounded-2xl text-xs">
                        <div className="flex items-center space-x-2.5">
                            <Key size={20} className="text-indigo-500" />
                            <div>
                                <p className="font-bold text-slate-800">Symmetric App Key</p>
                                <p className="text-slate-400 font-mono">base64:lx_app_key_3847293847...</p>
                            </div>
                        </div>
                        <button 
                            onClick={handleRotateKey}
                            className="bg-white hover:bg-slate-50 border border-slate-200 font-semibold px-3 py-1.5 rounded-xl transition-colors cursor-pointer"
                        >
                            Rotate Key
                        </button>
                    </div>
                </div>
            </div>



            {/* Billing & Subscription */}
            <div className="bg-white/80 backdrop-blur border border-slate-200/60 rounded-3xl p-6 space-y-6 font-sans shadow-sm">
                <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider font-outfit">Billing & Subscription</h4>
                        <p className="text-[11px] text-slate-500 mt-0.5">Kelola paket langganan, metode pembayaran, dan lihat invoice Anda.</p>
                    </div>
                    <span className={`text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider ${
                        user?.plan === 'secure' ? 'bg-indigo-50 text-indigo-700 border border-indigo-150' : 
                        user?.plan === 'enterprise' ? 'bg-purple-50 text-purple-700 border border-purple-150' : 
                        'bg-slate-100 text-slate-700'
                    }`}>
                        {user?.plan || 'free'} Plan
                    </span>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Subscription details */}
                    <div className="space-y-4 col-span-2">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="bg-slate-50 border border-slate-200/60 rounded-2xl p-3.5 text-xs">
                                <p class="text-slate-400 font-semibold mb-1">Kapasitas Penyimpanan</p>
                                <p class="text-sm font-bold text-slate-800">{getStorageText()}</p>
                            </div>
                            <div className="bg-slate-50 border border-slate-200/60 rounded-2xl p-3.5 text-xs">
                                <p class="text-slate-400 font-semibold mb-1">Metode Pembayaran</p>
                                <p class="text-sm font-bold text-slate-800 flex items-center space-x-1.5 font-mono">
                                    <CreditCard size={16} />
                                    <span>{getPaymentMethod()}</span>
                                </p>
                            </div>
                        </div>

                        <div className="bg-slate-50 border border-slate-200/60 rounded-2xl p-4 text-xs flex justify-between items-center">
                            <div>
                                <p className="font-bold text-slate-800">
                                    {user?.plan === 'secure' ? 'Paket Secure Aktif' : user?.plan === 'enterprise' ? 'Paket Enterprise Aktif' : 'Anda menggunakan paket Free'}
                                </p>
                                <p className="text-slate-500 mt-1">
                                    {user?.plan === 'free' ? 'Upgrade untuk tanda tangan tanpa batas dan integrasi resmi CA.' : 'Pembayaran berikutnya akan didebit secara otomatis pada 26 Juli 2026.'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Protection status box */}
                    <div className="bg-slate-900 text-white rounded-3xl p-5 relative overflow-hidden flex flex-col justify-between shadow-lg">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-indigo-500 rounded-full blur-3xl opacity-20"></div>
                        <div className="space-y-1 relative z-10">
                            <span className="text-[9px] font-bold text-indigo-300 uppercase tracking-widest">Status Proteksi</span>
                            <p className="text-sm font-bold font-outfit">{user?.plan === 'free' ? 'Proteksi Dasar' : 'Proteksi Maksimal (HSM)'}</p>
                        </div>
                        <div className="mt-4 flex items-center space-x-2 relative z-10">
                            <div className="bg-white/10 p-2 rounded-xl border border-white/10 shrink-0">
                                {user?.plan === 'free' ? (
                                    <ShieldWarning size={18} className="text-amber-400" />
                                ) : (
                                    <ShieldCheck size={18} className="text-emerald-400" />
                                )}
                            </div>
                            <span className="text-[10px] text-slate-300 leading-normal">
                                {user?.plan === 'free' ? 'Segera tingkatkan untuk enkripsi tingkat HSM fungsional.' : 'Kunci privat Anda tersimpan aman di HSM fisik bersertifikasi FIPS 140-2.'}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Invoice History */}
                <div className="border-t border-slate-100 pt-5">
                    <h5 className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3 font-outfit">Riwayat Invoices</h5>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs font-medium text-slate-500">
                            <thead>
                                <tr className="text-slate-400 border-b border-slate-100 uppercase text-[9px] tracking-wider">
                                    <th className="pb-2">Nomor Invoice</th>
                                    <th className="pb-2">Tanggal</th>
                                    <th className="pb-2">Paket</th>
                                    <th className="pb-2">Nominal</th>
                                    <th className="pb-2 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {user?.plan === 'free' ? (
                                    <tr>
                                        <td colSpan="5" className="py-6 text-center text-slate-400">Belum ada riwayat pembayaran untuk paket Free.</td>
                                    </tr>
                                ) : (
                                    <>
                                        <tr className="text-slate-700">
                                            <td className="py-3 font-mono font-bold text-indigo-600">INV/20260626/LX/90384</td>
                                            <td className="py-3 text-slate-500">26 Jun 2026</td>
                                            <td className="py-3 text-slate-600">{user?.plan === 'secure' ? 'LEXA Secure Plan' : 'LEXA Enterprise Plan'}</td>
                                            <td className="py-3 font-semibold text-slate-800">
                                                {user?.plan === 'secure' ? 'Rp 299.000' : 'Rp 9.990.000'}
                                            </td>
                                            <td className="py-3 text-right">
                                                <span className="bg-emerald-50 text-emerald-600 border border-emerald-100 text-[10px] font-bold px-2 py-0.5 rounded-md">Paid</span>
                                            </td>
                                        </tr>
                                    </>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
}
