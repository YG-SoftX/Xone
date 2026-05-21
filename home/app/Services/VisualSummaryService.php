<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * VisualSummaryService
 * 
 * Automatically generates visual summaries (charts, tables, infographics)
 * from extracted data using Chart.js and HTML table generation.
 */
class VisualSummaryService
{
    /**
     * Detect data patterns in page content and suggest visualizations
     * 
     * @param string $html Page HTML content
     * @param string $url Source URL
     * @return array Detected visualizations with HTML embeds
     */
    public function detectAndGenerate(string $html, string $url): array
    {
        $visualizations = [];
        
        // 1. Detect and extract tables
        $tables = $this->extractTables($html);
        if (!empty($tables)) {
            foreach ($tables as $index => $tableData) {
                $tableNum = $index + 1;
                $visualizations[] = [
                    'type' => 'table',
                    'title' => "Table {$tableNum}",
                    'html' => $this->renderTable($tableData),
                    'data' => $tableData,
                    'confidence' => 0.95,
                ];
            }
        }
        
        // 2. Detect numerical data for charts
        $numericalData = $this->extractNumericalData($html);
        if (!empty($numericalData)) {
            foreach ($numericalData as $index => $dataSet) {
                $chartType = $this->suggestChartType($dataSet);
                $chartNum = $index + 1;
                $visualizations[] = [
                    'type' => 'chart',
                    'chart_type' => $chartType,
                    'title' => $dataSet['title'] ?? "Chart {$chartNum}",
                    'html' => $this->renderChart($dataSet, $chartType),
                    'data' => $dataSet,
                    'confidence' => 0.8,
                ];
            }
        }
        
        // 3. Detect lists for summary cards
        $lists = $this->extractLists($html);
        if (!empty($lists)) {
            foreach ($lists as $index => $listData) {
                $listNum = $index + 1;
                $visualizations[] = [
                    'type' => 'list',
                    'title' => $listData['title'] ?? "List {$listNum}",
                    'html' => $this->renderList($listData),
                    'data' => $listData,
                    'confidence' => 0.7,
                ];
            }
        }
        
        // 4. Generate key metrics summary
        $metrics = $this->extractKeyMetrics($html);
        if (!empty($metrics)) {
            $visualizations[] = [
                'type' => 'metrics',
                'title' => 'Key Metrics',
                'html' => $this->renderMetrics($metrics),
                'data' => $metrics,
                'confidence' => 0.85,
            ];
        }
        
        return $visualizations;
    }
    
    /**
     * Extract HTML tables from page content
     */
    private function extractTables(string $html): array
    {
        $tables = [];
        
        // Use DOMDocument to parse tables
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        
        $tableElements = $dom->getElementsByTagName('table');
        
        foreach ($tableElements as $tableIndex => $table) {
            $rows = [];
            $tableRows = $table->getElementsByTagName('tr');
            
            foreach ($tableRows as $row) {
                $cells = [];
                $cellElements = $row->getElementsByTagName('td');
                
                if ($cellElements->length === 0) {
                    $cellElements = $row->getElementsByTagName('th');
                }
                
                foreach ($cellElements as $cell) {
                    $cells[] = trim($cell->textContent);
                }
                
                if (!empty($cells)) {
                    $rows[] = $cells;
                }
            }
            
            if (count($rows) > 1 && count($rows[0]) > 0) {
                $tables[] = [
                    'headers' => $rows[0],
                    'data' => array_slice($rows, 1),
                    'row_count' => count($rows) - 1,
                    'col_count' => count($rows[0]),
                ];
            }
        }
        
        return $tables;
    }
    
