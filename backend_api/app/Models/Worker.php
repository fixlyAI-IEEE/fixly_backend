<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Worker extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'user_id',
        'job_type_id',
        'is_available',
        'is_verified',
        'rating',
        'avg_price',
        'working_days',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'is_verified'  => 'boolean',
            'rating'       => 'float',
            'working_days' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobType::class);
    }

    public function requests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Request::class);
    }

    public function ratings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rating::class);
    }
}