<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
{
  public function toArray(Request $request): array
{
    return [
        'id'              => $this->id,
        'name'            => $this->whenLoaded('user', fn () => $this->user->name),
        'phone'           => $this->whenLoaded('user', fn () => $this->user->phone),
        'city'            => $this->whenLoaded('user', fn () => $this->user->city),
        'areas'           => $this->whenLoaded('user', fn () => $this->user->areas),
        'profile_picture' => $this->whenLoaded('user', fn () => $this->user->profile_picture
            ? asset('storage/' . $this->user->profile_picture)
            : null
        ),
        'is_available'    => $this->is_available,
        'is_verified'     => $this->is_verified,
        'rating'          => $this->rating,
        'avg_price'       => $this->avg_price,
        'job_type'        => $this->when(
            $this->relationLoaded('jobType'),
            fn () => [
                'id'   => $this->jobType->id,
                'name' => $this->jobType->name,
            ]
        ),
        'working_days'    => $this->working_days,
    ];
}
}