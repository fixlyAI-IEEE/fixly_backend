<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'description' => $this->description,
            'city'        => $this->city,
            'status'      => $this->status ?? 'pending',   // null in DB = pending
            'job_type'    => $this->whenLoaded('jobType', fn () => [
                'id'   => $this->jobType->id,
                'name' => $this->jobType->name,
            ]),
            'accepted_worker' => $this->whenLoaded(
                'acceptedWorker',
                fn () => $this->acceptedWorker
                    ? new WorkerResource($this->acceptedWorker)
                    : null
            ),
            'workers'    => $this->whenLoaded(
                'workers',
                fn () => WorkerResource::collection($this->workers)
            ),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}