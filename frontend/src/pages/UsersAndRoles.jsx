import React, { useState, useEffect } from 'react';
import { 
    Users,
    ShieldCheck,
    CheckCircle,
    Warning
} from '@phosphor-icons/react';


export default function UsersAndRoles() {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchUsers = async () => {
        setLoading(true);
        try {
            const response = await fetch('http://localhost:5000/api/auth/users');
            if (response.ok) {
                const data = await response.json();
                const formatted = data.map((u) => ({
                    id: u.id,
                    name: u.name,
                    email: u.email,
                    role: u.role || 'member',
                    team: u.role === 'admin' ? 'Management' : 'General',
                    status: 'active',
                    created_at: u.created_at || new Date().toISOString(),
                    last_login: new Date().toISOString()
                }));
                setUsers(formatted);
            } else {
                setError('Gagal memuat data pengguna dari database.');
            }
        } catch (err) {
            setError('Gagal menghubungi server database.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchUsers();
    }, []);

    return (
        <div className="p-8 space-y-6">
            <div>
                <h2 className="text-2xl font-bold text-slate-800 font-outfit">Users & Roles</h2>
                <p className="text-sm text-slate-500 mt-0.5 font-sans">Kelola keanggotaan tim, otorisasi tanda tangan, dan hak akses.</p>
            </div>

            {error && (
                <div className="p-4 rounded-2xl flex items-center space-x-3 text-xs font-sans bg-rose-50 text-rose-800 border border-rose-200">
                    <Warning size={18} />
                    <span>{error}</span>
                </div>
            )}

            <div className="bg-white/80 backdrop-blur border border-slate-200/60 rounded-3xl p-6 shadow-sm font-sans">
                {loading ? (
                    <div className="text-center py-8 text-slate-500 text-xs">Memuat data...</div>
                ) : (
                    <div className="overflow-x-auto">
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
                                {users.map((userItem) => (
                                    <tr key={userItem.id} className="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center space-x-2.5">
                                                <img 
                                                    class="w-8 h-8 rounded-full border border-slate-200 shadow-sm object-cover" 
                                                    src={userItem.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(userItem.name)}&background=bfdbfe&color=1e3a8a&bold=true`} 
                                                    alt="User" 
                                                />
                                                <span class="font-bold text-slate-800 text-[13px] leading-normal">{userItem.name}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-xs text-slate-500 font-semibold font-mono">
                                            {userItem.email}
                                        </td>
                                        <td class="px-4 py-4 text-xs">
                                            <span class="bg-indigo-50 border border-indigo-100/60 text-indigo-700 font-bold px-2.5 py-1 rounded-md">
                                                {userItem.role === 'admin' ? 'Owner / Administrator' : 'Staff Member'}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-xs text-slate-500 font-medium">
                                            <span class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 font-bold flex items-center w-fit space-x-1">
                                                <ShieldCheck size={14} weight="bold" />
                                                <span>BSrE Verified</span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-xs text-slate-400 font-medium">
                                            {new Date(userItem.created_at || new Date()).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}
