import { Head, Link } from '@inertiajs/react';
import { Mail, ShieldCheck, Lock, Zap, Globe, HardDrive } from 'lucide-react';

export default function Welcome({ auth }) {
    return (
        <div className="min-h-screen bg-[#050510] text-[#e2e8f0] font-sans overflow-hidden selection:bg-red-600 selection:text-white">
            <Head title="YG Mail | Sovereign E2EE Communication" />
            
            {/* Background Decorative Elements */}
            <div className="fixed inset-0 pointer-events-none">
                <div className="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-red-600/10 blur-[120px] rounded-full"></div>
                <div className="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-rose-900/20 blur-[150px] rounded-full"></div>
                <div className="absolute top-[30%] left-[60%] w-[30%] h-[30%] bg-red-900/5 blur-[100px] rounded-full"></div>
            </div>

            <div className="relative z-10 max-w-7xl mx-auto px-6 lg:px-8">
                {/* Navbar */}
                <header className="flex justify-between items-center py-8">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-gradient-to-br from-red-500 to-rose-700 rounded-xl flex items-center justify-center shadow-lg shadow-red-500/20">
                            <Mail className="w-6 h-6 text-white" />
                        </div>
                        <span className="text-xl font-black tracking-tighter text-white uppercase italic">Ygxone <span className="text-red-500">Mail</span></span>
                    </div>

                    <nav className="flex gap-6 items-center">
                        {auth.user ? (
                            <Link href={route('mail.index')} className="px-6 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full text-xs font-black uppercase tracking-widest transition-all backdrop-blur-md">
                                Enter Inbox
                            </Link>
                        ) : (
                            <>
                                <Link href={route('login')} className="text-xs font-black uppercase tracking-widest hover:text-red-400 transition-colors">Login</Link>
                                <Link href={route('register')} className="px-6 py-2.5 bg-red-600 hover:bg-red-500 rounded-full text-xs font-black uppercase tracking-widest shadow-lg shadow-red-600/30 transition-all">Claim Address</Link>
                            </>
                        )}
                    </nav>
                </header>

                {/* Hero Section */}
                <main className="mt-16 lg:mt-24 text-center">
                    <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 text-[10px] font-black uppercase tracking-[0.2em] mb-10 animate-pulse">
                        <ShieldCheck size={14} /> End-to-End Encrypted Node
                    </div>
                    
                    <h1 className="text-5xl lg:text-8xl font-black tracking-tight text-white leading-tight mb-8">
                        The Future of — <br/>
                        <span className="bg-gradient-to-r from-red-400 via-rose-400 to-orange-400 bg-clip-text text-transparent italic uppercase tracking-tighter">Sovereign Intel.</span>
                    </h1>

                    <p className="max-w-2xl mx-auto text-lg lg:text-xl text-gray-400 font-medium mb-12">
                        Communication without surveillance. YG Mail leverages the Sovereign Protocol to ensure your messages are encrypted, private, and permanent.
                    </p>

                    <div className="flex flex-wrap justify-center gap-6 mb-24">
                        <Link href={route('register')} className="px-10 py-5 bg-red-600 hover:bg-red-500 rounded-2xl text-lg font-black tracking-tight shadow-2xl shadow-red-600/40 transition-all transform hover:-translate-y-1 italic uppercase">
                            Initialize Secure Mail
                        </Link>
                        <a href="#features" className="px-10 py-5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl text-lg font-black tracking-tight backdrop-blur-xl transition-all italic uppercase">
                            Verify Protocol
                        </a>
                    </div>

                    {/* Features Bento */}
                    <div id="features" className="grid lg:grid-cols-3 gap-8 text-left mb-32">
                        <div className="p-10 rounded-[2.5rem] bg-gradient-to-br from-white/5 to-transparent border border-white/10 backdrop-blur-2xl group hover:border-red-500/30 transition-all">
                            <div className="w-14 h-14 bg-red-500/20 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                                <Lock className="text-red-400" />
                            </div>
                            <h3 className="text-2xl font-black text-white italic uppercase tracking-tight mb-4">Zero-Leak E2EE</h3>
                            <p className="text-gray-400 font-medium leading-relaxed">Your keys, your data. Not even the Ygxone Master Node can decrypt your communication pipeline.</p>
                        </div>
                        <div className="p-10 rounded-[2.5rem] bg-gradient-to-br from-white/5 to-transparent border border-white/10 backdrop-blur-2xl lg:translate-y-12 group hover:border-red-500/30 transition-all">
                             <div className="w-14 h-14 bg-orange-500/20 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                                <Zap className="text-orange-400" />
                            </div>
                            <h3 className="text-2xl font-black text-white italic uppercase tracking-tight mb-4">Instant Sync</h3>
                            <p className="text-gray-400 font-medium leading-relaxed">Built on the high-velocity Sovereign Engine, your mail is synchronized across all devices in real-time with no lag.</p>
                        </div>
                        <div className="p-10 rounded-[2.5rem] bg-gradient-to-br from-white/5 to-transparent border border-white/10 backdrop-blur-2xl group hover:border-red-500/30 transition-all">
                             <div className="w-14 h-14 bg-rose-500/20 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                                <HardDrive className="text-rose-400" />
                            </div>
                            <h3 className="text-2xl font-black text-white italic uppercase tracking-tight mb-4">Node Sovereignty</h3>
                            <p className="text-gray-400 font-medium leading-relaxed">Self-hosted architecture options available for enterprise clusters. Absolute control over your mail records.</p>
                        </div>
                    </div>
                </main>

                {/* Footer */}
                <footer className="py-20 border-t border-white/5 flex flex-col lg:flex-row justify-between items-center gap-10">
                    <div className="text-center lg:text-left">
                        <div className="flex items-center justify-center lg:justify-start gap-3 mb-6">
                            <div className="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center"><Mail size={16} className="text-red-500" /></div>
                            <span className="text-lg font-black tracking-tighter text-white uppercase italic">Ygxone <span className="text-red-500">Mail</span></span>
                        </div>
                        <p className="text-gray-500 text-[10px] font-black uppercase tracking-[0.3em]">Sovereign Communication Protocol v1.4.2</p>
                    </div>

                    <div className="flex gap-10 text-center lg:text-left">
                        <div className="flex flex-col gap-3">
                            <h4 className="text-white font-black uppercase text-[10px] tracking-widest">Protocol</h4>
                            <a href="#" className="text-gray-500 hover:text-white transition-colors text-xs font-bold uppercase tracking-tight">E2EE Docs</a>
                            <a href="#" className="text-gray-500 hover:text-white transition-colors text-xs font-bold uppercase tracking-tight">Node Setup</a>
                        </div>
                        <div className="flex flex-col gap-3">
                            <h4 className="text-white font-black uppercase text-[10px] tracking-widest">Ecosystem</h4>
                            <a href="#" className="text-gray-500 hover:text-white transition-colors text-xs font-bold uppercase tracking-tight">Account</a>
                            <a href="#" className="text-gray-500 hover:text-white transition-colors text-xs font-bold uppercase tracking-tight">Drive</a>
                        </div>
                    </div>

                    <div className="px-8 py-4 bg-white/[0.03] border border-white/5 rounded-2xl text-[9px] font-black text-gray-500 uppercase tracking-widest">
                        Master Node: YGX-SVR-HK1 | Status: <span className="text-emerald-500">OPTIMAL</span>
                    </div>
                </footer>
            </div>
            
            {/* Visual Flare */}
            <div className="absolute top-[20%] right-[-5%] w-[500px] h-[500px] bg-red-500/5 rotate-45 blur-[80px] pointer-events-none"></div>
        </div>
    );
}
