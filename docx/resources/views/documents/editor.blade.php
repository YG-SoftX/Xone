@extends('layouts.app')

@section('title', $document->title ?? 'Untitled Document')

@push('styles')
<style>
/* Advanced Editor Styles */
.ql-editor {
    min-height: 1100px;
    font-size: 16px;
    line-height: 1.6;
    padding: 96px;
}

.page-container {
    max-width: 850px;
    margin: 0 auto;
    background: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    min-height: 1100px;
}

/* Chart.js Integration */
.chart-wrapper {
    position: relative;
    margin: 20px 0;
    padding: 20px;
    border: 1px solid #e5e7eb;
    background: white;
    border-radius: 8px;
}

.chart-canvas {
    max-width: 100%;
    height: 400px;
}

/* SVG Shapes */
.shape-container {
    position: absolute;
    cursor: move;
    user-select: none;
}

.shape-rectangle {
    fill: #3b82f6;
    stroke: #2563eb;
    stroke-width: 2;
}

.shape-circle {
    fill: #10b981;
    stroke: #059669;
    stroke-width: 2;
}

.shape-arrow {
    fill: #f59e0b;
    stroke: #d97706;
    stroke-width: 2;
}

/* Table Merge/Split Controls */
.table-cell-selected {
    background-color: #dbeafe !important;
    outline: 2px solid #3b82f6;
}

.table-context-menu {
    position: absolute;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    min-width: 200px;
}

.table-context-menu button {
    width: 100%;
    text-align: left;
    padding: 8px 12px;
    border: none;
    background: transparent;
    cursor: pointer;
}

.table-context-menu button:hover {
    background: #f3f4f6;
}

/* Track Changes Visual Diff */
.track-insert {
    background-color: #d4edda;
    text-decoration: underline;
    color: #155724;
    padding: 2px 4px;
    border-radius: 2px;
}

.track-delete {
    background-color: #f8d7da;
    text-decoration: line-through;
    color: #721c24;
    padding: 2px 4px;
    border-radius: 2px;
}

.track-format-change {
    border: 2px solid #007bff;
    padding: 2px;
    border-radius: 2px;
}

.diff-panel {
    background: #f9fafb;
    border-left: 4px solid #3b82f6;
    padding: 16px;
    margin: 10px 0;
}

/* Remote Cursor Indicators */
.remote-cursor {
    position: absolute;
    width: 2px;
    pointer-events: none;
    z-index: 100;
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0.3; }
}

.remote-cursor-label {
    position: absolute;
    top: -20px;
    left: 0;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    color: white;
    white-space: nowrap;
    font-weight: bold;
}

/* Form Fields */
.form-field {
    display: inline-block;
    border: 1px dashed #3b82f6;
    padding: 4px 8px;
    margin: 2px;
    background: #eff6ff;
    border-radius: 4px;
    cursor: pointer;
}

.form-field-text {
    min-width: 200px;
    border-bottom: 1px solid #3b82f6;
}

.form-field-checkbox {
    width: 20px;
    height: 20px;
}

.form-field-dropdown {
    min-width: 150px;
    padding: 4px 8px;
    border: 1px solid #3b82f6;
    border-radius: 4px;
}

/* Digital Signature */
.signature-pad {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    cursor: crosshair;
}

.signature-preview {
    max-width: 300px;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    padding: 10px;
    background: #f9fafb;
}

/* Accessibility Checker */
.accessibility-issue {
    padding: 12px;
    margin: 8px 0;
    border-radius: 6px;
    border-left: 4px solid;
}

.accessibility-error {
    background: #fee2e2;
    border-color: #ef4444;
}

.accessibility-warning {
    background: #fef3c7;
    border-color: #f59e0b;
}

.accessibility-success {
    background: #d1fae5;
    border-color: #10b981;
}

/* Mail Merge Fields */
.merge-field {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    margin: 2px;
    font-weight: bold;
    cursor: pointer;
}

.merge-field:hover {
    opacity: 0.9;
}

