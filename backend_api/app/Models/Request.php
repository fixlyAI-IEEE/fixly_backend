<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Request extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'job_type_id',
        'accepted_worker_id',
        'status',
        'description',
        'city',
    ];

    // ── Relations ──────────────────────────────────────────────────

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobType::class);
    }

    public function acceptedWorker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Worker::class, 'accepted_worker_id');
    }

    // All workers this request was broadcast to
    public function workers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Worker::class, 'request_workers')
            ->withPivot('status')
            ->withTimestamps();
    }

    // Only workers who sent an offer (pivot status = offered)
    public function offeredWorkers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Worker::class, 'request_workers')
            ->withPivot('status')
            ->wherePivot('status', 'offered')
            ->withTimestamps();
    }

    public function ratings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rating::class);
    }

    // ── Status helpers ─────────────────────────────────────────────

    public function isPending(): bool   { return $this->status === null; }
    public function isAccepted(): bool  { return $this->status === 'accepted'; }
    public function isRejected(): bool  { return $this->status === 'rejected'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
}