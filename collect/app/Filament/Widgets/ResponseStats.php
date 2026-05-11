<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResponseStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Projects', \App\Models\CollectProject::count())
                ->description('Total data collection projects')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('info'),
            Stat::make('Active Forms', \App\Models\CollectForm::where('status', 'published')->count())
                ->description('Forms accepting submissions')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Total Responses', \App\Models\CollectSubmission::count())
                ->description('Submissions collected')
                ->descriptionIcon('heroicon-m-document-text')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('primary'),
        ];
    }
}