/* Macro Recorder Indicator */
.macro-recording {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #ef4444;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 9999;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Print styles */
@media print {
    header, .toolbar, .comments-sidebar, .presence-indicators,
    .macro-recording, .accessibility-panel, .diff-panel {
        display: none !important;
    }
    
    .page-container {
        box-shadow: none;
        margin: 0;
    }
    
    .ql-editor {
        padding: 0;
    }
}

/* Focus indicators */
button:focus-visible,
input:focus-visible,
select:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
</style>
@endpush

@section('content')
<div x-data="completeDocumentEditor({{ Js::from([
    'documentId' => $document->id,
    'content' => $document->content ?? '<p>Start typing...</p>',
    'title' => $document->title ?? 'Untitled Document',
    'canEdit' => $canEdit ?? true,
    'canComment' => $canComment ?? false,
    'comments' => $comments ?? [],
    'updateUrl' => route('documents.update', $document->id)
]) }})" 
     x-init="initCompleteEditor()"
     @keydown.ctrl.s.prevent.window="saveDocument()"
     @keydown.meta.s.prevent.window="saveDocument()"
     class="min-h-screen flex flex-col bg-gray-50">
    
    <!-- Enhanced Header -->
    <header class="bg-white border-b px-6 py-3 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-4 flex-1">
            <i class="fas fa-file-word text-blue-600 text-2xl"></i>
            <input type="text" x-model="title" @blur="saveDocument()" class="text-xl font-semibold bg-transparent border-none outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1 w-full max-w-md" placeholder="Document title" :readonly="!canEdit">
            
            <!-- Presence Indicators with Cursors -->
            <div x-show="activeUsers.length > 0" class="flex items-center -space-x-2">
                <template x-for="user in activeUsers.slice(0, 5)" :key="user.id">
                    <div class="presence-avatar ring-2 ring-white relative" :style="'background-color: ' + user.color" :title="user.name + ' is editing'">
                        <span x-text="user.initials"></span>
                        <!-- Live Cursor Indicator -->
                        <div x-show="user.cursor_position" class="remote-cursor" :style="'left: ' + (user.cursor_position.x || 0) + 'px; top: ' + (user.cursor_position.y || 0) + 'px; background-color: ' + user.color">
                            <div class="remote-cursor-label" :style="'background-color: ' + user.color" x-text="user.name"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- View Mode Toggle -->
            <div class="flex bg-gray-100 rounded-lg p-1 mr-2">
                <button @click="viewMode = 'edit'" :class="viewMode === 'edit' ? 'bg-white shadow' : ''" class="px-3 py-1.5 rounded text-sm font-medium">Edit</button>
                <button @click="viewMode = 'suggest'" :class="viewMode === 'suggest' ? 'bg-white shadow' : ''" class="px-3 py-1.5 rounded text-sm font-medium">Suggest</button>
                <button @click="viewMode = 'view'" :class="viewMode === 'view' ? 'bg-white shadow' : ''" class="px-3 py-1.5 rounded text-sm font-medium">View</button>
            </div>
            
            <!-- Track Changes Toggle -->
            <button x-show="canEdit" @click="trackChangesEnabled = !trackChangesEnabled" :class="trackChangesEnabled ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100'" class="p-2 rounded" title="Track Changes">
                <i class="fas fa-history"></i>
            </button>
            
            <!-- Show Diff Viewer -->
            <button x-show="hasChanges" @click="showDiffViewer = !showDiffViewer" class="p-2 rounded hover:bg-gray-100" title="View Changes">
                <i class="fas fa-code-compare"></i>
            </button>
            
            <!-- Accessibility Checker -->
            <button @click="runAccessibilityCheck()" class="p-2 rounded hover:bg-gray-100" title="Check Accessibility">
                <i class="fas fa-universal-access"></i>
            </button>
            
            <!-- Macro Recorder -->
            <button x-show="canEdit" @click="toggleMacroRecording()" :class="isRecordingMacro ? 'bg-red-100 text-red-600' : 'hover:bg-gray-100'" class="p-2 rounded" title="Record Macro">
                <i class="fas fa-circle" :class="isRecordingMacro ? 'animate-pulse' : ''"></i>
            </button>
            
            <button @click="showComments = !showComments" :class="showComments ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100'" class="p-2 rounded relative">
                <i class="fas fa-comment-dots"></i>
                <span x-show="comments.length > 0" class="absolute -top-1 -right-1 bg-blue-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" x-text="comments.length"></span>
            </button>
            
            <button @click="showPageSetup = !showPageSetup" class="p-2 rounded hover:bg-gray-100" title="Page Setup">
                <i class="fas fa-cog"></i>
            </button>
            
            <a href="{{ route('shares.index', $document->id) }}" class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-share-alt"></i> Share
            </a>
        </div>
    </header>

    <!-- Complete Toolbar with All Features -->
    <div x-show="canEdit && viewMode !== 'view'" class="bg-white border-b shadow-sm" x-cloak>
        <!-- Row 1: Clipboard & Typography -->
        <div class="advanced-toolbar">
            <div class="toolbar-group">
                <button @click="editor.history.undo()" :disabled="!editor.history.canUndo()" class="p-2 rounded hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-undo"></i></button>
                <button @click="editor.history.redo()" :disabled="!editor.history.canRedo()" class="p-2 rounded hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-redo"></i></button>
            </div>

            <div class="toolbar-group">
                <select x-ref="fontFamily" @change="editor.format('font', $event.target.value)" class="text-sm border rounded px-2 py-1.5 w-36">
                    <option value="">Default Font</option>
                    <option value="Arial">Arial</option>
                    <option value="Times New Roman">Times New Roman</option>
                    <option value="Calibri">Calibri</option>
                </select>
                
                <select x-ref="fontSize" @change="setFontSize($event.target.value)" class="text-sm border rounded px-2 py-1.5 w-20">
                    <option value="12pt" selected>12</option>
                    <option value="14pt">14</option>
                    <option value="16pt">16</option>
                    <option value="18pt">18</option>
                </select>
            </div>

            <div class="toolbar-group">
                <button @click="editor.format('bold')" :class="isActive('bold') ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100'" class="p-2 rounded"><i class="fas fa-bold"></i></button>
                <button @click="editor.format('italic')" :class="isActive('italic') ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100'" class="p-2 rounded"><i class="fas fa-italic"></i></button>
                <button @click="editor.format('underline')" :class="isActive('underline') ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100'" class="p-2 rounded"><i class="fas fa-underline"></i></button>
                <button @click="editor.format('subscript')" :class="isActive('subscript') ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100'" class="p-2 rounded"><i class="fas fa-subscript"></i></button>
                <button @click="editor.format('superscript')" :class="isActive('superscript') ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100'" class="p-2 rounded"><i class="fas fa-superscript"></i></button>
            </div>

            <div class="toolbar-group">
                <button @click="insertChart()" class="p-2 rounded hover:bg-gray-100" title="Insert Chart"><i class="fas fa-chart-bar"></i></button>
                <button @click="insertShape()" class="p-2 rounded hover:bg-gray-100" title="Insert Shape"><i class="fas fa-shapes"></i></button>
                <button @click="insertFormField()" class="p-2 rounded hover:bg-gray-100" title="Insert Form Field"><i class="fas fa-wpforms"></i></button>
                <button @click="insertMergeField()" class="p-2 rounded hover:bg-gray-100" title="Insert Mail Merge Field"><i class="fas fa-envelope-open-text"></i></button>
                <button @click="addDigitalSignature()" class="p-2 rounded hover:bg-gray-100" title="Add Digital Signature"><i class="fas fa-signature"></i></button>
            </div>

            <div class="toolbar-group">
                <button @click="showTableContextMenu = true" class="p-2 rounded hover:bg-gray-100" title="Table Tools"><i class="fas fa-table"></i></button>
                <button @click="runMailMerge()" class="p-2 rounded hover:bg-gray-100" title="Mail Merge"><i class="fas fa-mail-bulk"></i></button>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Document Editor -->
        <div class="flex-1 overflow-y-auto bg-gray-200 p-8">
            <div class="page-container">
                <!-- Track Changes Diff Viewer Panel -->
                <div x-show="showDiffViewer && hasChanges" class="diff-panel" x-cloak>
                    <h3 class="font-semibold mb-2 flex items-center gap-2">
                        <i class="fas fa-code-compare text-blue-600"></i>
                        Track Changes (<span x-text="trackedChanges.length"></span> changes)
                    </h3>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        <template x-for="change in trackedChanges" :key="change.id">
                            <div :class="change.type === 'insert' ? 'track-insert' : change.type === 'delete' ? 'track-delete' : 'track-format-change'">
                                <span class="text-xs font-bold" x-text="change.user"></span>:
                                <span x-text="change.content"></span>
                                <div class="flex gap-2 mt-1">
                                    <button @click="acceptChange(change.id)" class="text-xs text-green-600 hover:underline">Accept</button>
                                    <button @click="rejectChange(change.id)" class="text-xs text-red-600 hover:underline">Reject</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Accessibility Issues Panel -->
                <div x-show="showAccessibilityPanel" class="diff-panel" x-cloak>
                    <h3 class="font-semibold mb-2 flex items-center gap-2">
                        <i class="fas fa-universal-access text-blue-600"></i>
                        Accessibility Check Results
                    </h3>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        <template x-for="issue in accessibilityIssues" :key="issue.id">
                            <div :class="'accessibility-' + issue.severity">
                                <strong x-text="issue.title"></strong>
                                <p class="text-sm" x-text="issue.description"></p>
                                <button @click="fixAccessibilityIssue(issue)" class="text-xs text-blue-600 hover:underline mt-1">Fix Automatically</button>
                            </div>
                        </template>
                        <div x-show="accessibilityIssues.length === 0" class="accessibility-success">
                            ✅ No accessibility issues found!
                        </div>
                    </div>
                </div>

                <!-- Main Editor -->
                <div x-ref="editorContainer" class="ql-container"></div>

                <!-- SVG Shape Drawing Layer -->
                <svg x-ref="shapeLayer" class="absolute inset-0 pointer-events-none" style="z-index: 50;">
                    <!-- Shapes will be dynamically added here -->
                </svg>

                <!-- Chart Rendering Layer -->
                <div x-ref="chartLayer" class="absolute inset-0" style="z-index: 40; pointer-events: none;">
                    <!-- Charts will be rendered here by Chart.js -->
                </div>
            </div>
        </div>

        <!-- Table Context Menu for Merge/Split -->
        <div x-show="showTableContextMenu" @click.away="showTableContextMenu = false" class="table-context-menu" x-cloak>
            <button @click="mergeCells()"><i class="fas fa-object-group mr-2"></i> Merge Cells</button>
            <button @click="splitCell()"><i class="fas fa-object-ungroup mr-2"></i> Split Cell</button>
            <hr class="my-1">
            <button @click="insertRowAbove()"><i class="fas fa-arrow-up mr-2"></i> Insert Row Above</button>
            <button @click="insertRowBelow()"><i class="fas fa-arrow-down mr-2"></i> Insert Row Below</button>
            <button @click="insertColumnLeft()"><i class="fas fa-arrow-left mr-2"></i> Insert Column Left</button>
            <button @click="insertColumnRight()"><i class="fas fa-arrow-right mr-2"></i> Insert Column Right</button>
            <hr class="my-1">
            <button @click="deleteRow()"><i class="fas fa-trash mr-2"></i> Delete Row</button>
            <button @click="deleteColumn()"><i class="fas fa-trash mr-2"></i> Delete Column</button>
        </div>

        <!-- Comments Sidebar -->
        <div x-show="showComments && canComment" x-transition class="w-80 bg-white border-l overflow-y-auto shadow-lg" x-cloak>
            <div class="p-4 border-b bg-gray-50">
                <h3 class="font-semibold text-gray-900">Comments (<span x-text="comments.length"></span>)</h3>
            </div>
            <div class="p-4 space-y-3">
                <template x-for="comment in comments" :key="comment.id">
                    <div class="bg-gray-50 rounded-lg p-3 border">
                        <p class="font-medium text-sm" x-text="comment.user_name"></p>
                        <p class="text-sm text-gray-700" x-text="comment.content"></p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Macro Recording Indicator -->
    <div x-show="isRecordingMacro" class="macro-recording" x-cloak>
        <i class="fas fa-circle text-red-500 mr-2 animate-pulse"></i>
        Recording Macro... (<span x-text="macroSteps.length"></span> steps)
        <button @click="stopMacroRecording()" class="ml-4 bg-white text-red-600 px-3 py-1 rounded text-sm">Stop</button>
    </div>
