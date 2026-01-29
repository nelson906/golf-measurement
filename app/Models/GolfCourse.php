<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GolfCourse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'latitude',
        'longitude',
        'map_image_path',
        'overlay_config',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'overlay_config' => 'array',
    ];

    /**
     * Buche del campo
     */
    public function holes(): HasMany
    {
        return $this->hasMany(Hole::class)->orderBy('hole_number');
    }

    /**
     * URL completo della mappa
     */
    public function getMapUrlAttribute(): ?string
    {
        return $this->map_image_path 
            ? asset('storage/' . $this->map_image_path)
            : null;
    }

    /**
     * Coordinate per mappa
     */
    public function getCoordinatesAttribute(): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }
}
