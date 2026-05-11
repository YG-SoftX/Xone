import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    FileText, FolderPlus, Search, Grid, List as ListIcon, Plus,
    MoreVertical, Share2, Trash2, Download, Clock, FileEdit,
    Table2, Presentation, LayoutTemplate, FolderOpen, Star,
    Shield, Wallet, Brain, Inbox, ThLarge, Bell, ArrowUpRight
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

export default function DocumentHome({ documents, folders, templates }) {
    const [view, setView] = useState('grid');
    const [search, setSearch] = useState('');
    const [activeTab, setActiveTab] = useState('documents');

    const filteredDocs = (documents || []).filter(d =>
        search === '' || d.title.toLowerCase().includes(search.toLowerCase())
    );

    const createDocument = () => {
        router.post(route('documents.store'), {
            title: 'Untitled Document',
            document_type: 'document',
        });
    };

    return (
        <div className="flex h-screen overflow-hidden bg-[#020617] text-white font-['Inter']">
            <Head title="Sovereign Documents — YGXONE" />

            {/* Universal Sidebar: The Backbone of the Empire */}
            <aside className="hidden md:flex flex-col w-72 bg-white/[0.03] backdrop-blur-2xl border-r border-white/10 z-50">
                <div className="p-8">
                    <Link href="/" className="flex items-center space-x-3 group">
                        <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                            <FileText className="text-white h-5 w-5" />
                        </div>
                        <span className="text-2xl font-black text-white tracking-tighter uppercase">YGXONE</span>
                    </Link>
                </div>

                <div className="px-6 mb-6">
                    <button onClick={createDocument} className="w-full py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-black rounded-2xl hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-blue-600/20 uppercase tracking-widest text-[10px]">
                        <Plus className="inline-block mr-2 h-4 w-4" /> New Document
                    </button>
                </div>

                <nav className="flex-1 px-4 space-y-2 mt-4 overflow-y-auto">
                    <p className="text-[10px] font-black text-gray-500 uppercase tracking-widest px-4 mb-4">Productivity Nodes</p>
                    
                    <button onClick={() => setActiveTab('documents')} className={`w-full flex items-center px-4 py-4 rounded-2xl font-bold transition-all ${activeTab === 'documents' ? 'bg-blue-500/10 border-l-3 border-blue-500 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white'}`}>
                        <FileText className="w-8 h-5" />
                        <span className="text-sm tracking-tight">My Documents</span>
                    </button>

                    <button onClick={() => setActiveTab('templates')} className={`w-full flex items-center px-4 py-4 rounded-2xl font-bold transition-all ${activeTab === 'templates' ? 'bg-purple-500/10 border-l-3 border-purple-500 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white'}`}>
                        <LayoutTemplate className="w-8 h-5" />
                        <span className="text-sm tracking-tight">Template Gallery</span>
                    </button>

                    <div className="pt-8">
                        <p className="text-[10px] font-black text-gray-500 uppercase tracking-widest px-4 mb-4">Ecosystem Bridge</p>
                        <a href="https://account.ygxone.com/dashboard" className="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all hover:bg-white/5 hover:text-white">
                            <ThLarge className="w-8 h-5 text-blue-400" />
                            <span className="text-sm tracking-tight">Command Center</span>
                        </a>
                        <a href="https://pay.ygxone.com" className="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all hover:bg-white/5 hover:text-white">
                            <Wallet className="w-8 h-5 text-purple-400" />
                            <span className="text-sm tracking-tight">YG Pay Authority</span>
                        </a>
                    </div>
                </nav>

                <div className="p-6 border-t border-white/5 bg-black/20 mt-auto">
                    <div className="flex items-center space-x-4">
                        <div className="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-black border border-white/10 shadow-lg">Y</div>
                        <div className="flex-1 min-w-0">
                            <p className="text-xs font-black text-white truncate">Sovereign Elite</p>
                            <p className="text-[10px] text-blue-400 font-bold uppercase tracking-widest">Active Node</p>
                        </div>
                    </div>
                </div>
            </aside>

            {/* Main Workspace */}
            <div className="flex-1 flex flex-col overflow-hidden relative">
                
                {/* Universal Header: The Command Bar */}
                <header className="h-20 bg-white/[0.02] backdrop-blur-2xl border-b border-white/5 flex items-center justify-between px-8 z-40">
                    <div className="flex items-center flex-1 space-x-8">
                        <div className="relative w-full max-w-xl">
                            <Search className="absolute left-5 top-1/2 -translate-y-1/2 text-gray-500 h-4 w-4" />
                            <input 
                                type="text" 
                                placeholder="Search document archives..." 
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full bg-white/5 border border-white/10 rounded-2xl py-3 pl-12 pr-6 text-sm text-white placeholder-gray-600 focus:border-blue-500/50 outline-none transition-all"
                            />
                        </div>
                    </div>

                    <div className="flex items-center space-x-6 ml-8">
                        <button className="relative w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-all">
                            <Bell className="h-4 w-4" />
                            <span className="absolute top-2 right-2 w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                        </button>
                    </div>
                </header>

                {/* Workspace Content */}
                <main className="flex-1 overflow-y-auto p-8 md:p-12">
                    <AnimatePresence mode="wait">
                        {activeTab === 'documents' && (
                            <motion.div 
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                exit={{ opacity: 0, y: -20 }}
                                key="docs"
                            >
                                <div className="flex items-center justify-between mb-10">
                                    <h2 className="text-3xl font-black text-white tracking-tighter uppercase">My Documents</h2>
                                    <div className="flex bg-white/5 rounded-xl p-1 border border-white/10">
                                        <button onClick={() => setView('grid')} className={`p-2 rounded-lg transition-all ${view === 'grid' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500 hover:text-white'}`}>
                                            <Grid className="h-4 w-4" />
                                        </button>
                                        <button onClick={() => setView('list')} className={`p-2 rounded-lg transition-all ${view === 'list' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500 hover:text-white'}`}>
                                            <ListIcon className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                {filteredDocs.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-32 bg-white/2 border border-white/5 rounded-[3rem] border-dashed">
                                        <FileText className="h-16 w-16 text-gray-700 mb-6" />
                                        <h3 className="text-xl font-bold text-gray-400 mb-2 uppercase tracking-tight">No Documents Found</h3>
                                        <p className="text-gray-600 text-sm mb-8">Initialize your first sovereign document.</p>
                                        <button onClick={createDocument} className="px-10 py-4 bg-white/5 border border-white/10 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-white/10 transition-all">Create Now</button>
                                    </div>
                                ) : (
                                    <div className={view === 'grid' ? "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" : "space-y-4"}>
                                        {filteredDocs.map(doc => (
                                            <motion.div
                                                key={doc.id}
                                                whileHover={{ y: -5 }}
                                                className={`group relative ${view === 'grid' ? 'bg-white/[0.02] border border-white/5 rounded-[2.5rem] overflow-hidden' : 'bg-white/[0.02] border border-white/5 rounded-2xl p-4 flex items-center justify-between'}`}
                                            >
                                                <Link href={route('documents.show', doc.id)} className="flex-1">
                                                    {view === 'grid' && (
                                                        <div className="h-48 bg-gradient-to-br from-blue-500/5 to-purple-500/5 flex items-center justify-center border-b border-white/5">
                                                            <FileText className="h-16 w-16 text-blue-500/40 group-hover:text-blue-500/60 transition-all" />
                                                        </div>
                                                    )}
                                                    <div className="p-6">
                                                        <div className="flex items-center gap-3 mb-2">
                                                            <FileText className="h-4 w-4 text-blue-500" />
                                                            <h3 className="font-bold text-white truncate text-sm uppercase tracking-tight">{doc.title}</h3>
                                                        </div>
                                                        <div className="flex items-center justify-between">
                                                            <p className="text-[10px] text-gray-500 font-bold uppercase tracking-widest">{doc.updated}</p>
                                                            <span className="text-[8px] px-2 py-1 bg-green-500/10 text-green-500 border border-green-500/20 rounded-full font-black uppercase">{doc.status}</span>
                                                        </div>
                                                    </div>
                                                </Link>
                                            </motion.div>
                                        ))}
                                    </div>
                                )}
                            </motion.div>
                        )}
                        
                        {activeTab === 'templates' && (
                            <motion.div 
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                exit={{ opacity: 0, y: -20 }}
                                key="templates"
                            >
                                <h2 className="text-3xl font-black text-white tracking-tighter uppercase mb-10">Template Gallery</h2>
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                                    {(templates || []).map(template => (
                                        <motion.div
                                            key={template.id}
                                            whileHover={{ y: -5 }}
                                            className="bg-white/[0.02] border border-white/5 rounded-[2.5rem] overflow-hidden cursor-pointer"
                                        >
                                            <div className="h-48 bg-gradient-to-br from-purple-500/5 to-blue-500/5 flex items-center justify-center border-b border-white/5">
                                                <LayoutTemplate className="h-16 w-16 text-purple-500/40" />
                                            </div>
                                            <div className="p-6 text-center">
                                                <h3 className="font-bold text-white text-sm uppercase tracking-tight mb-1">{template.name}</h3>
                                                <p className="text-[10px] text-gray-500 font-bold uppercase tracking-widest">{template.category}</p>
                                            </div>
                                        </motion.div>
                                    ))}
                                </div>
                            </motion.div>
                        )}
                    </AnimatePresence>
                </main>

                {/* Footer Authority */}
                <footer className="p-8 border-t border-white/5 bg-black/20">
                    <div className="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div className="flex items-center space-x-6">
                            <a href="#" className="text-[10px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors">Protocol Status</a>
                            <a href="#" className="text-[10px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors">Encryption Audit</a>
                        </div>
                        <p className="text-[10px] font-black text-gray-700 uppercase tracking-[0.3em]">
                            &copy; {new Date().getFullYear()} YGXONE EMPIRE. ALL AUTHORITY RESERVED.
                        </p>
                    </div>
                </footer>
            </div>
        </div>
    );
}
