<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'job_type_id',
        'message',
        'response',
    ];

    protected $casts = [
        'job_type_id' => 'integer',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobType::class);
    }
}