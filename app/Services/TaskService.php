<?php
/**
 * date: 9.4.2026.
 * owner: lukasavic18@gmail.com
 *
 * Contains business logic for task CRUD operations, filtering,
 * statistics, and cache invalidation.
 */

namespace App\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class TaskService
{
    private const STATS_CACHE_TTL = 60; // seconds

    private function statsCacheKey(int $userId): string
    {
        return 'task_stats:'.$userId;
    }

    /*
    |--------------------------------------------------------------------------
    | Listing & Retrieval
    |--------------------------------------------------------------------------
    */

    /**
     * Return a paginated, optionally filtered list of tasks for one user.
     */
    public function list(array $filters, int $userId): LengthAwarePaginator
    {
        $query = Task::query()->where('user_id', $userId)->latest();

        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['unassigned'])) {
            $query->unassigned();
        } elseif (!empty($filters['assignee'])) {
            $query->where('assignee', $filters['assignee']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Distinct non-empty assignee labels for one user (for filter UI).
     *
     * @return list<string>
     */
    public function distinctAssignees(int $userId): array
    {
        return Task::query()
            ->where('user_id', $userId)
            ->whereNotNull('assignee')
            ->where('assignee', '!=', '')
            ->distinct()
            ->orderBy('assignee')
            ->pluck('assignee')
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Mutations
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Task
    {
        $data = $this->dropNullEnumColumns($data);
        // Always set enum columns on the model. DB defaults are not synced
        // back after insert, so omitted keys leave raw attributes missing
        // and enum casts become null.
        $data = array_merge(
            [
                'status'   => TaskStatus::Todo->value,
                'priority' => TaskPriority::Medium->value,
            ],
            $data
        );

        $task = Task::create($data);

        if ($task->user_id !== null) {
            $this->invalidateStatsCacheForUser($task->user_id);
        }

        return $task;
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($this->dropNullEnumColumns($data));

        if ($task->user_id !== null) {
            $this->invalidateStatsCacheForUser($task->user_id);
        }

        return $task->fresh();
    }

    public function delete(Task $task): void
    {
        $userId = $task->user_id;
        $task->delete();

        if ($userId !== null) {
            $this->invalidateStatsCacheForUser($userId);
        }
    }

    public function toggleStatus(Task $task): Task
    {
        $task->update([
            'status' => $task->status->toggled()->value,
        ]);

        if ($task->user_id !== null) {
            $this->invalidateStatsCacheForUser($task->user_id);
        }

        return $task->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    public function stats(int $userId): array
    {
        return Cache::remember(
            $this->statsCacheKey($userId),
            self::STATS_CACHE_TTL,
            function () use ($userId) {
            $base = Task::query()->where('user_id', $userId);

            $total = (clone $base)->count();
                $completed = (clone $base)
                    ->where('status', TaskStatus::Completed->value)
                    ->count();
                $todo = (clone $base)
                    ->where('status', TaskStatus::Todo->value)
                    ->count();
                $inProgress = (clone $base)
                    ->where('status', TaskStatus::InProgress->value)
                    ->count();
            $overdue = (clone $base)->overdue()->count();

            return [
                'total'         => $total,
                'completed'     => $completed,
                'todo'          => $todo,
                'in_progress'   => $inProgress,
                'overdue'       => $overdue,
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Cache Helpers
    |--------------------------------------------------------------------------
    */

    public function invalidateStatsCacheForUser(int $userId): void
    {
        Cache::forget($this->statsCacheKey($userId));
    }

    /**
     * Avoid persisting null for enum columns:
     * validated JSON can include explicit nulls,
     * which overrides DB defaults and breaks enum casts in TaskResource.
     */
    private function dropNullEnumColumns(array $data): array
    {
        foreach (['status', 'priority'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $v = $data[$key];
            if ($v === null || $v === '') {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