    /**
     * Extract numerical data suitable for charting
     */
    private function extractNumericalData(string $html): array
    {
        $dataSets = [];
        
        // Pattern 1: Look for percentage patterns (e.g., "45%", "growth: 23%")
        preg_match_all('/([\w\s]+)[:\s]+(\d+(?:\.\d+)?)%/', $html, $matches);
        if (!empty($matches[1]) && !empty($matches[2])) {
            $dataSets[] = [
                'title' => 'Percentage Distribution',
                'labels' => array_map('trim', array_slice($matches[1], 0, 10)),
                'values' => array_map('floatval', array_slice($matches[2], 0, 10)),
                'unit' => '%',
            ];
        }
        
        // Pattern 2: Look for currency amounts (e.g., "$1,234", "price: $567")
        preg_match_all('/([\w\s]+)[:\s]+\$([0-9,]+)/', $html, $moneyMatches);
        if (!empty($moneyMatches[1]) && !empty($moneyMatches[2])) {
            $dataSets[] = [
                'title' => 'Financial Data',
                'labels' => array_map('trim', array_slice($moneyMatches[1], 0, 10)),
                'values' => array_map(function($v) {
                    return floatval(str_replace(',', '', $v));
                }, array_slice($moneyMatches[2], 0, 10)),
                'unit' => '$',
            ];
        }
        
        // Pattern 3: Look for year-value pairs (e.g., "2020: 100", "2021: 150")
        preg_match_all('/(20\d{2})[:\s]+(\d+(?:,\d+)*)/', $html, $yearMatches);
        if (!empty($yearMatches[1]) && !empty($yearMatches[2])) {
            $dataSets[] = [
                'title' => 'Yearly Trends',
                'labels' => array_slice($yearMatches[1], 0, 10),
                'values' => array_map(function($v) {
                    return intval(str_replace(',', '', $v));
                }, array_slice($yearMatches[2], 0, 10)),
                'unit' => '',
            ];
        }
        
        return $dataSets;
    }
    
    /**
     * Extract bullet/numbered lists
     */
    private function extractLists(string $html): array
    {
        $lists = [];
        
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        
        // Extract <ul> and <ol> lists
        foreach (['ul', 'ol'] as $tag) {
            $listElements = $dom->getElementsByTagName($tag);
            
            foreach ($listElements as $listIndex => $list) {
                $items = [];
                $listItems = $list->getElementsByTagName('li');
                
                foreach ($listItems as $item) {
                    $text = trim($item->textContent);
                    if (!empty($text)) {
                        $items[] = $text;
                    }
                }
                
                if (count($items) >= 3) {
                    $lists[] = [
                        'title' => ucfirst($tag) . ' List',
                        'items' => array_slice($items, 0, 20),
                        'count' => count($items),
                    ];
                }
            }
        }
        
        return $lists;
    }
    
    /**
     * Extract key metrics/statistics
     */
    private function extractKeyMetrics(string $html): array
    {
        $metrics = [];
        
        // Look for large numbers with labels (common metric pattern)
        preg_match_all('/<[^>]*>([\w\s]+)<\/[^>]*>\s*[:\-]?\s*<[^>]*>([\d,.]+(?:[KMBT]|\s*%))/', $html, $matches);
        
        if (!empty($matches[1]) && !empty($matches[2])) {
            for ($i = 0; $i < min(6, count($matches[1])); $i++) {
                $metrics[] = [
                    'label' => trim($matches[1][$i]),
                    'value' => trim($matches[2][$i]),
                ];
            }
        }
        
        return $metrics;
    }
    
    /**
     * Suggest appropriate chart type based on data characteristics
     */
    private function suggestChartType(array $dataSet): string
    {
        $labelCount = count($dataSet['labels']);
        
        // Time series → Line chart
        if (preg_match('/^(20\d{2}|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)/', $dataSet['labels'][0] ?? '')) {
            return 'line';
        }
        
        // Few categories → Bar chart
        if ($labelCount <= 8) {
            return 'bar';
        }
        
        // Many categories → Pie/Doughnut
        if ($labelCount <= 12) {
            return 'doughnut';
        }
        
        // Default to bar
        return 'bar';
    }
    
