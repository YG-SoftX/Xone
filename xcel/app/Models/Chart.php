<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chart extends Model
{
    use HasFactory;

    protected $fillable = [
        'sheet_id',
        'chart_type',
        'title',
        'data_range',
        'position_x',
        'position_y',
        'width',
        'height',
        'config_json',
    ];

    protected $casts = [
        'config_json' => 'array',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * The parent sheet.
     */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(Sheet::class);
    }

    /**
     * Get the cells that this chart references.
     *
     * @return \Illuminate\Support\Collection<Cell>
     */
    public function getDataRange(): \Illuminate\Support\Collection
    {
        $parts = explode(':', $this->data_range);

        if (count($parts) !== 2) {
            return collect();
        }

        return $this->sheet->getRange($parts[0], $parts[1]);
    }

    /**
     * Render the chart as an image URL placeholder.
     * In a real implementation, this would generate a chart image.
     */
    public function renderAsImage(): string
    {
        $config = json_encode([
            'type' => $this->chart_type,
            'title' => $this->title,
            'data_range' => $this->data_range,
            'config' => $this->config_json,
        ]);

        $encoded = base64_encode($config);

        return "data:image/svg+xml;base64," . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="' . $this->width . '" height="' . $this->height . '">'
            . '<text x="50%" y="50%" text-anchor="middle">' . htmlspecialchars($this->title) . '</text>'
            . '</svg>'
        );
    }
}
