<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hole extends Model
{
    use HasFactory;

    protected $fillable = [
        'golf_course_id',
        'hole_number',
        'par',
        'length_yards',
        'tee_points',
        'green_point',
        'centerline',
    ];

    protected $casts = [
        'hole_number' => 'integer',
        'par' => 'integer',
        'length_yards' => 'integer',
        'tee_points' => 'array',
        'green_point' => 'array',
        'centerline' => 'array',
    ];

    /**
     * Campo golf
     */
    public function golfCourse(): BelongsTo
    {
        return $this->belongsTo(GolfCourse::class);
    }

    /**
     * Drive su questa buca
     */
    public function drives(): HasMany
    {
        return $this->hasMany(Drive::class);
    }
}
