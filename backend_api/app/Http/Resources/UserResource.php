<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
  public function toArray(Request $request): array
{
    return [
        'id'                => $this->id,
        'name'              => $this->name,
        'phone'             => $this->phone,
        'role'              => $this->role,
        'city'              => $this->city,
        'areas'             => $this->areas,
        'profile_picture'   => $this->profile_picture
            ? asset('storage/' . $this->profile_picture)
            : null,
        'is_verified'       => ! is_null($this->phone_verified_at),
        'worker'            => $this->whenLoaded('worker', fn () => [
            'id'           => $this->worker->id,
            'job_type'     => $this->worker->jobType?->name,
            'is_available' => $this->worker->is_available,
            'rating'       => $this->worker->rating,
        ]),
        'created_at'        => $this->created_at->toDateTimeString(),
    ];
}
}