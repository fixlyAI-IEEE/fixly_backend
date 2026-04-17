<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'phone'  => $this->phone,
            'role'   => $this->role,
            'city'   => $this->city,
            'areas'  => $this->areas,
            'worker' => $this->when(
                $this->relationLoaded('worker') && $this->worker,
                fn () => new WorkerResource($this->worker)
            ),
        ];
    }
}