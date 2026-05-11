import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { FileSpreadsheet, Plus, Search, Grid, List, Trash2, Copy, Download, Archive, LayoutTemplate } from 'lucide-react';
import { motion } from 'framer-motion';

export default function SpreadsheetHome({ spreadsheets, templates }) {
    const [search, setSearch] = useState('');
    const [view, setView] = useState('grid');
    const [activeTab, setActiveTab] = useState('all');

    const filtered = (spreadsheets || []).filter(s =>
        search === '' || s.title.toLowerCase().includes(search.toLowerCase())
    );

    const createSpreadsheet = () => {
        router.post(route('spreadsheets.store'), { title: 'Untitled Spreadsheet' });
    };

    const createFromTemplate = (templateId) => {
        router.post(route('templates.apply', templateId));
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title="YG Xcel" />

            <header className="bg-white border-b px-6 py-4">
                <div className="max-w-7xl mx-auto flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <h1 className="text-2xl font-bold text-[#107C41]">YG Xcel</h1>
                        <nav className="flex gap-1 ml-4">
                            {[
                                { key: 'all', label: 'All' },
                                { key: 'recent', label: 'Recent' },
                                { key: 'archived', label: 'Archived' },
                                { key: 'templates', label: 'Templates' },
                            ].map(tab => (
                                <button key={tab.key}
                                    onClick={() => setActiveTab(tab.key)}
                                    className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${
                                        activeTab === tab.key ? 'bg-green-50 text-[#107C41]' : 'text-gray-600 hover:bg-gray-50'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </nav>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input type="text" placeholder="Search spreadsheets..." value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-10 pr-4 py-2 border rounded-lg w-64 focus:outline-none focus:ring-2 focus:ring-green-500" />
                        </div>
                    </div>
                </div>
            </header>

            <main className="max-w-7xl mx-auto p-6">
                {activeTab === 'templates' ? (
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Template Gallery</h2>
                        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            {(templates || []).map(template => (
                                <motion.div key={template.id} whileHover={{ scale: 1.03 }}
                                    className="bg-white rounded-xl border hover:shadow-lg cursor-pointer overflow-hidden"
                                    onClick={() => createFromTemplate(template.id)}>
                                    <div className="h-36 bg-gradient-to-br from-green-50 to-emerald-50 flex items-center justify-center">
                                        <FileSpreadsheet className="h-14 w-14 text-green-500" />
                                    </div>
                                    <div className="p-4">
                                        <h3 className="font-medium text-gray-900">{template.name}</h3>
                                        <p className="text-xs text-gray-500 mt-1">{template.description}</p>
                                        <span className="inline-block mt-2 text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full">{template.category}</span>
                                    </div>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="mb-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Start a new spreadsheet</h2>
                            <div className="flex gap-4">
                                <motion.button whileHover={{ scale: 1.05 }} onClick={createSpreadsheet}
                                    className="w-44 h-44 bg-white rounded-xl border-2 border-dashed border-gray-300 hover:border-green-500 flex flex-col items-center justify-center gap-3 transition-colors">
                                    <div className="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                                        <Plus className="h-7 w-7 text-[#107C41]" />
                                    </div>
                                    <span className="text-sm font-medium text-gray-700">Blank Spreadsheet</span>
                                </motion.button>
                                {(templates || []).filter(t => t.is_system).slice(0, 3).map(template => (
                                    <motion.button key={template.id} whileHover={{ scale: 1.05 }}
                                        className="w-44 h-44 bg-white rounded-xl border hover:border-green-500 hover:shadow-lg flex flex-col items-center justify-center gap-3 transition-all"
                                        onClick={() => createFromTemplate(template.id)}>
                                        <div className="w-14 h-14 bg-emerald-100 rounded-full flex items-center justify-center">
                                            <LayoutTemplate className="h-7 w-7 text-emerald-600" />
                                        </div>
                                        <span className="text-sm font-medium text-gray-700 text-center px-2">{template.name}</span>
                                    </motion.button>
                                ))}
                            </div>
                        </div>

                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Recent spreadsheets</h2>

                        {filtered.length === 0 ? (
                            <div className="text-center py-16 bg-white rounded-xl border">
                                <FileSpreadsheet className="mx-auto h-14 w-14 text-gray-400 mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">No spreadsheets yet</h3>
                                <p className="text-gray-500 mb-4">Create your first spreadsheet to get started</p>
                                <button onClick={createSpreadsheet}
                                    className="inline-flex items-center gap-2 bg-[#107C41] text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                    <Plus className="h-4 w-4" /> Create Spreadsheet
                                </button>
                            </div>
                        ) : view === 'grid' ? (
                            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                {filtered.map(s => (
                                    <motion.div key={s.id} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }}
                                        className="bg-white rounded-xl border hover:shadow-lg transition-shadow group">
                                        <Link href={route('spreadsheets.show', s.id)} className="block">
                                            <div className="h-32 bg-gradient-to-br from-green-50 to-emerald-50 flex items-center justify-center">
                                                <FileSpreadsheet className="h-14 w-14 text-green-400" />
                                            </div>
                                            <div className="p-3">
                                                <h3 className="font-medium text-gray-900 truncate">{s.title}</h3>
                                                <p className="text-xs text-gray-500 mt-1">{s.updated}</p>
                                            </div>
                                        </Link>
                                        <div className="px-3 pb-3 flex items-center justify-between">
                                            <span className={`text-xs px-2 py-0.5 rounded-full ${
                                                s.status === 'published' ? 'bg-green-100 text-green-700' :
                                                s.status === 'archived' ? 'bg-gray-100 text-gray-700' : 'bg-yellow-100 text-yellow-700'
                                            }`}>{s.status}</span>
                                            <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button onClick={() => router.post(route('spreadsheets.duplicate', s.id))} className="p-1 rounded hover:bg-gray-100"><Copy className="h-3.5 w-3.5 text-gray-600" /></button>
                                                <button onClick={() => router.post(route('spreadsheets.archive', s.id))} className="p-1 rounded hover:bg-gray-100"><Archive className="h-3.5 w-3.5 text-gray-600" /></button>
                                                <button onClick={() => router.delete(route('spreadsheets.delete', s.id))} className="p-1 rounded hover:bg-gray-100"><Trash2 className="h-3.5 w-3.5 text-gray-600" /></button>
                                            </div>
                                        </div>
                                    </motion.div>
                                ))}
                            </div>
                        ) : (
                            <div className="bg-white rounded-xl border overflow-hidden">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {filtered.map(s => (
                                            <tr key={s.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4">
                                                    <Link href={route('spreadsheets.show', s.id)} className="flex items-center gap-3">
                                                        <FileSpreadsheet className="h-5 w-5 text-[#107C41]" />
                                                        <span className="font-medium text-gray-900">{s.title}</span>
                                                    </Link>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`text-xs px-2 py-0.5 rounded-full ${
                                                        s.status === 'published' ? 'bg-green-100 text-green-700' :
                                                        s.status === 'archived' ? 'bg-gray-100 text-gray-700' : 'bg-yellow-100 text-yellow-700'
                                                    }`}>{s.status}</span>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500">{s.updated}</td>
                                                <td className="px-6 py-4 text-sm space-x-2">
                                                    <button onClick={() => router.post(route('spreadsheets.duplicate', s.id))} className="text-blue-600 hover:text-blue-900"><Copy className="h-4 w-4" /></button>
                                                    <button onClick={() => router.post(route('spreadsheets.archive', s.id))} className="text-gray-600 hover:text-gray-900"><Archive className="h-4 w-4" /></button>
                                                    <button onClick={() => router.delete(route('spreadsheets.delete', s.id))} className="text-red-600 hover:text-red-900"><Trash2 className="h-4 w-4" /></button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </>
                )}
            </main>
        </div>
    );
}
