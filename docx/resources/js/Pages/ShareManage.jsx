import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Share2, Mail, X, Check, ChevronDown, Copy, Link2 } from 'lucide-react';

export default function ShareManage({ document: doc, shares }) {
    const [email, setEmail] = useState('');
    const [permission, setPermission] = useState('view');
    const [sharing, setSharing] = useState(false);

    const handleShare = (e) => {
        e.preventDefault();
        if (!email) return;
        setSharing(true);
        router.post(route('shares.store', doc.id), {
            email,
            permission,
        }, {
            onSuccess: () => setEmail(''),
            onFinish: () => setSharing(false),
        });
    };

    const updatePermission = (shareId, newPerm) => {
        router.patch(route('shares.update', shareId), { permission: newPerm });
    };

    const revokeShare = (shareId) => {
        router.delete(route('shares.revoke', shareId));
    };

    const copyLink = () => {
        const url = window.location.origin + route('documents.show', doc.id);
        navigator.clipboard.writeText(url);
        alert('Link copied!');
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title={`Share - ${doc.title}`} />

            <header className="bg-white border-b px-6 py-4">
                <div className="max-w-3xl mx-auto flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route('documents.show', doc.id)} className="p-2 rounded hover:bg-gray-100">
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold text-gray-900">Share "{doc.title}"</h1>
                        </div>
                    </div>
                </div>
            </header>

            <main className="max-w-3xl mx-auto p-6 space-y-6">
                {/* Share Link */}
                <div className="bg-white rounded-xl border p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">Share Link</h3>
                    <div className="flex items-center gap-2">
                        <div className="flex-1 flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2">
                            <Link2 className="h-4 w-4 text-gray-400" />
                            <span className="text-sm text-gray-600 truncate">
                                {window.location.origin}/documents/{doc.id}
                            </span>
                        </div>
                        <button
                            onClick={copyLink}
                            className="flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200"
                        >
                            <Copy className="h-4 w-4" />
                            Copy
                        </button>
                    </div>
                </div>

                {/* Share with People */}
                <div className="bg-white rounded-xl border p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">Share with people</h3>
                    <form onSubmit={handleShare} className="flex gap-2">
                        <div className="flex-1 flex items-center gap-2 border rounded-lg px-3">
                            <Mail className="h-4 w-4 text-gray-400" />
                            <input
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="Enter email address"
                                className="flex-1 py-2 border-none focus:outline-none text-sm"
                                required
                            />
                        </div>
                        <select
                            value={permission}
                            onChange={(e) => setPermission(e.target.value)}
                            className="border rounded-lg px-3 py-2 text-sm"
                        >
                            <option value="view">Can view</option>
                            <option value="comment">Can comment</option>
                            <option value="edit">Can edit</option>
                        </select>
                        <button
                            type="submit"
                            disabled={sharing}
                            className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50"
                        >
                            <Share2 className="h-4 w-4" />
                            {sharing ? 'Sharing...' : 'Share'}
                        </button>
                    </form>
                </div>

                {/* People with Access */}
                <div className="bg-white rounded-xl border p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">People with access</h3>
                    <div className="space-y-3">
                        {/* Owner */}
                        <div className="flex items-center justify-between py-2 border-b border-gray-100">
                            <div className="flex items-center gap-3">
                                <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                    O
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900">You (Owner)</p>
                                    <p className="text-xs text-gray-500">{doc.owner_email}</p>
                                </div>
                            </div>
                            <span className="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full">Owner</span>
                        </div>

                        {/* Shared Users */}
                        {shares?.map(share => (
                            <div key={share.id} className="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <div className="flex items-center gap-3">
                                    <div className="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 text-sm font-bold">
                                        {(share.email || share.user_name)?.[0]?.toUpperCase()}
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{share.email || share.user_name}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <select
                                        value={share.permission}
                                        onChange={(e) => updatePermission(share.id, e.target.value)}
                                        className="text-sm border rounded px-2 py-1"
                                    >
                                        <option value="view">Can view</option>
                                        <option value="comment">Can comment</option>
                                        <option value="edit">Can edit</option>
                                    </select>
                                    <button
                                        onClick={() => revokeShare(share.id)}
                                        className="p-1 rounded hover:bg-red-50 text-red-600"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        ))}

                        {shares?.length === 0 && (
                            <p className="text-sm text-gray-500 text-center py-4">No one else has access yet</p>
                        )}
                    </div>
                </div>
            </main>
        </div>
    );
}
