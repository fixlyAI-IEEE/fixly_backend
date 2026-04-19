<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCycle extends Model
{
    protected $fillable = [
        'worker_id',
        'cycle_number',
        'completed_jobs',
        'amount_due',
        'amount_paid',
        'status',
        'proof_image',
        'proof_uploaded_at',
        'paid_at',
        'cycle_started_at',
        'cycle_ended_at',
    ];

    protected $casts = [
        'proof_uploaded_at' => 'datetime',
        'paid_at'           => 'datetime',
        'cycle_started_at'  => 'datetime',
        'cycle_ended_at'    => 'datetime',
    ];

    public function worker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function isPending(): bool        { return $this->status === 'pending'; }
    public function isProofUploaded(): bool  { return $this->status === 'proof_uploaded'; }
    public function isPaid(): bool           { return $this->status === 'paid'; }
    public function isRejected(): bool       { return $this->status === 'rejected'; }
}