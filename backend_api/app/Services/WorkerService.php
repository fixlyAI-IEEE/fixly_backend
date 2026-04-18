<?php

namespace App\Services;

use App\Models\Worker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkerService
{
    /**
     * Return a paginated list of available, verified workers
     * optionally filtered by job_type_id and/or city.
     */
    public function listing(array $filters): LengthAwarePaginator
    {
        return Worker::query()
            ->with(['user', 'jobType'])
            ->where('is_available', true)
            ->where('is_verified', true)
            ->when(
                ! empty($filters['job_type_id']),
                fn ($q) => $q->where('job_type_id', $filters['job_type_id'])
            )
            ->when(
                ! empty($filters['city']),
                fn ($q) => $q->whereHas(
                    'user',
                    fn ($u) => $u->where('city', 'like', '%' . $filters['city'] . '%')
                )
            )
            ->when(
                ! empty($filters['areas']),
                fn ($q) => $q->whereHas(
                    'user',
                    fn ($u) => $u->where('areas', 'like', '%' . $filters['areas'] . '%')
                )
            )
            ->when(
                isset($filters['min_rating']) && $filters['min_rating'] !== null,
                fn ($q) => $q->where('rating', '>=', $filters['min_rating'])
            )
            ->orderByDesc('rating')
            ->paginate($filters['per_page'] ?? 15);
    }
}