<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'phone'; 
    protected $keyType = 'string';   
    public $incrementing = false;    
    protected $fillable = [
        'phone',
        'otp',
        'is_verified',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'expires_at'  => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}