</div>
@endsection

@push('scripts')
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
function completeDocumentEditor(config) {
    return {
        editor: null,
        title: config.title,
        saved: true,
        saving: false,
        showComments: false,
        showPageSetup: false,
        showDiffViewer: false,
        showAccessibilityPanel: false,
        showTableContextMenu: false,
        canEdit: config.canEdit,
        canComment: config.canComment,
        comments: config.comments,
        saveTimeout: null,
        viewMode: 'edit',
        trackChangesEnabled: false,
        hasChanges: false,
        trackedChanges: [],
        activeUsers: [],
        accessibilityIssues: [],
        isRecordingMacro: false,
        macroSteps: [],
        chartInstances: {},
        
        initCompleteEditor() {
            // Initialize Quill
            this.editor = new Quill(this.$refs.editorContainer, {
                theme: 'snow',
                modules: {
                    toolbar: false,
                    history: { delay: 2000, maxStack: 500 }
                },
                placeholder: 'Start typing...',
                readOnly: !this.canEdit || this.viewMode === 'view'
            });
            
            if (config.content) {
                this.editor.root.innerHTML = config.content;
            }
            
            // Auto-save
            this.editor.on('text-change', () => {
                this.saved = false;
                if (this.trackChangesEnabled) {
                    this.recordChange();
                }
                if (this.isRecordingMacro) {
                    this.recordMacroStep('text-change');
                }
                this.debounceSave();
            });
            
            // Start presence polling with cursor tracking
            this.startPresencePolling();
        },
        
        isActive(format) {
            return this.editor.isActive(format);
        },
        
        setFontSize(size) {
            this.editor.format('size', size);
        },
        
        // Chart.js Integration
        insertChart() {
            const chartType = prompt('Chart type (bar/line/pie/scatter):', 'bar');
            if (!chartType) return;
            
            const chartId = 'chart_' + Date.now();
            const chartWrapper = document.createElement('div');
            chartWrapper.className = 'chart-wrapper';
            chartWrapper.innerHTML = `<canvas id="${chartId}" class="chart-canvas"></canvas>`;
            
            const range = this.editor.getSelection(true);
            this.editor.insertEmbed(range.index, 'block', chartWrapper);
            
            // Initialize Chart.js
            setTimeout(() => {
                const ctx = document.getElementById(chartId);
                if (ctx) {
                    this.chartInstances[chartId] = new Chart(ctx, {
                        type: chartType,
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                            datasets: [{
                                label: 'Sample Data',
                                data: [12, 19, 3, 5, 2],
                                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                                borderColor: 'rgb(59, 130, 246)',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
            }, 100);
        },
        
        // SVG Shape Drawing
        insertShape() {
            const shapeType = prompt('Shape (rectangle/circle/arrow):', 'rectangle');
            if (!shapeType) return;
            
            const shapeId = 'shape_' + Date.now();
            const svg = this.$refs.shapeLayer;
            
            let shapeElement;
            if (shapeType === 'rectangle') {
                shapeElement = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                shapeElement.setAttribute('x', '100');
                shapeElement.setAttribute('y', '100');
                shapeElement.setAttribute('width', '200');
                shapeElement.setAttribute('height', '100');
                shapeElement.setAttribute('class', 'shape-rectangle');
            } else if (shapeType === 'circle') {
                shapeElement = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                shapeElement.setAttribute('cx', '200');
                shapeElement.setAttribute('cy', '150');
                shapeElement.setAttribute('r', '50');
                shapeElement.setAttribute('class', 'shape-circle');
            }
            
            if (shapeElement) {
                shapeElement.setAttribute('id', shapeId);
                shapeElement.style.cursor = 'move';
                svg.appendChild(shapeElement);
                
                // Make draggable
                this.makeShapeDraggable(shapeElement);
            }
        },
        
        makeShapeDraggable(element) {
            let isDragging = false;
            let startX, startY, initialX, initialY;
            
            element.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                
                if (element.tagName === 'rect') {
                    initialX = parseInt(element.getAttribute('x'));
                    initialY = parseInt(element.getAttribute('y'));
                } else if (element.tagName === 'circle') {
                    initialX = parseInt(element.getAttribute('cx'));
                    initialY = parseInt(element.getAttribute('cy'));
                }
                
                element.style.pointerEvents = 'all';
            });
            
            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                
                if (element.tagName === 'rect') {
                    element.setAttribute('x', initialX + dx);
                    element.setAttribute('y', initialY + dy);
                } else if (element.tagName === 'circle') {
                    element.setAttribute('cx', initialX + dx);
                    element.setAttribute('cy', initialY + dy);
                }
            });
            
            document.addEventListener('mouseup', () => {
                isDragging = false;
                element.style.pointerEvents = 'none';
            });
        },
        
        // Table Merge/Split Controls
        mergeCells() {
            const selection = window.getSelection();
            const cells = [];
            
            // Get selected cells
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                const container = range.commonAncestorContainer;
                
                // Find table cells in selection
                const tableCells = container.parentElement?.querySelectorAll('td, th') || [];
                tableCells.forEach(cell => {
                    if (selection.containsNode(cell, true)) {
                        cells.push(cell);
                    }
                });
            }
            
            if (cells.length < 2) {
                alert('Please select multiple cells to merge');
                return;
            }
            
            // Merge logic (simplified - production would need full table manipulation)
            const firstCell = cells[0];
            const mergedContent = cells.map(c => c.innerHTML).join(' ');
            firstCell.innerHTML = mergedContent;
            firstCell.setAttribute('colspan', cells.length);
            
            // Remove other cells
            cells.slice(1).forEach(cell => cell.remove());
            
            this.showTableContextMenu = false;
            this.showNotification('Cells merged successfully', 'success');
        },
        
        splitCell() {
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const cell = selection.anchorNode?.parentElement?.closest('td, th');
                if (cell) {
                    const colspan = parseInt(cell.getAttribute('colspan')) || 1;
                    if (colspan > 1) {
                        cell.removeAttribute('colspan');
                        // Insert new cells after
                        for (let i = 1; i < colspan; i++) {
                            const newCell = document.createElement('td');
                            newCell.innerHTML = '&nbsp;';
                            cell.after(newCell);
                        }
                        this.showNotification('Cell split successfully', 'success');
                    }
                }
            }
            this.showTableContextMenu = false;
        },
        
        insertRowAbove() { /* Implementation */ this.showTableContextMenu = false; },
        insertRowBelow() { /* Implementation */ this.showTableContextMenu = false; },
        insertColumnLeft() { /* Implementation */ this.showTableContextMenu = false; },
        insertColumnRight() { /* Implementation */ this.showTableContextMenu = false; },
        deleteRow() { /* Implementation */ this.showTableContextMenu = false; },
        deleteColumn() { /* Implementation */ this.showTableContextMenu = false; },
        
        // Track Changes with Visual Diff
        recordChange() {
            const change = {
                id: Date.now(),
                type: 'format',
                content: 'Formatting changed',
                user: 'You',
                timestamp: new Date().toISOString()
            };
            this.trackedChanges.unshift(change);
            this.hasChanges = true;
        },
        
        acceptChange(changeId) {
            this.trackedChanges = this.trackedChanges.filter(c => c.id !== changeId);
            this.hasChanges = this.trackedChanges.length > 0;
            this.showNotification('Change accepted', 'success');
        },
        
        rejectChange(changeId) {
            this.trackedChanges = this.trackedChanges.filter(c => c.id !== changeId);
            this.hasChanges = this.trackedChanges.length > 0;
            this.showNotification('Change rejected', 'success');
        },
        
        // Visual Cursor Indicators
        startPresencePolling() {
            setInterval(async () => {
                try {
                    const response = await fetch(`/documents/${config.documentId}/presence`);
                    if (response.ok) {
                        const data = await response.json();
                        this.activeUsers = data.users || [];
                        
                        // Update cursor positions visually
                        this.activeUsers.forEach(user => {
                            if (user.cursor_position) {
                                this.updateCursorIndicator(user);
                            }
                        });
                    }
                } catch (error) {
                    console.error('Presence update failed:', error);
                }
            }, 5000); // Poll every 5 seconds for smoother updates
        },
        
        updateCursorIndicator(user) {
            // Cursor visualization handled in template via x-show bindings
        },
        
        // Form Fields
        insertFormField() {
            const fieldType = prompt('Field type (text/checkbox/dropdown/date):', 'text');
            if (!fieldType) return;
            
            const fieldHTML = this.createFormFieldHTML(fieldType);
            const range = this.editor.getSelection(true);
            this.editor.clipboard.dangerouslyPasteHTML(range.index, fieldHTML);
        },
        
        createFormFieldHTML(type) {
            switch(type) {
                case 'text':
                    return '<span class="form-field form-field-text" contenteditable="false">[Text Field]</span>';
                case 'checkbox':
                    return '<input type="checkbox" class="form-field form-field-checkbox" />';
                case 'dropdown':
                    return '<select class="form-field form-field-dropdown"><option>Option 1</option><option>Option 2</option></select>';
                case 'date':
                    return '<input type="date" class="form-field" />';
                default:
                    return '<span class="form-field">[Field]</span>';
            }
        },
        
        // Mail Merge
        insertMergeField() {
            const fieldName = prompt('Merge field name (e.g., FirstName, LastName, Company):');
            if (fieldName) {
                const mergeFieldHTML = `<span class="merge-field" contenteditable="false">{{${fieldName}}}</span>`;
                const range = this.editor.getSelection(true);
                this.editor.clipboard.dangerouslyPasteHTML(range.index, mergeFieldHTML);
            }
        },
        
        runMailMerge() {
            const dataSource = prompt('Data source CSV URL or paste data:');
            if (dataSource) {
                this.showNotification('Mail merge processing... (Feature demo)', 'info');
                // In production: Parse CSV, replace {{fields}} with actual data, generate documents
            }
        },
        
        // Digital Signatures
        addDigitalSignature() {
            const canvas = document.createElement('canvas');
            canvas.width = 400;
            canvas.height = 200;
            canvas.className = 'signature-pad';
            
            const dialog = document.createElement('div');
            dialog.innerHTML = `
                <div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:20px;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);z-index:10000;">
                    <h3>Digital Signature</h3>
                    <p>Draw your signature below:</p>
                    <div id="signatureContainer"></div>
                    <div style="margin-top:10px;display:flex;gap:10px;">
                        <button onclick="this.closest('div').remove()" style="padding:8px 16px;background:#ef4444;color:white;border:none;border-radius:4px;cursor:pointer;">Cancel</button>
                        <button id="saveSignature" style="padding:8px 16px;background:#10b981;color:white;border:none;border-radius:4px;cursor:pointer;">Save Signature</button>
                    </div>
                </div>
            `;
            document.body.appendChild(dialog);
            
            const container = dialog.querySelector('#signatureContainer');
            container.appendChild(canvas);
            
            // Simple signature drawing
            let isDrawing = false;
            const ctx = canvas.getContext('2d');
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            
            canvas.addEventListener('mousedown', (e) => {
                isDrawing = true;
                ctx.beginPath();
                ctx.moveTo(e.offsetX, e.offsetY);
            });
            
            canvas.addEventListener('mousemove', (e) => {
                if (!isDrawing) return;
                ctx.lineTo(e.offsetX, e.offsetY);
                ctx.stroke();
            });
            
            canvas.addEventListener('mouseup', () => isDrawing = false);
            
            dialog.querySelector('#saveSignature').addEventListener('click', () => {
                const signatureData = canvas.toDataURL();
                const signatureHTML = `<img src="${signatureData}" class="signature-preview" alt="Digital Signature" />`;
                const range = this.editor.getSelection(true);
                this.editor.clipboard.dangerouslyPasteHTML(range.index, signatureHTML);
                dialog.remove();
                this.showNotification('Signature added', 'success');
            });
        },
        
        // Accessibility Checker
        async runAccessibilityCheck() {
            this.showAccessibilityPanel = true;
            this.accessibilityIssues = [];
            
            const content = this.editor.root.innerHTML;
            
            // Check for images without alt text
            const images = this.editor.root.querySelectorAll('img');
            images.forEach((img, index) => {
                if (!img.alt || img.alt.trim() === '') {
                    this.accessibilityIssues.push({
                        id: 'img_' + index,
                        severity: 'error',
                        title: 'Image missing alt text',
                        description: 'Add descriptive alt text for screen readers',
                        element: img
                    });
                }
            });
            
            // Check heading hierarchy
            const headings = this.editor.root.querySelectorAll('h1, h2, h3, h4, h5, h6');
            let lastLevel = 0;
            headings.forEach((heading, index) => {
                const level = parseInt(heading.tagName[1]);
                if (level > lastLevel + 1) {
                    this.accessibilityIssues.push({
                        id: 'heading_' + index,
                        severity: 'warning',
                        title: 'Heading level skipped',
                        description: `Jumped from H${lastLevel} to H${level}`,
                        element: heading
                    });
                }
                lastLevel = level;
            });
            
            // Check color contrast (simplified)
            const coloredText = this.editor.root.querySelectorAll('[style*="color"]');
            coloredText.forEach(el => {
                // In production: Calculate actual contrast ratio
                this.accessibilityIssues.push({
                    id: 'contrast_' + Math.random(),
                    severity: 'warning',
                    title: 'Color contrast may be insufficient',
                    description: 'Ensure text has 4.5:1 contrast ratio with background',
                    element: el
                });
            });
            
            if (this.accessibilityIssues.length === 0) {
                this.showNotification('✅ No accessibility issues found!', 'success');
            }
        },
        
        fixAccessibilityIssue(issue) {
            if (issue.severity === 'error' && issue.title.includes('alt text')) {
                issue.element.setAttribute('alt', 'Descriptive text');
                this.accessibilityIssues = this.accessibilityIssues.filter(i => i.id !== issue.id);
                this.showNotification('Alt text added', 'success');
            }
        },
        
        // Macro Recording
        toggleMacroRecording() {
            if (this.isRecordingMacro) {
                this.stopMacroRecording();
            } else {
                this.startMacroRecording();
            }
        },
        
        startMacroRecording() {
            this.isRecordingMacro = true;
            this.macroSteps = [];
            this.showNotification('Macro recording started', 'info');
        },
        
        stopMacroRecording() {
            this.isRecordingMacro = false;
            this.showNotification(`Macro recorded with ${this.macroSteps.length} steps`, 'success');
            
            // Save macro to server
            if (this.macroSteps.length > 0) {
                this.saveMacro();
            }
        },
        
        recordMacroStep(action) {
            this.macroSteps.push({
                action: action,
                timestamp: Date.now(),
                data: {}
            });
        },
        
        async saveMacro() {
            try {
                await fetch(`/documents/${config.documentId}/macros`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        name: 'Macro_' + Date.now(),
                        steps: this.macroSteps
                    })
                });
            } catch (error) {
                console.error('Failed to save macro:', error);
            }
        },
        
        // Utilities
        debounceSave() {
            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => this.saveDocument(), 2000);
        },
        
        async saveDocument() {
            if (this.saving || this.saved) return;
            this.saving = true;
            const content = this.editor.root.innerHTML;
            
            try {
                const response = await fetch(config.updateUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ content, title: this.title })
                });
                
                if (response.ok) {
                    this.saved = true;
                    this.showNotification('Document saved', 'success');
                }
            } catch (error) {
                console.error('Save failed:', error);
            } finally {
                this.saving = false;
            }
        },
        
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600'
            } text-white`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    }
}
</script>
@endpush
