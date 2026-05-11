import { useState, useCallback, useEffect, useRef, useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Bold, Italic, Underline, AlignLeft, AlignCenter, AlignRight,
    Type, Save, Download, Share2, Plus, FileSpreadsheet,
    BarChart3, PieChart, TrendingUp, MessageSquare, Trash2,
    Undo, Redo, Search, ChevronDown, PaintBucket, LetterText as Font
} from 'lucide-react';
import { motion } from 'framer-motion';

const COL_COUNT = 26;
const ROW_COUNT = 100;
const COL_WIDTH = 100;
const ROW_HEIGHT = 25;

function colLetter(n) {
    let s = '';
    while (n >= 0) {
        s = String.fromCharCode(65 + (n % 26)) + s;
        n = Math.floor(n / 26) - 1;
    }
    return s;
}

function cellId(row, col) {
    return colLetter(col) + (row + 1);
}

function parseCellId(id) {
    const match = id.match(/^([A-Z]+)(\d+)$/);
    if (!match) return null;
    let col = 0;
    for (let i = 0; i < match[1].length; i++) {
        col = col * 26 + (match[1].charCodeAt(i) - 64);
    }
    return { row: parseInt(match[2]) - 1, col: col - 1 };
}

export default function SpreadsheetEditor({ spreadsheet, sheets, activeSheetId }) {
    const [cells, setCells] = useState({});
    const [selectedCell, setSelectedCell] = useState(null);
    const [editingCell, setEditingCell] = useState(null);
    const [formulaBar, setFormulaBar] = useState('');
    const [activeSheet, setActiveSheet] = useState(activeSheetId);
    const [saving, setSaving] = useState(false);
    const [showShare, setShowShare] = useState(false);
    const [cellFormats, setCellFormats] = useState({});
    const gridRef = useRef(null);
    const inputRef = useRef(null);

    const currentSheet = sheets?.find(s => s.id === activeSheet);

    // Keyboard navigation
    useEffect(() => {
        const handler = (e) => {
            if (!selectedCell) return;
            const pos = parseCellId(selectedCell);
            if (!pos) return;

            if (e.key === 'Tab') {
                e.preventDefault();
                const newCol = e.shiftKey ? pos.col - 1 : pos.col + 1;
                if (newCol >= 0 && newCol < COL_COUNT) {
                    selectCell(cellId(pos.row, newCol));
                }
            }
            if (e.key === 'Enter') {
                e.preventDefault();
                if (editingCell) {
                    finishEditing();
                } else {
                    setEditingCell(selectedCell);
                    setFormulaBar(cells[selectedCell]?.formula || cells[selectedCell]?.value || '');
                }
            }
            if (e.key === 'Escape' && editingCell) {
                setEditingCell(null);
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveCells();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                toggleFormat('bold');
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'i') {
                e.preventDefault();
                toggleFormat('italic');
            }
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [selectedCell, editingCell, cells]);

    const selectCell = useCallback((id) => {
        if (editingCell) finishEditing();
        setSelectedCell(id);
        const cell = cells[id];
        setFormulaBar(cell?.formula || cell?.value || '');
    }, [cells, editingCell]);

    const startEditing = useCallback((id) => {
        setEditingCell(id);
        const cell = cells[id];
        setFormulaBar(cell?.formula || cell?.value || '');
        setTimeout(() => inputRef.current?.focus(), 50);
    }, [cells]);

    const finishEditing = useCallback(() => {
        if (!editingCell) return;
        const val = formulaBar;
        const isFormula = val?.startsWith('=');
        const newCells = { ...cells };

        newCells[editingCell] = {
            ...newCells[editingCell],
            cell_address: editingCell,
            value: isFormula ? null : val,
            formula: isFormula ? val : null,
            computed_value: isFormula ? evalFormula(val) : val,
            data_type: isFormula ? 'formula' : (isNaN(val) ? 'text' : 'number'),
            row: parseCellId(editingCell).row,
            column: parseCellId(editingCell).col,
            sheet_id: activeSheet,
            format_json: cellFormats[editingCell] || {},
        };

        setCells(newCells);
        setEditingCell(null);
        setCellSelected(editingCell);
    }, [editingCell, formulaBar, cells, activeSheet, cellFormats]);

    const evalFormula = (formula) => {
        // Simple formula evaluator
        const expr = formula.substring(1).toUpperCase();
        try {
            // Handle SUM, AVERAGE, COUNT, MAX, MIN
            const funcMatch = expr.match(/^(SUM|AVERAGE|COUNT|MAX|MIN)\((.+)\)$/);
            if (funcMatch) {
                const [, func, range] = funcMatch;
                const values = getRangeValues(range);
                const nums = values.filter(v => !isNaN(parseFloat(v))).map(Number);
                switch (func) {
                    case 'SUM': return nums.reduce((a, b) => a + b, 0);
                    case 'AVERAGE': return nums.length ? nums.reduce((a, b) => a + b, 0) / nums.length : 0;
                    case 'COUNT': return nums.length;
                    case 'MAX': return nums.length ? Math.max(...nums) : 0;
                    case 'MIN': return nums.length ? Math.min(...nums) : 0;
                }
            }
            // Handle cell references
            const resolved = expr.replace(/[A-Z]+\d+/g, (match) => {
                const c = cells[match];
                const v = c?.computed_value ?? c?.value ?? 0;
                return isNaN(v) ? `"${v}"` : v;
            });
            return Function('"use strict"; return (' + resolved + ')')();
        } catch {
            return '#ERROR!';
        }
    };

    const getRangeValues = (range) => {
        const [start, end] = range.split(':');
        const s = parseCellId(start);
        const e = parseCellId(end);
        const values = [];
        for (let r = s.row; r <= e.row; r++) {
            for (let c = s.col; c <= e.col; c++) {
                const id = cellId(r, c);
                const cell = cells[id];
                values.push(cell?.computed_value ?? cell?.value ?? '');
            }
        }
        return values;
    };

    const toggleFormat = (key) => {
        if (!selectedCell) return;
        const fmt = { ...(cellFormats[selectedCell] || {}) };
        fmt[key] = !fmt[key];
        setCellFormats(prev => ({ ...prev, [selectedCell]: fmt }));
        setCells(prev => ({
            ...prev,
            [selectedCell]: { ...prev[selectedCell], format_json: fmt }
        }));
    };

    const applyFormat = (key, value) => {
        if (!selectedCell) return;
        const fmt = { ...(cellFormats[selectedCell] || {}), [key]: value };
        setCellFormats(prev => ({ ...prev, [selectedCell]: fmt }));
    };

    const saveCells = useCallback(() => {
        if (editingCell) finishEditing();
        const cellsToSave = Object.values(cells)
            .filter(c => c.sheet_id === activeSheet)
            .map(c => ({
                cell_address: c.cell_address,
                value: c.value,
                formula: c.formula,
                computed_value: c.computed_value,
                data_type: c.data_type,
                format_json: c.format_json || cellFormats[c.cell_address] || {},
            }));

        if (cellsToSave.length === 0) return;

        setSaving(true);
        router.post(route('cells.batch', activeSheet), { cells: cellsToSave }, {
            onFinish: () => setSaving(false),
        });
    }, [cells, activeSheet, cellFormats, editingCell, finishEditing]);

    // Auto-save every 15 seconds
    useEffect(() => {
        const timer = setInterval(() => {
            if (Object.keys(cells).length > 0) saveCells();
        }, 15000);
        return () => clearInterval(timer);
    }, [saveCells]);

    const addSheet = () => {
        router.post(route('sheets.store', spreadsheet.id), {
            name: `Sheet ${sheets.length + 1}`,
        });
    };

    const exportFile = (format) => {
        window.location.href = route('spreadsheets.export', { id: spreadsheet.id, format });
    };

    const getCellStyle = (cell) => {
        const fmt = cellFormats[cell] || {};
        return {
            fontWeight: fmt.bold ? 'bold' : 'normal',
            fontStyle: fmt.italic ? 'italic' : 'normal',
            textDecoration: fmt.underline ? 'underline' : 'none',
            textAlign: fmt.align || 'left',
            backgroundColor: fmt.bgColor || (cell === selectedCell ? '#E8F0FE' : 'white'),
            color: fmt.color || 'black',
            border: cell === selectedCell ? '2px solid #107C41' : '1px solid #E0E0E0',
        };
    };

    return (
        <div className="h-screen flex flex-col bg-white">
            <Head title={spreadsheet?.title || 'YG Xcel'} />

            {/* Header */}
            <header className="bg-[#107C41] text-white px-4 py-2 flex items-center justify-between">
                <div className="flex items-center gap-4 flex-1">
                    <a href={route('home')} className="hover:bg-white/20 p-1 rounded">
                        <FileSpreadsheet className="h-6 w-6" />
                    </a>
                    <div>
                        <h1 className="text-lg font-semibold">{spreadsheet?.title}</h1>
                        <p className="text-xs text-white/70">{saving ? 'Saving...' : 'All changes saved'}</p>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <button onClick={() => exportFile('xlsx')} className="flex items-center gap-1 px-3 py-1.5 bg-white/20 rounded hover:bg-white/30 text-sm">
                        <Download className="h-4 w-4" /> XLSX
                    </button>
                    <button onClick={() => exportFile('csv')} className="flex items-center gap-1 px-3 py-1.5 bg-white/20 rounded hover:bg-white/30 text-sm">
                        <Download className="h-4 w-4" /> CSV
                    </button>
                    <button onClick={() => setShowShare(!showShare)} className="flex items-center gap-1 px-3 py-1.5 bg-white text-[#107C41] rounded text-sm font-medium">
                        <Share2 className="h-4 w-4" /> Share
                    </button>
                </div>
            </header>

            {/* Toolbar */}
            <div className="bg-gray-50 border-b px-4 py-1.5 flex items-center gap-1 flex-wrap">
                <div className="flex items-center gap-0.5">
                    <button onClick={() => router.reload()} className="p-1.5 rounded hover:bg-gray-200" title="Undo"><Undo className="h-4 w-4 text-gray-600" /></button>
                    <button className="p-1.5 rounded hover:bg-gray-200" title="Redo"><Redo className="h-4 w-4 text-gray-600" /></button>
                </div>
                <div className="w-px h-6 bg-gray-300 mx-1" />
                <div className="flex items-center gap-0.5">
                    <button onClick={() => toggleFormat('bold')} className={`p-1.5 rounded ${cellFormats[selectedCell]?.bold ? 'bg-gray-300' : 'hover:bg-gray-200'}`}><Bold className="h-4 w-4" /></button>
                    <button onClick={() => toggleFormat('italic')} className={`p-1.5 rounded ${cellFormats[selectedCell]?.italic ? 'bg-gray-300' : 'hover:bg-gray-200'}`}><Italic className="h-4 w-4" /></button>
                    <button onClick={() => toggleFormat('underline')} className={`p-1.5 rounded ${cellFormats[selectedCell]?.underline ? 'bg-gray-300' : 'hover:bg-gray-200'}`}><Underline className="h-4 w-4" /></button>
                </div>
                <div className="w-px h-6 bg-gray-300 mx-1" />
                <div className="flex items-center gap-0.5">
                    <button onClick={() => applyFormat('align', 'left')} className="p-1.5 rounded hover:bg-gray-200"><AlignLeft className="h-4 w-4" /></button>
                    <button onClick={() => applyFormat('align', 'center')} className="p-1.5 rounded hover:bg-gray-200"><AlignCenter className="h-4 w-4" /></button>
                    <button onClick={() => applyFormat('align', 'right')} className="p-1.5 rounded hover:bg-gray-200"><AlignRight className="h-4 w-4" /></button>
                </div>
                <div className="w-px h-6 bg-gray-300 mx-1" />
                <div className="flex items-center gap-1">
                    <Font className="h-4 w-4 text-gray-500" />
                    <select onChange={(e) => applyFormat('fontSize', e.target.value)} className="text-sm border rounded px-1 py-0.5">
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12" selected>12</option>
                        <option value="14">14</option>
                        <option value="16">16</option>
                    </select>
                </div>
                <div className="w-px h-6 bg-gray-300 mx-1" />
                <div className="flex items-center gap-1">
                    <PaintBucket className="h-4 w-4 text-gray-500" />
                    <input type="color" onChange={(e) => applyFormat('bgColor', e.target.value)} className="w-6 h-6 border rounded cursor-pointer" title="Background color" />
                    <input type="color" onChange={(e) => applyFormat('color', e.target.value)} className="w-6 h-6 border rounded cursor-pointer" title="Text color" />
                </div>
                <div className="flex-1" />
                {selectedCell && (
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                        <span className="font-mono font-bold bg-gray-200 px-2 py-0.5 rounded">{selectedCell}</span>
                    </div>
                )}
            </div>

            {/* Formula Bar */}
            <div className="flex items-center border-b bg-white">
                <div className="w-12 text-center font-mono text-sm text-gray-600 border-r py-1.5 bg-gray-50">
                    {selectedCell || ''}
                </div>
                <div className="px-2 text-gray-400">fx</div>
                <input
                    ref={inputRef}
                    type="text"
                    value={formulaBar}
                    onChange={(e) => setFormulaBar(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') finishEditing();
                        if (e.key === 'Escape') { setEditingCell(null); setFormulaBar(''); }
                    }}
                    className="flex-1 px-2 py-1.5 text-sm border-none focus:outline-none font-mono"
                    placeholder="Enter value or formula (e.g., =SUM(A1:A10))"
                />
                {editingCell && (
                    <button onClick={finishEditing} className="px-3 py-1.5 bg-[#107C41] text-white text-sm mr-2 rounded">
                        ✓
                    </button>
                )}
            </div>

            {/* Spreadsheet Grid */}
            <div className="flex-1 overflow-auto" ref={gridRef}>
                <table className="border-collapse select-none" style={{ tableLayout: 'fixed' }}>
                    <thead>
                        <tr>
                            <th className="w-10 h-6 bg-gray-100 border border-gray-300 sticky top-0 left-0 z-20" style={{ minWidth: 40 }} />
                            {Array.from({ length: COL_COUNT }, (_, i) => (
                                <th key={i} className="h-6 bg-gray-100 border border-gray-300 text-xs font-medium text-gray-600 sticky top-0 z-10" style={{ width: COL_WIDTH }}>
                                    {colLetter(i)}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {Array.from({ length: ROW_COUNT }, (_, row) => (
                            <tr key={row}>
                                <td className="w-10 text-center text-xs text-gray-500 bg-gray-50 border border-gray-300 sticky left-0 z-10" style={{ height: ROW_HEIGHT, minWidth: 40 }}>
                                    {row + 1}
                                </td>
                                {Array.from({ length: COL_COUNT }, (_, col) => {
                                    const id = cellId(row, col);
                                    const cell = cells[id];
                                    const displayValue = cell?.computed_value ?? cell?.value ?? '';
                                    const isEditing = editingCell === id;
                                    const isSelected = selectedCell === id;

                                    return (
                                        <td
                                            key={col}
                                            style={{
                                                ...getCellStyle(id),
                                                width: COL_WIDTH,
                                                height: ROW_HEIGHT,
                                                padding: '2px 4px',
                                                fontSize: (cellFormats[id]?.fontSize || 12) + 'px',
                                            }}
                                            onClick={() => selectCell(id)}
                                            onDoubleClick={() => startEditing(id)}
                                            className="relative"
                                        >
                                            {isEditing ? (
                                                <input
                                                    ref={inputRef}
                                                    type="text"
                                                    value={formulaBar}
                                                    onChange={(e) => setFormulaBar(e.target.value)}
                                                    onBlur={finishEditing}
                                                    onKeyDown={(e) => {
                                                        if (e.key === 'Enter') finishEditing();
                                                        if (e.key === 'Escape') { setEditingCell(null); }
                                                    }}
                                                    className="w-full h-full border-none outline-none bg-white px-1 py-0 text-sm"
                                                    autoFocus
                                                />
                                            ) : (
                                                <span className="block truncate">{displayValue}</span>
                                            )}
                                            {cell?.formula && !isEditing && (
                                                <span className="absolute top-0 right-0 w-2 h-2 bg-green-500 rounded-bl" title={`Formula: ${cell.formula}`} />
                                            )}
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Sheet Tabs */}
            <div className="flex items-center border-t bg-gray-50 px-2 py-1">
                <button onClick={addSheet} className="p-1 rounded hover:bg-gray-200 mr-2" title="Add sheet">
                    <Plus className="h-4 w-4 text-gray-600" />
                </button>
                <div className="flex gap-1 overflow-x-auto flex-1">
                    {sheets?.map(sheet => (
                        <button
                            key={sheet.id}
                            onClick={() => { setActiveSheet(sheet.id); setCells({}); }}
                            className={`px-4 py-1.5 text-sm rounded-t font-medium transition-colors ${sheet.id === activeSheet
                                    ? 'bg-white text-[#107C41] border-t-2 border-[#107C41]'
                                    : 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                                }`}
                        >
                            {sheet.name}
                        </button>
                    ))}
                </div>
            </div>

            {/* Status Bar */}
            <div className="bg-[#107C41] text-white px-4 py-1 flex items-center justify-between text-xs">
                <span>{sheets?.length || 0} sheets</span>
                <div className="flex gap-4">
                    {selectedCell && cells[selectedCell] && (
                        <>
                            <span>Value: {cells[selectedCell]?.computed_value ?? cells[selectedCell]?.value ?? ''}</span>
                            {cells[selectedCell]?.formula && <span>Formula: {cells[selectedCell].formula}</span>}
                        </>
                    )}
                </div>
                <span>Ctrl+S to save • Enter to edit • Double-click cell</span>
            </div>
        </div>
    );
}
