<?php

namespace App\Filament\Widgets;

use App\Services\GrafanaExporter;
use Filament\Widgets\Widget;

class GrafanaDashboardWidget extends Widget
{
    protected static ?string $pollingInterval = '300s';
    
    protected int|string|array $columnSpan = 'full';
    
    protected static string $view = 'filament.widgets.grafana-dashboard-widget';

    public function getDashboardUrl(): ?string
    {
        $exporter = app(GrafanaExporter::class);
        return $exporter->getDashboardUrl();
    }

    public function isConnected(): bool
    {
        $exporter = app(GrafanaExporter::class);
        return $exporter->isConfigured();
    }

    public function getLastExport(): ?string
    {
        // TODO: Query last export timestamp from database or cache
        return now()->subMinutes(5)->format('Y-m-d H:i:s');
    }
}
