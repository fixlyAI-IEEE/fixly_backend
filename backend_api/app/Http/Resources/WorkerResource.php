<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'is_available' => $this->is_available,
            'is_verified'  => $this->is_verified,
            'rating'       => $this->rating,
            'job_type'     => $this->when(
                $this->relationLoaded('jobType'),
                fn () => [
                    'id'   => $this->jobType->id,
                    'name' => $this->jobType->name,
                ]
            ),
            'working_days' => $this->working_days,
        ];
    }
}