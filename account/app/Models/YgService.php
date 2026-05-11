<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YgService extends Model
{
    protected $table = 'yg_services';

    protected $fillable = [
        'service_key',
        'service_name',
        'url',
        'is_active',
        'is_maintenance',
        'status',
        'port',
        'health_check_url',
        'last_health_check',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_maintenance' => 'boolean',
        'metadata' => 'array',
        'last_health_check' => 'datetime',
    ];

    public function checkHealth()
    {
        if (!$this->health_check_url) {
            return 'unknown';
        }

        try {
            $response = @file_get_contents($this->health_check_url);
            $this->update([
                'last_health_check' => now(),
                'status' => $response !== false ? 'operational' : 'down',
            ]);
            return $response !== false ? 'operational' : 'down';
        } catch (\Exception $e) {
            $this->update([
                'last_health_check' => now(),
                'status' => 'down',
            ]);
            return 'down';
        }
    }
}
