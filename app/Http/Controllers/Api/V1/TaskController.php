<?php
/**
 * date: 9.4.2026.
 * owner: lukasavic18@gmail.com
 *
 * Exposes task API endpoints for listing, creating, updating, and
 * deleting tasks.
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskCollection;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService)
    {
    }

    /**
     * GET /api/v1/tasks
     *
     * List all tasks with optional ?status=, ?search=,
     * ?assignee=, and ?unassigned=1 filters.
     */
    public function index(Request $request): TaskCollection
    {
        $tasks = $this->taskService->list(
            array_merge(
                $request->only(['status', 'search', 'per_page', 'assignee']),
                ['unassigned' => $request->boolean('unassigned')]
            ),
            $request->user()->id
        );

        return new TaskCollection($tasks);
    }

    /**
     * GET /api/v1/tasks/assignees
     *
     * Distinct assignee strings for the current user (for filter dropdowns).
     */
    public function assignees(Request $request): JsonResponse
    {
        $labels = $this->taskService->distinctAssignees($request->user()->id);

        return response()->json(['data' => $labels]);
    }

    /**
     * POST /api/v1/tasks
     *
     * Create a new task.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create(array_merge(
            $request->validated(),
            ['user_id' => $request->user()->id]
        ));

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/tasks/{task}
     *
     * Show a single task.
     */
    public function show(Task $task): TaskResource
    {
        return new TaskResource($task);
    }

    /**
     * PUT /api/v1/tasks/{task}
     *
     * Update a task.
     */
    public function update(
        UpdateTaskRequest $request,
        Task $task
    ): TaskResource
    {
        $task = $this->taskService->update($task, $request->validated());

        return new TaskResource($task);
    }

    /**
     * DELETE /api/v1/tasks/{task}
     *
     * Delete a task.
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->taskService->delete($task);

        return response()->json(null, 204);
    }

    /**
     * PATCH /api/v1/tasks/{task}/toggle
     *
     * Toggle a task's status between todo and completed.
     */
    public function toggle(Task $task): TaskResource
    {
        $task = $this->taskService->toggleStatus($task);

        return new TaskResource($task);
    }

    /**
     * GET /api/v1/stats
     *
     * Return aggregated task statistics (cached).
     */
    public function stats(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->taskService->stats($request->user()->id),
        ]);
    }
}
