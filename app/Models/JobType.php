<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Worker;
use App\Models\Request;

class JobType extends Model
{
     protected $table = 'job_types';
    protected $fillable = ['name'];

    public function workers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Worker::class);
    }

    public function requests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Request::class);
    }
}