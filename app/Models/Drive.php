<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drive extends Model
{
    use HasFactory;

    protected $fillable = [
        'hole_id',
        'user_id',
        'tee_lat',
        'tee_lng',
        'total_distance_meters',
        'total_distance_yards',
        'num_shots',
        'shots',
    ];

    protected $casts = [
        'tee_lat' => 'decimal:7',
        'tee_lng' => 'decimal:7',
        'total_distance_meters' => 'decimal:2',
        'total_distance_yards' => 'decimal:2',
        'num_shots' => 'integer',
        'shots' => 'array',
    ];

    /**
     * Buca
     */
    public function hole(): BelongsTo
    {
        return $this->belongsTo(Hole::class);
    }

    /**
     * Utente
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Misurazioni larghezza
     */
    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    /**
     * Coordinate tee
     */
    public function getTeeCoordinatesAttribute(): array
    {
        return [
            'lat' => (float) $this->tee_lat,
            'lng' => (float) $this->tee_lng,
        ];
    }
}
