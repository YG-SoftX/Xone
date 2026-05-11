<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\Folder;
use App\Models\Template;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocxStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Documents', Document::count())
                ->description(Document::whereDate('created_at', today())->count() . ' created today')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
            Stat::make('Published', Document::where('status', 'published')->count())
                ->description('Live documents')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success'),
            Stat::make('Total Folders', Folder::count())
                ->description('Organized folders')
                ->descriptionIcon('heroicon-m-folder')
                ->color('info'),
            Stat::make('Templates', Template::count())
                ->description(Template::where('is_public', true)->count() . ' public')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('warning'),
            Stat::make('Shared Docs', DocumentShare::count())
                ->description('Active shares')
                ->descriptionIcon('heroicon-m-share')
                ->color('secondary'),
            Stat::make('Total Users', User::count())
                ->description('Registered')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
