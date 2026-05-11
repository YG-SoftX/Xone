<?php

namespace App\Filament\Widgets;

use App\Models\DriveFile;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class StorageUsageChart extends ChartWidget
{
    protected static ?string $heading = 'Files Uploaded (Last 30 Days)';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = DriveFile::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(size) as total_size')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $counts = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::now()->subDays($i)->format('M d');
            $row = $data->firstWhere('date', $date);
            $counts[] = $row ? $row->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Files Uploaded',
                    'data'            => $counts,
                    'backgroundColor' => 'rgba(26, 115, 232, 0.2)',
                    'borderColor'     => 'rgba(26, 115, 232, 1)',
                    'borderWidth'     => 2,
                    'fill'            => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
