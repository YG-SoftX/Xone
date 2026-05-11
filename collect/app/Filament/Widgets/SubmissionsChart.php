<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class SubmissionsChart extends ChartWidget
{
    protected ?string $heading = 'Submissions Chart';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
