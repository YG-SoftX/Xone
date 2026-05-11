import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Clock, Check, X, RotateCcw, Trash2 } from 'lucide-react';

export default function VersionHistory({ document: doc, versions }) {
    const restoreVersion = (versionId) => {
        if (!confirm('Restore this version? Current content will be saved as a new version.')) return;
        router.post(route('versions.restore', { id: doc.id, versionId }));
    };

    const deleteVersion = (versionId) => {
        if (!confirm('Delete this version?')) return;
        router.delete(route('versions.delete', { id: doc.id, versionId }));
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title={`Version History - ${doc.title}`} />

            <header className="bg-white border-b px-6 py-4">
                <div className="max-w-5xl mx-auto flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route('documents.show', doc.id)} className="p-2 rounded hover:bg-gray-100">
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold text-gray-900">{doc.title}</h1>
                            <p className="text-sm text-gray-500">Version History ({versions?.length || 0} versions)</p>
                        </div>
                    </div>
                </div>
            </header>

            <main className="max-w-5xl mx-auto p-6">
                {versions?.length === 0 ? (
                    <div className="text-center py-16 bg-white rounded-xl border">
                        <Clock className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No versions yet</h3>
                        <p className="text-gray-500">Versions are created automatically when you save</p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {versions?.map((version, idx) => (
                            <div key={version.id} className="bg-white rounded-xl border p-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-4">
                                        <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <span className="text-sm font-bold text-blue-600">v{version.version_number}</span>
                                        </div>
                                        <div>
                                            <p className="font-medium text-gray-900">
                                                {idx === 0 ? 'Current Version' : `Version ${version.version_number}`}
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                {version.user_name} • {version.created_at}
                                            </p>
                                            {version.change_summary && (
                                                <p className="text-sm text-gray-600 mt-1">{version.change_summary}</p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {idx > 0 && (
                                            <>
                                                <button
                                                    onClick={() => restoreVersion(version.id)}
                                                    className="flex items-center gap-2 px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700"
                                                >
                                                    <RotateCcw className="h-4 w-4" />
                                                    Restore
                                                </button>
                                                <button
                                                    onClick={() => deleteVersion(version.id)}
                                                    className="p-1.5 rounded hover:bg-red-50 text-red-600"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </main>
        </div>
    );
}
