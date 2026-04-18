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
        'worker_id',
        'job_type_id',
        'status',
        'description',
        'city',
        'status',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function worker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function jobType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobType::class);
    }
     public function workers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Worker::class, 'request_workers')
                    ->withPivot('status')
                    ->withTimestamps();
    }
 
    // ── Helpers ────────────────────────────────────────────────────
 
    public function isAccepted(): bool { return $this->status === 'accepted'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
    public function isPending(): bool  { return $this->status === null; }
}