    /**
     * Render HTML table
     */
    private function renderTable(array $tableData): string
    {
        $html = '<div class="overflow-x-auto">';
        $html .= '<table class="min-w-full text-xs border-collapse">';
        
        // Headers
        $html .= '<thead><tr class="bg-gray-100">';
        foreach ($tableData['headers'] as $header) {
            $html .= '<th class="border border-gray-300 px-2 py-1 text-left font-semibold">' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead>';
        
        // Data rows
        $html .= '<tbody>';
        foreach ($tableData['data'] as $rowIndex => $row) {
            $bgClass = $rowIndex % 2 === 0 ? 'bg-white' : 'bg-gray-50';
            $html .= '<tr class="' . $bgClass . '">';
            foreach ($row as $cell) {
                $html .= '<td class="border border-gray-300 px-2 py-1">' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        
        $html .= '</table>';
        $html .= '<p class="text-[10px] text-gray-500 mt-1">' . $tableData['row_count'] . ' rows × ' . $tableData['col_count'] . ' columns</p>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render Chart.js chart
     */
    private function renderChart(array $dataSet, string $chartType): string
    {
        $chartId = 'chart_' . uniqid();
        $labels = json_encode($dataSet['labels']);
        $values = json_encode($dataSet['values']);
        $title = htmlspecialchars($dataSet['title']);
        $unit = $dataSet['unit'] ?? '';
        
        $html = '<div class="bg-white rounded-xl p-3 border border-gray-200">';
        $html .= '<h4 class="text-xs font-bold mb-2">' . $title . '</h4>';
        $html .= '<canvas id="' . $chartId . '" height="200"></canvas>';
        $html .= '<script>
            (function() {
                const ctx = document.getElementById("' . $chartId . '");
                if (ctx && typeof Chart !== "undefined") {
                    new Chart(ctx, {
                        type: "' . $chartType . '",
                        data: {
                            labels: ' . $labels . ',
                            datasets: [{
                                label: "' . $title . ' (' . $unit . ')",
                                data: ' . $values . ',
                                backgroundColor: [
                                    "rgba(99, 102, 241, 0.6)",
                                    "rgba(168, 85, 247, 0.6)",
                                    "rgba(236, 72, 153, 0.6)",
                                    "rgba(251, 146, 60, 0.6)",
                                    "rgba(34, 197, 94, 0.6)"
                                ],
                                borderColor: [
                                    "rgb(99, 102, 241)",
                                    "rgb(168, 85, 247)",
                                    "rgb(236, 72, 153)",
                                    "rgb(251, 146, 60)",
                                    "rgb(34, 197, 94)"
                                ],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: true, position: "bottom" }
                            },
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                }
            })();
        </script>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render formatted list
     */
    private function renderList(array $listData): string
    {
        $html = '<div class="bg-white rounded-xl p-3 border border-gray-200">';
        $html .= '<h4 class="text-xs font-bold mb-2">' . htmlspecialchars($listData['title']) . ' (' . $listData['count'] . ' items)</h4>';
        $html .= '<ul class="space-y-1">';
        
        foreach (array_slice($listData['items'], 0, 10) as $item) {
            $html .= '<li class="text-xs text-gray-700 flex items-start gap-2">';
            $html .= '<i class="fas fa-check-circle text-green-500 text-[10px] mt-0.5"></i>';
            $html .= '<span>' . htmlspecialchars(substr($item, 0, 150)) . '</span>';
            $html .= '</li>';
        }
        
        if ($listData['count'] > 10) {
            $html .= '<li class="text-[10px] text-gray-500 italic">...and ' . ($listData['count'] - 10) . ' more items</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render key metrics cards
     */
    private function renderMetrics(array $metrics): string
    {
        $html = '<div class="grid grid-cols-2 gap-2">';
        
        foreach ($metrics as $metric) {
            $html .= '<div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-2 border border-blue-200 text-center">';
            $html .= '<div class="text-lg font-bold text-blue-700">' . htmlspecialchars($metric['value']) . '</div>';
            $html .= '<div class="text-[10px] text-gray-600 mt-1">' . htmlspecialchars($metric['label']) . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
