import React, { useState, useEffect, useCallback } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Inbox, Send, Trash2, Star, File, Plus, Search,
    RotateCcw, ShieldCheck, Wallet, LayoutGrid, Lock,
    ArrowLeft, Paperclip, X, Eye, EyeOff, CheckSquare,
    MoreVertical, AlertCircle
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

/* ─── csrf helper ─────────────────────────────────────────── */
const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const apiFetch = (url, options = {}) =>
    fetch(url, {
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        ...options,
    }).then(r => r.json());

/* ─── App Launcher ────────────────────────────────────────── */
const AppLauncher = () => {
    const [open, setOpen] = useState(false);
    const apps = [
        { n: 'Account', i: '🛡️', u: import.meta.env.VITE_YG_ACCOUNT_URL || 'http://localhost:8000' },
        { n: 'Drive',   i: '📁', u: import.meta.env.VITE_YG_DRIVE_URL   || 'http://localhost:3007' },
        { n: 'DocX',    i: '📄', u: import.meta.env.VITE_YG_DOCX_URL    || 'http://localhost:8003' },
        { n: 'Meet',    i: '📹', u: import.meta.env.VITE_YG_MEET_URL    || 'http://localhost:3009' },
        { n: 'Chat',    i: '💬', u: import.meta.env.VITE_YG_CHAT_URL    || 'http://localhost:8006' },
        { n: 'Pay',     i: '💳', u: import.meta.env.VITE_YG_PAY_URL     || 'http://localhost:3001' },
    ];
    return (
        <div className="relative">
            <button onClick={() => setOpen(o => !o)} className="w-10 h-10 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center hover:bg-white/10 transition-all">
                <LayoutGrid size={18} className="text-gray-400" />
            </button>
            <AnimatePresence>
                {open && (
                    <motion.div initial={{ opacity: 0, scale: 0.95, y: -8 }} animate={{ opacity: 1, scale: 1, y: 0 }} exit={{ opacity: 0, scale: 0.95, y: -8 }}
                        className="absolute top-13 right-0 w-72 bg-[#0a0a1a]/95 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-2xl p-5 z-[100]">
                        <div className="grid grid-cols-3 gap-3">
                            {apps.map(a => (
                                <a key={a.n} href={a.u} className="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-white/5 transition-all hover:scale-105 cursor-pointer">
                                    <div className="w-11 h-11 bg-white/5 rounded-xl flex items-center justify-center text-2xl border border-white/5">{a.i}</div>
                                    <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest">{a.n}</span>
                                </a>
                            ))}
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
};

/* ─── Compose Modal ───────────────────────────────────────── */
const ComposeModal = ({ onClose, onSent, defaultTo = '', defaultSubject = '' }) => {
    const [form, setForm] = useState({ to: defaultTo, subject: defaultSubject, body: '' });
    const [invoiceAmt, setInvoiceAmt] = useState('');
    const [sending, setSending] = useState(false);
    const [error, setError] = useState('');

    const handleSend = async () => {
        if (!form.to || !form.subject || !form.body) {
            setError('To, subject and body are required.');
            return;
        }
        setSending(true);
        setError('');
        try {
            const res = await apiFetch('/api/mail/send', {
                method: 'POST',
                body: JSON.stringify(form),
            });
            if (res.status === 'success') {
                onSent(res.mail);
                onClose();
            } else {
                setError(res.message || 'Failed to send. Try again.');
            }
        } catch {
            setError('Network error. Make sure the server is running.');
        } finally {
            setSending(false);
        }
    };

    return (
        <motion.div initial={{ y: 30, opacity: 0 }} animate={{ y: 0, opacity: 1 }} exit={{ y: 30, opacity: 0 }}
            className="fixed bottom-0 right-10 w-[600px] max-h-[80vh] bg-[#0a0a1a]/98 backdrop-blur-3xl shadow-2xl rounded-t-[2.5rem] border border-white/10 z-[100] flex flex-col overflow-hidden">
            {/* Header */}
            <div className="px-8 py-5 bg-white/[0.02] border-b border-white/5 flex justify-between items-center shrink-0">
                <h3 className="text-base font-black text-white italic uppercase tracking-tight">New Message</h3>
                <div className="flex gap-2">
                    <button className="w-9 h-9 flex items-center justify-center text-gray-500 hover:text-white"><Eye size={16} /></button>
                    <button onClick={onClose} className="w-9 h-9 flex items-center justify-center bg-white/5 rounded-xl text-gray-500 hover:bg-red-500/20 hover:text-red-400 transition-all"><X size={16} /></button>
                </div>
            </div>

            <div className="flex-1 overflow-y-auto p-8 space-y-4">
                {error && (
                    <div className="flex items-center gap-2 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-400">
                        <AlertCircle size={14} /> {error}
                    </div>
                )}
                <input value={form.to} onChange={e => setForm(f => ({ ...f, to: e.target.value }))}
                    className="w-full bg-transparent border-b border-white/10 py-3 text-sm text-white placeholder-gray-600 focus:border-red-500 outline-none transition-colors"
                    placeholder="To" type="email" />
                <input value={form.subject} onChange={e => setForm(f => ({ ...f, subject: e.target.value }))}
                    className="w-full bg-transparent border-b border-white/10 py-3 text-sm text-white placeholder-gray-600 focus:border-red-500 outline-none transition-colors"
                    placeholder="Subject" />
                <textarea value={form.body} onChange={e => setForm(f => ({ ...f, body: e.target.value }))} rows={7}
                    className="w-full bg-transparent outline-none resize-none text-sm text-gray-300 placeholder-gray-700 leading-relaxed"
                    placeholder="Write your message..." />

                {/* YG Pay Invoice embed */}
                <div className="p-5 bg-emerald-500/5 rounded-2xl border border-emerald-500/20 border-dashed">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2 text-[10px] font-black text-emerald-400 uppercase tracking-widest">
                            <Wallet size={14} /> Attach Payment Request
                        </div>
                        <div className="flex items-center gap-2">
                            <input value={invoiceAmt} onChange={e => setInvoiceAmt(e.target.value)}
                                className="w-20 bg-white/5 border border-emerald-500/20 rounded-lg px-3 py-1.5 text-xs font-black text-emerald-400 outline-none placeholder-emerald-900"
                                placeholder="Amount" />
                            <button onClick={() => {
                                if (!invoiceAmt) return;
                                setForm(f => ({ ...f, body: f.body + `\n\n[YG_INVOICE:${invoiceAmt}]` }));
                                setInvoiceAmt('');
                            }} className="px-4 py-1.5 bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white rounded-lg text-[10px] font-black uppercase transition-all">
                                Generate
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="p-6 border-t border-white/5 flex gap-4 shrink-0">
                <button onClick={handleSend} disabled={sending}
                    className="flex-1 py-4 bg-gradient-to-r from-red-600 to-rose-700 rounded-2xl text-sm font-black italic uppercase tracking-widest text-white shadow-lg shadow-red-500/20 hover:scale-[1.02] active:scale-95 disabled:opacity-50 transition-all flex items-center justify-center gap-2">
                    <Send size={16} /> {sending ? 'Sending...' : 'Send Securely'}
                </button>
                <button className="w-14 h-14 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center text-gray-400 hover:text-white transition-all">
                    <Paperclip size={20} />
                </button>
            </div>
        </motion.div>
    );
};

/* ─── Main Mail Component ─────────────────────────────────── */
export default function YGMail({ initialEmails = [] }) {
    const user = usePage().props.auth?.user;

    const [emails, setEmails]           = useState(initialEmails);
    const [folder, setFolder]           = useState('inbox');
    const [selectedEmail, setSelected] = useState(null);
    const [composing, setComposing]     = useState(false);
    const [replyTo, setReplyTo]         = useState({ to: '', subject: '' });
    const [searchQuery, setSearch]      = useState('');
    const [searchResults, setResults]   = useState(null); // null = not searching
    const [isSearching, setSearching]   = useState(false);
    const [isDecrypting, setDecrypting] = useState(false);
    const [toast, setToast]             = useState('');

    const showToast = (msg) => {
        setToast(msg);
        setTimeout(() => setToast(''), 3000);
    };

    /* ── folder filter ── */
    const displayEmails = searchResults ?? emails.filter(e => {
        if (folder === 'inbox')   return e.folder === 'inbox';
        if (folder === 'sent')    return e.folder === 'sent';
        if (folder === 'trash')   return e.folder === 'trash';
        if (folder === 'starred') return e.starred;
        return true;
    });

    /* ── open email → mark read ── */
    const openEmail = useCallback(async (email) => {
        setDecrypting(true);
        setSelected(email);
        setTimeout(() => setDecrypting(false), 700);

        if (!email.read) {
            try {
                await apiFetch(`/api/mail/${email.id}/read`, { method: 'PATCH' });
                setEmails(prev => prev.map(e => e.id === email.id ? { ...e, read: true } : e));
            } catch { /* silent */ }
        }
    }, []);

    /* ── delete email ── */
    const deleteEmail = useCallback(async (id, currentFolder) => {
        try {
            const res = await apiFetch(`/api/mail/${id}`, { method: 'DELETE' });
            if (res.status === 'deleted') {
                setEmails(prev => prev.filter(e => e.id !== id));
                showToast('Email permanently deleted.');
            } else {
                setEmails(prev => prev.map(e => e.id === id ? { ...e, folder: 'trash' } : e));
                showToast('Moved to trash.');
            }
            if (selectedEmail?.id === id) setSelected(null);
        } catch { showToast('Failed to delete.'); }
    }, [selectedEmail]);

    /* ── search ── */
    const handleSearch = useCallback(async (q) => {
        setSearch(q);
        if (!q.trim()) { setResults(null); return; }
        setSearching(true);
        try {
            const res = await apiFetch(`/api/mail/search?q=${encodeURIComponent(q)}`);
            setResults(Array.isArray(res) ? res : []);
        } catch { setResults([]); }
        finally { setSearching(false); }
    }, []);

    /* ── reply ── */
    const startReply = (email) => {
        setReplyTo({ to: email.from, subject: `Re: ${email.subject}` });
        setComposing(true);
    };

    /* ── after send ── */
    const handleSent = (newMail) => {
        setEmails(prev => [newMail, ...prev]);
        showToast('Message sent successfully!');
    };

    const folders = [
        { id: 'inbox',   icon: <Inbox size={16} />,  label: 'Global Inbox',    count: emails.filter(e => e.folder === 'inbox' && !e.read).length },
        { id: 'sent',    icon: <Send size={16} />,    label: 'Sent',            count: 0 },
        { id: 'starred', icon: <Star size={16} />,    label: 'Starred',         count: 0 },
        { id: 'trash',   icon: <Trash2 size={16} />,  label: 'Trash',           count: emails.filter(e => e.folder === 'trash').length },
    ];

    const formatDate = (d) => {
        if (!d) return '';
        const date = new Date(d);
        const now  = new Date();
        const diff = now - date;
        if (diff < 86400000) return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        if (diff < 604800000) return date.toLocaleDateString([], { weekday: 'short' });
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    };

    const renderBody = (body) => {
        if (!body) return null;
        const parts = body.split(/(\[YG_INVOICE:\d+\])/g);
        return parts.map((part, i) => {
            const m = part.match(/\[YG_INVOICE:(\d+)\]/);
            if (m) {
                const amount = m[1];
                return (
                    <div key={i} className="my-6 p-8 bg-emerald-500/5 border border-emerald-500/20 rounded-3xl space-y-4 max-w-md">
                        <div className="flex items-center gap-3">
                            <Wallet className="text-emerald-400" size={20} />
                            <span className="font-black text-white italic uppercase tracking-tight">YG Payment Request</span>
                        </div>
                        <div className="text-4xl font-black text-white italic">Rs.{Number(amount).toLocaleString()}</div>
                        <button onClick={() => window.location.href = `${import.meta.env.VITE_YG_PAY_URL || '#'}/checkout?amount=${amount}`}
                            className="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black uppercase tracking-widest rounded-2xl flex items-center justify-center gap-2 transition-all text-sm">
                            <ShieldCheck size={16} /> Authorize Payment
                        </button>
                    </div>
                );
            }
            return <p key={i} className="text-sm text-gray-300 leading-relaxed whitespace-pre-wrap">{part}</p>;
        });
    };

    return (
        <div className="flex h-screen bg-[#050510] text-[#EDEDEC] overflow-hidden relative">
            <Head title="YG Mail | Sovereign Inbox" />

            {/* Toast */}
            <AnimatePresence>
                {toast && (
                    <motion.div initial={{ opacity: 0, y: -20 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -20 }}
                        className="fixed top-6 left-1/2 -translate-x-1/2 z-[200] px-6 py-3 bg-[#0d0d20] border border-white/10 rounded-full text-xs font-black text-white shadow-2xl">
                        {toast}
                    </motion.div>
                )}
            </AnimatePresence>

            {/* ── Left Sidebar ── */}
            <aside className="w-72 bg-[#0a0a1a]/60 backdrop-blur-3xl border-r border-white/5 flex flex-col z-50 shrink-0">
                <div className="p-8 flex items-center gap-3">
                    <div className="w-10 h-10 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10">
                        <Lock className="text-red-500" size={20} />
                    </div>
                    <div>
                        <h1 className="text-lg font-black text-white italic uppercase tracking-tighter">Sovereign <span className="text-red-500">Mail</span></h1>
                        <p className="text-[9px] text-gray-600 font-bold uppercase tracking-widest">E2EE Protocol</p>
                    </div>
                </div>

                <div className="px-6 mb-4">
                    <button onClick={() => { setComposing(true); setReplyTo({ to: '', subject: '' }); }}
                        className="w-full py-4 bg-gradient-to-r from-red-600 to-rose-700/80 hover:from-red-500 text-white rounded-2xl font-black italic uppercase tracking-widest flex items-center justify-center gap-2 transition-all shadow-lg shadow-red-500/20">
                        <Plus size={18} /> Compose
                    </button>
                </div>

                <nav className="flex-1 px-4 space-y-0.5">
                    {folders.map(f => (
                        <button key={f.id} onClick={() => { setFolder(f.id); setSelected(null); setResults(null); setSearch(''); }}
                            className={`w-full px-6 py-3.5 rounded-2xl flex items-center justify-between text-[10px] font-black uppercase tracking-widest transition-all border ${
                                folder === f.id && !searchResults
                                    ? 'bg-red-500/10 border-red-500/20 text-red-400'
                                    : 'bg-transparent border-transparent text-gray-500 hover:bg-white/5 hover:text-white'
                            }`}>
                            <span className="flex items-center gap-3">{f.icon} {f.label}</span>
                            {f.count > 0 && <span className="bg-red-500/20 text-red-400 text-[9px] px-2 py-0.5 rounded-md font-black">{f.count}</span>}
                        </button>
                    ))}
                </nav>

                <div className="p-6 border-t border-white/5">
                    <div className="bg-white/5 rounded-2xl p-5 border border-white/10">
                        <div className="flex items-center gap-2 mb-3">
                            <ShieldCheck size={14} className="text-emerald-400" />
                            <span className="text-[9px] font-black text-white uppercase tracking-widest">Secure Node</span>
                        </div>
                        <div className="w-full h-1 bg-white/5 rounded-full">
                            <div className="h-full bg-emerald-500 rounded-full w-full" />
                        </div>
                        <p className="text-[8px] text-gray-600 uppercase tracking-widest mt-2 text-center">AES-256 E2EE Active</p>
                    </div>
                    <a href={import.meta.env.VITE_YG_ACCOUNT_URL || 'http://localhost:8000'}
                        className="block text-center text-[10px] font-black text-gray-600 hover:text-white uppercase tracking-widest mt-4 transition-colors">
                        ← YG Account
                    </a>
                </div>
            </aside>

            {/* ── Main Area ── */}
            <main className="flex-1 flex flex-col overflow-hidden">

                {/* Top bar */}
                <header className="h-20 px-8 border-b border-white/5 flex items-center justify-between gap-6 bg-[#0a0a1a]/20 backdrop-blur-xl shrink-0">
                    <div className="flex-1 max-w-xl flex gap-3">
                        <div className="flex-1 bg-white/5 border border-white/10 h-12 rounded-2xl flex items-center px-5 gap-3 group focus-within:border-white/20 transition-all">
                            <Search className="text-gray-500 group-focus-within:text-white transition-colors shrink-0" size={16} />
                            <input
                                className="bg-transparent border-none w-full h-full focus:ring-0 text-sm text-white placeholder-gray-600 outline-none"
                                placeholder="Search emails..."
                                value={searchQuery}
                                onChange={e => handleSearch(e.target.value)}
                            />
                            {isSearching && <RotateCcw size={14} className="text-gray-500 animate-spin shrink-0" />}
                        </div>
                        {searchResults !== null && (
                            <button onClick={() => { setResults(null); setSearch(''); }} className="px-4 h-12 bg-white/5 border border-white/10 rounded-2xl text-xs text-gray-400 hover:text-white transition-all">
                                Clear
                            </button>
                        )}
                    </div>

                    <div className="flex items-center gap-4">
                        <div className="hidden md:flex items-center gap-2 px-3 py-1.5 bg-red-950/20 border border-red-500/20 rounded-xl">
                            <ShieldCheck size={14} className="text-red-400" />
                            <span className="text-[9px] font-black italic uppercase tracking-widest text-red-400">Secure</span>
                        </div>
                        <AppLauncher />
                        <div className="w-px h-8 bg-white/10" />
                        {user && (
                            <div className="flex items-center gap-3">
                                <div className="text-right hidden lg:block">
                                    <p className="text-xs font-black text-white italic">{user.name}</p>
                                    <p className="text-[9px] text-gray-500">{user.email}</p>
                                </div>
                                <div className="w-10 h-10 bg-gradient-to-br from-red-600 to-rose-800 rounded-2xl flex items-center justify-center font-black text-white italic border border-white/10">
                                    {user.name?.[0]?.toUpperCase()}
                                </div>
                            </div>
                        )}
                        <Link href={route('logout')} method="post" as="button"
                            className="text-[10px] font-black text-gray-600 hover:text-red-400 uppercase tracking-widest transition-colors">
                            Exit
                        </Link>
                    </div>
                </header>

                {/* Email list + viewer */}
                <div className="flex-1 flex overflow-hidden">

                    {/* Email list */}
                    <div className={`flex flex-col border-r border-white/5 overflow-y-auto ${selectedEmail ? 'w-80 shrink-0' : 'flex-1'}`}>
                        <div className="px-6 py-3 border-b border-white/5 flex items-center justify-between shrink-0">
                            <span className="text-[10px] font-black text-white uppercase tracking-widest italic">
                                {searchResults !== null ? `Search: "${searchQuery}"` : folders.find(f => f.id === folder)?.label}
                            </span>
                            <span className="text-[10px] text-gray-600">{displayEmails.length} messages</span>
                        </div>

                        {displayEmails.length === 0 ? (
                            <div className="flex-1 flex flex-col items-center justify-center text-center p-10">
                                <Lock size={40} className="text-gray-800 mb-4" />
                                <p className="text-[10px] font-black text-gray-700 uppercase tracking-widest">No messages</p>
                            </div>
                        ) : (
                            displayEmails.map(email => (
                                <div key={email.id}
                                    onClick={() => openEmail(email)}
                                    className={`px-6 py-5 border-b border-white/[0.03] flex items-start gap-4 cursor-pointer transition-all hover:bg-white/[0.02] group ${
                                        selectedEmail?.id === email.id ? 'bg-red-600/5 border-l-2 border-l-red-500' : ''
                                    } ${!email.read ? 'bg-white/[0.01]' : ''}`}>
                                    <CheckSquare size={16} className="text-gray-700 group-hover:text-gray-400 mt-0.5 shrink-0 transition-colors" />
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-start justify-between gap-2">
                                            <span className={`text-xs truncate ${!email.read ? 'font-black text-white' : 'font-medium text-gray-400'}`}>
                                                {email.folder === 'sent' ? `To: ${email.to}` : email.from}
                                            </span>
                                            <span className="text-[10px] text-gray-600 shrink-0">{formatDate(email.created_at)}</span>
                                        </div>
                                        <p className={`text-[11px] truncate mt-0.5 ${!email.read ? 'font-bold text-gray-200' : 'font-medium text-gray-500'}`}>
                                            {email.subject || '(no subject)'}
                                        </p>
                                        <p className="text-[10px] text-gray-700 truncate mt-0.5">{email.body?.replace(/\[YG_INVOICE:\d+\]/g, '💳 Payment request')}</p>
                                    </div>
                                    <button onClick={e => { e.stopPropagation(); deleteEmail(email.id, email.folder); }}
                                        className="opacity-0 group-hover:opacity-100 p-1 text-gray-700 hover:text-red-400 transition-all">
                                        <Trash2 size={14} />
                                    </button>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Email viewer */}
                    {selectedEmail ? (
                        <div className="flex-1 flex flex-col overflow-hidden">
                            {/* Viewer header */}
                            <div className="px-10 py-5 border-b border-white/5 flex items-center justify-between shrink-0">
                                <button onClick={() => setSelected(null)} className="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-white transition-all group">
                                    <ArrowLeft size={14} className="group-hover:-translate-x-1 transition-transform" /> Back
                                </button>
                                <div className="flex items-center gap-3">
                                    <button onClick={() => startReply(selectedEmail)}
                                        className="px-5 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-white transition-all">
                                        Reply
                                    </button>
                                    <button onClick={() => { setReplyTo({ to: '', subject: `Fwd: ${selectedEmail.subject}` }); setComposing(true); }}
                                        className="px-5 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-400 transition-all">
                                        Forward
                                    </button>
                                    <button onClick={() => deleteEmail(selectedEmail.id, selectedEmail.folder)}
                                        className="p-2 text-gray-600 hover:text-red-400 transition-colors">
                                        <Trash2 size={16} />
                                    </button>
                                </div>
                            </div>

                            {/* Viewer body */}
                            <div className="flex-1 overflow-y-auto p-10">
                                <h2 className="text-3xl font-black text-white italic uppercase tracking-tight leading-tight mb-6">
                                    {selectedEmail.subject || '(no subject)'}
                                </h2>

                                <div className="flex items-center gap-4 p-5 bg-white/[0.02] border border-white/5 rounded-2xl mb-8">
                                    <div className="w-12 h-12 bg-gradient-to-br from-red-600 to-rose-900 rounded-xl flex items-center justify-center font-black text-white italic text-lg">
                                        {selectedEmail.from?.[0]?.toUpperCase()}
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-black text-white italic uppercase tracking-tight">{selectedEmail.from}</p>
                                        <p className="text-[10px] text-gray-500 font-bold mt-1">To: {selectedEmail.to}</p>
                                    </div>
                                    <span className="text-[10px] text-gray-600">{formatDate(selectedEmail.created_at)}</span>
                                </div>

                                <AnimatePresence mode="wait">
                                    {isDecrypting ? (
                                        <motion.div key="dec" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
                                            className="h-48 flex flex-col items-center justify-center gap-4 bg-emerald-500/5 rounded-3xl border border-emerald-500/10 border-dashed">
                                            <div className="relative">
                                                <div className="w-16 h-16 border-4 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin" />
                                                <ShieldCheck className="absolute inset-0 m-auto text-emerald-400" size={24} />
                                            </div>
                                            <p className="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Decrypting payload...</p>
                                        </motion.div>
                                    ) : (
                                        <motion.div key="body" initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="space-y-4">
                                            {renderBody(selectedEmail.body)}
                                        </motion.div>
                                    )}
                                </AnimatePresence>
                            </div>
                        </div>
                    ) : (
                        <div className="flex-1 hidden md:flex items-center justify-center">
                            <div className="text-center">
                                <Lock size={48} className="text-gray-800 mx-auto mb-4" />
                                <p className="text-[10px] font-black text-gray-700 uppercase tracking-widest">Select a message to read</p>
                            </div>
                        </div>
                    )}
                </div>
            </main>

            {/* ── Compose Modal ── */}
            <AnimatePresence>
                {composing && (
                    <ComposeModal
                        key="compose"
                        onClose={() => setComposing(false)}
                        onSent={handleSent}
                        defaultTo={replyTo.to}
                        defaultSubject={replyTo.subject}
                    />
                )}
            </AnimatePresence>
        </div>
    );
}
