import { useState, useCallback, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Bold, Italic, Underline, Strikethrough, AlignLeft, AlignCenter, AlignRight,
    AlignJustify, List, ListOrdered, Type, Heading1, Heading2, Quote, Code,
    Image, Link2, Table, Undo, Redo, Download, Share2, MessageSquare,
    Clock, FileText, Save, Check, X, MoreVertical, Printer, FileEdit,
    FileSpreadsheet, Presentation, Trash2, FolderPlus, Search
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

const toolbarGroups = [
    {
        label: 'History',
        items: [
            { icon: Undo, action: 'undo', label: 'Undo (Ctrl+Z)' },
            { icon: Redo, action: 'redo', label: 'Redo (Ctrl+Y)' },
        ]
    },
    {
        label: 'Text Style',
        items: [
            { icon: Bold, action: 'bold', label: 'Bold (Ctrl+B)' },
            { icon: Italic, action: 'italic', label: 'Italic (Ctrl+I)' },
            { icon: Underline, action: 'underline', label: 'Underline (Ctrl+U)' },
            { icon: Strikethrough, action: 'strikeThrough', label: 'Strikethrough' },
        ]
    },
    {
        label: 'Headings',
        items: [
            { icon: Type, action: 'removeFormat', label: 'Normal text' },
            { icon: Heading1, action: 'formatBlock', value: 'h1', label: 'Heading 1' },
            { icon: Heading2, action: 'formatBlock', value: 'h2', label: 'Heading 2' },
            { icon: Quote, action: 'formatBlock', value: 'blockquote', label: 'Quote' },
            { icon: Code, action: 'formatBlock', value: 'pre', label: 'Code block' },
        ]
    },
    {
        label: 'Alignment',
        items: [
            { icon: AlignLeft, action: 'justifyLeft', label: 'Align left' },
            { icon: AlignCenter, action: 'justifyCenter', label: 'Align center' },
            { icon: AlignRight, action: 'justifyRight', label: 'Align right' },
            { icon: AlignJustify, action: 'justifyFull', label: 'Justify' },
        ]
    },
    {
        label: 'Lists',
        items: [
            { icon: List, action: 'insertUnorderedList', label: 'Bullet list' },
            { icon: ListOrdered, action: 'insertOrderedList', label: 'Numbered list' },
        ]
    },
    {
        label: 'Insert',
        items: [
            { icon: Link2, action: 'createLink', label: 'Insert link' },
            { icon: Image, action: 'insertImage', label: 'Insert image' },
            { icon: Table, action: 'insertTable', label: 'Insert table' },
        ]
    },
];

export default function DocumentEditor({ document: doc, canEdit, canComment }) {
    const [content, setContent] = useState(doc.content || '<p>Start typing...</p>');
    const [title, setTitle] = useState(doc.title || 'Untitled Document');
    const [showComments, setShowComments] = useState(false);
    const [commentText, setCommentText] = useState('');
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(true);
    const [showToolbar, setShowToolbar] = useState(true);
    const [selection, setSelection] = useState({});
    const comments = doc.comments || [];
    const suggestions = doc.suggestions || [];

    const execCommand = useCallback((command, value = null) => {
        document.execCommand(command, false, value);
        setSaved(false);
    }, []);

    const handleContentChange = useCallback((e) => {
        setContent(e.target.innerHTML);
        setSaved(false);
    }, []);

    const saveDocument = useCallback(() => {
        setSaving(true);
        router.patch(route('documents.update', doc.id), {
            content: content,
            title: title,
            word_count: countWords(content),
            page_count: estimatePages(content),
        }, {
            onSuccess: () => setSaved(true),
            onFinish: () => setSaving(false),
        });
    }, [content, title, doc.id]);

    // Auto-save every 30 seconds
    useEffect(() => {
        const interval = setInterval(() => {
            if (!saved) saveDocument();
        }, 30000);
        return () => clearInterval(interval);
    }, [saved, saveDocument]);

    // Keyboard shortcuts
    useEffect(() => {
        const handler = (e) => {
            if (e.ctrlKey || e.metaKey) {
                if (e.key === 's') {
                    e.preventDefault();
                    saveDocument();
                }
                if (e.key === 'p') {
                    e.preventDefault();
                    window.print();
                }
            }
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [saveDocument]);

    const addComment = () => {
        if (!commentText.trim()) return;
        router.post(route('comments.store', doc.id), {
            content: commentText,
        }, {
            onSuccess: () => setCommentText(''),
        });
    };

    return (
        <div className="min-h-screen bg-gray-100 flex flex-col">
            <Head title={title} />

            {/* Top Bar */}
            <header className="bg-white border-b px-4 py-2 flex items-center justify-between sticky top-0 z-50">
                <div className="flex items-center gap-4 flex-1">
                    <a href={route('home')} className="text-gray-600 hover:text-gray-900">
                        <FileText className="h-6 w-6" />
                    </a>
                    <div className="flex-1 max-w-lg">
                        <input
                            type="text"
                            value={title}
                            onChange={(e) => { setTitle(e.target.value); setSaved(false); }}
                            className="w-full text-lg font-medium border-none focus:outline-none focus:ring-0 bg-transparent"
                            placeholder="Untitled document"
                        />
                    </div>
                    <div className="flex items-center gap-2 text-sm text-gray-500">
                        {saving ? (
                            <span className="flex items-center gap-1">Saving...</span>
                        ) : saved ? (
                            <span className="flex items-center gap-1"><Check className="h-3 w-3" /> Saved</span>
                        ) : (
                            <span className="flex items-center gap-1"><FileEdit className="h-3 w-3" /> Unsaved changes</span>
                        )}
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        onClick={() => setShowComments(!showComments)}
                        className={`p-2 rounded hover:bg-gray-100 ${showComments ? 'bg-blue-50 text-blue-600' : ''}`}
                    >
                        <MessageSquare className="h-5 w-5" />
                        {comments.length > 0 && (
                            <span className="absolute -top-1 -right-1 bg-blue-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                                {comments.length}
                            </span>
                        )}
                    </button>
                    <button
                        onClick={() => router.get(route('documents.export', { id: doc.id, format: 'pdf' }))}
                        className="p-2 rounded hover:bg-gray-100"
                    >
                        <Download className="h-5 w-5" />
                    </button>
                    <button
                        onClick={() => router.get(route('shares.index', doc.id))}
                        className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700"
                    >
                        <Share2 className="h-4 w-4" />
                        Share
                    </button>
                </div>
            </header>

            {/* Toolbar */}
            {showToolbar && canEdit && (
                <div className="bg-white border-b px-4 py-1 flex items-center gap-1 overflow-x-auto">
                    {toolbarGroups.map((group, idx) => (
                        <div key={idx} className="flex items-center">
                            {group.items.map((item, i) => {
                                const Icon = item.icon;
                                return (
                                    <button
                                        key={i}
                                        onClick={() => {
                                            if (item.action === 'createLink') {
                                                const url = prompt('Enter URL:');
                                                if (url) execCommand(item.action, url);
                                            } else if (item.action === 'insertImage') {
                                                const url = prompt('Enter image URL:');
                                                if (url) execCommand(item.action, url);
                                            } else if (item.action === 'insertTable') {
                                                execCommand('insertHTML', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;margin:10px 0;"><tr><td>Cell</td><td>Cell</td></tr><tr><td>Cell</td><td>Cell</td></tr></table>');
                                            } else if (item.action === 'formatBlock') {
                                                execCommand(item.action, `<${item.value}>`);
                                            } else {
                                                execCommand(item.action, item.value);
                                            }
                                        }}
                                        className="p-1.5 rounded hover:bg-gray-100 text-gray-700"
                                        title={item.label}
                                    >
                                        <Icon className="h-4 w-4" />
                                    </button>
                                );
                            })}
                            {idx < toolbarGroups.length - 1 && (
                                <div className="w-px h-6 bg-gray-200 mx-1" />
                            )}
                        </div>
                    ))}

                    {/* Font size & family */}
                    <div className="w-px h-6 bg-gray-200 mx-1" />
                    <select
                        onChange={(e) => execCommand('fontSize', e.target.value)}
                        className="text-sm border rounded px-2 py-1"
                    >
                        <option value="3">12pt</option>
                        <option value="1">8pt</option>
                        <option value="2">10pt</option>
                        <option value="4">14pt</option>
                        <option value="5">18pt</option>
                        <option value="6">24pt</option>
                        <option value="7">36pt</option>
                    </select>
                    <select
                        onChange={(e) => execCommand('fontName', e.target.value)}
                        className="text-sm border rounded px-2 py-1 ml-1"
                    >
                        <option value="Arial">Arial</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Courier New">Courier New</option>
                        <option value="Georgia">Georgia</option>
                        <option value="Verdana">Verdana</option>
                        <option value="Calibri">Calibri</option>
                    </select>
                </div>
            )}

            {/* Main Content Area */}
            <div className="flex flex-1 overflow-hidden">
                {/* Document Editor */}
                <div className="flex-1 overflow-y-auto bg-gray-100 p-8">
                    <div className="max-w-[850px] mx-auto bg-white shadow-lg min-h-[1100px] p-[96px]">
                        <div
                            contentEditable={canEdit}
                            suppressContentEditableWarning
                            onInput={handleContentChange}
                            dangerouslySetInnerHTML={{ __html: content }}
                            className="outline-none prose prose-lg max-w-none"
                            style={{ minHeight: '800px' }}
                        />
                    </div>
                </div>

                {/* Comments Sidebar */}
                <AnimatePresence>
                    {showComments && canComment && (
                        <motion.div
                            initial={{ width: 0, opacity: 0 }}
                            animate={{ width: 320, opacity: 1 }}
                            exit={{ width: 0, opacity: 0 }}
                            className="bg-white border-l overflow-y-auto"
                        >
                            <div className="p-4 border-b">
                                <h3 className="font-semibold text-gray-900">Comments ({comments.length})</h3>
                            </div>

                            <div className="p-4 space-y-3">
                                {comments.filter(c => !c.resolved).map(comment => (
                                    <div key={comment.id} className="bg-gray-50 rounded-lg p-3">
                                        <div className="flex items-center justify-between mb-1">
                                            <span className="text-sm font-medium text-gray-900">
                                                {comment.user_name || 'User'}
                                            </span>
                                            <span className="text-xs text-gray-500">{comment.time}</span>
                                        </div>
                                        <p className="text-sm text-gray-700">{comment.content}</p>
                                    </div>
                                ))}

                                {comments.filter(c => !c.resolved).length === 0 && (
                                    <p className="text-sm text-gray-500 text-center py-8">No comments yet</p>
                                )}
                            </div>

                            <div className="p-4 border-t">
                                <textarea
                                    value={commentText}
                                    onChange={(e) => setCommentText(e.target.value)}
                                    placeholder="Add a comment..."
                                    className="w-full border rounded-lg p-2 text-sm resize-none"
                                    rows="3"
                                />
                                <button
                                    onClick={addComment}
                                    disabled={!commentText.trim()}
                                    className="mt-2 w-full bg-blue-600 text-white py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50"
                                >
                                    Comment
                                </button>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>
            </div>
        </div>
    );
}

function countWords(html) {
    const text = html.replace(/<[^>]*>/g, ' ');
    return text.trim().split(/\s+/).filter(w => w.length > 0).length;
}

function estimatePages(html) {
    const words = countWords(html);
    return Math.max(1, Math.ceil(words / 500));
}
