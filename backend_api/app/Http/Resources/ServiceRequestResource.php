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
        'status'      => $this->status ?? 'pending',

        'job_type' => $this->whenLoaded('jobType', fn () => [
            'id'   => $this->jobType->id,
            'name' => $this->jobType->name,
        ]),

        // Who the user finally confirmed
        'accepted_worker' => $this->whenLoaded(
            'acceptedWorker',
            fn () => $this->acceptedWorker
                ? new WorkerResource($this->acceptedWorker)
                : null
        ),

        // Workers who sent offers (used in GET /requests/{id}/offers)
        'offered_workers' => $this->whenLoaded(
            'offeredWorkers',
            fn () => $this->offeredWorkers->map(fn ($worker) => [
                'worker_id' => $worker->id,
                'name'      => $worker->user?->name,
                'rating'    => $worker->rating,
                'pivot_status' => $worker->pivot?->status,
            ])
        ),

        // Worker inbox: show their own pivot status
        'pivot_status' => $this->when(
            isset($this->pivot_status),  // only present in workerInbox join query
            fn () => $this->pivot_status
        ),

        // Only expose workers list if explicitly loaded (avoid leaking all workers to user)
        'workers' => $this->whenLoaded(
            'workers',
            fn () => WorkerResource::collection($this->workers)
        ),

        'created_at' => $this->created_at->toDateTimeString(),
    ];
}
}