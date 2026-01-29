<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Measurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'drive_id',
        'type',
        'point_a_lat',
        'point_a_lng',
        'point_b_lat',
        'point_b_lng',
        'distance_yards',
    ];

    protected $casts = [
        'point_a_lat' => 'decimal:7',
        'point_a_lng' => 'decimal:7',
        'point_b_lat' => 'decimal:7',
        'point_b_lng' => 'decimal:7',
        'distance_yards' => 'decimal:2',
    ];

    /**
     * Drive
     */
    public function drive(): BelongsTo
    {
        return $this->belongsTo(Drive::class);
    }

    /**
     * Punto A
     */
    public function getPointAAttribute(): array
    {
        return [
            'lat' => (float) $this->point_a_lat,
            'lng' => (float) $this->point_a_lng,
        ];
    }

    /**
     * Punto B
     */
    public function getPointBAttribute(): array
    {
        return [
            'lat' => (float) $this->point_b_lat,
            'lng' => (float) $this->point_b_lng,
        ];
    }
}
