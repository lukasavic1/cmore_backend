<?php
/**
 * date: 9.4.2026.
 * owner: lukasavic18@gmail.com
 *
 * Covers task API behavior with feature tests for CRUD, filters, and
 * statistics endpoints.
 */

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function statsCacheKey(): string
{
    return 'task_stats:'.auth()->id();
}

function taskPayload(array $overrides = []): array
{
    return array_merge([
        'title'       => 'Test Task Title',
        'description' => 'A helpful description.',
        'status'      => TaskStatus::Todo->value,
        'priority'    => TaskPriority::Medium->value,
        'due_date'    => now()->addDays(7)->format('Y-m-d'),
    ], $overrides);
}

// ===========================================================================
// POST /api/v1/tasks — Create
// ===========================================================================

describe('POST /api/v1/tasks', function () {
    it('creates a task with all fields', function () {
        $payload = taskPayload();

        $response = $this->postJson('/api/v1/tasks', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('data.title', $payload['title'])
                 ->assertJsonPath('data.status', 'todo')
                 ->assertJsonPath('data.priority', 'medium')
                 ->assertJsonStructure([
                     'data' => [
                         'id', 'title', 'description', 'status',
                         'priority', 'due_date', 'is_overdue',
                         'created_at', 'updated_at',
                     ],
                 ]);

        $this->assertDatabaseHas('tasks', ['title' => $payload['title']]);
    });

    it('creates a task with only the required title field', function () {
        $response = $this->postJson(
            '/api/v1/tasks',
            ['title' => 'Minimal Task']
        );

        $response->assertStatus(201)
                 ->assertJsonPath('data.title', 'Minimal Task')
                 ->assertJsonPath('data.status', 'todo')
                 ->assertJsonPath('data.priority', 'medium');
    });

    it('invalidates the stats cache on create', function () {
        Cache::put(statsCacheKey(), ['total' => 0], 60);

        $this->postJson('/api/v1/tasks', taskPayload());

        expect(Cache::has(statsCacheKey()))->toBeFalse();
    });

    it('returns 422 when title is missing', function () {
        $response = $this->postJson('/api/v1/tasks', [
            'description' => 'No title here',
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Validation failed.')
                 ->assertJsonStructure(['errors' => ['title']]);
    });

    it('returns 422 when title exceeds 255 characters', function () {
        $response = $this->postJson('/api/v1/tasks', [
            'title' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['title']]);
    });

    it('returns 422 when status is invalid', function () {
        $response = $this->postJson('/api/v1/tasks', taskPayload([
            'status' => 'in_review',
        ]));

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['status']]);
    });

    it('returns 422 when priority is invalid', function () {
        $response = $this->postJson('/api/v1/tasks', taskPayload([
            'priority' => 'critical',
        ]));

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['priority']]);
    });

    it('returns 422 when due_date is not a valid date', function () {
        $response = $this->postJson('/api/v1/tasks', taskPayload([
            'due_date' => 'not-a-date',
        ]));

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['due_date']]);
    });
});

// ===========================================================================
// GET /api/v1/tasks — List
// ===========================================================================

describe('GET /api/v1/tasks', function () {
    it('returns a paginated list of all tasks', function () {
        Task::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [['id', 'title', 'status', 'priority']],
                     'meta' => [
                         'current_page',
                         'per_page',
                         'total',
                         'last_page',
                     ],
                     'links' => ['first', 'last', 'prev', 'next'],
                 ])
                 ->assertJsonPath('meta.total', 5);
    });

    it('returns an empty list when no tasks exist', function () {
        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.total', 0)
                 ->assertJsonCount(0, 'data');
    });

    it('respects the per_page query parameter', function () {
        Task::factory()->count(10)->create();

        $response = $this->getJson('/api/v1/tasks?per_page=3');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.per_page', 3)
                 ->assertJsonCount(3, 'data');
    });
});

// ===========================================================================
// GET /api/v1/tasks — Filter by status
// ===========================================================================

describe('GET /api/v1/tasks?status=', function () {
    it('filters tasks by todo status', function () {
        Task::factory()->count(3)->pending()->create();
        Task::factory()->count(2)->inProgress()->create();
        Task::factory()->count(2)->completed()->create();

        $response = $this->getJson('/api/v1/tasks?status=todo');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.total', 3);

        collect($response->json('data'))->each(
            fn ($task) => expect($task['status'])->toBe('todo')
        );
    });

    it('filters tasks by in_progress status', function () {
        Task::factory()->count(3)->pending()->create();
        Task::factory()->count(2)->inProgress()->create();
        Task::factory()->count(2)->completed()->create();

        $response = $this->getJson('/api/v1/tasks?status=in_progress');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.total', 2);

        collect($response->json('data'))->each(
            fn ($task) => expect($task['status'])->toBe('in_progress')
        );
    });

    it('filters tasks by completed status', function () {
        Task::factory()->count(3)->pending()->create();
        Task::factory()->count(2)->completed()->create();

        $response = $this->getJson('/api/v1/tasks?status=completed');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.total', 2);

        collect($response->json('data'))->each(
            fn ($task) => expect($task['status'])->toBe('completed')
        );
    });
});

// ===========================================================================
// GET /api/v1/tasks — Search
// ===========================================================================

describe('GET /api/v1/tasks?search=', function () {
    it('searches tasks by title', function () {
        Task::factory()->create(['title' => 'Fix the login bug']);
        Task::factory()->create(['title' => 'Update dependencies']);

        $response = $this->getJson('/api/v1/tasks?search=login');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.total', 1)
                 ->assertJsonPath('data.0.title', 'Fix the login bug');
    });

    it('searches tasks by description', function () {
        Task::factory()->create([
            'title'       => 'Routine task',
            'description' => 'This involves upgrading the payment gateway.',
        ]);
        Task::factory()->create([
            'title'       => 'Another task',
            'description' => 'Nothing special here.',
        ]);

        $response = $this->getJson('/api/v1/tasks?search=payment+gateway');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.total', 1)
                 ->assertJsonPath('data.0.title', 'Routine task');
    });

    it('combines status filter with search', function () {
        Task::factory()->pending()->create(['title' => 'Todo search task']);
        Task::factory()->completed()->create([
            'title' => 'Completed search task',
        ]);
        Task::factory()->pending()->create(['title' => 'Unrelated todo task']);

        $response = $this->getJson('/api/v1/tasks?status=todo&search=search');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.total', 1)
                 ->assertJsonPath('data.0.title', 'Todo search task');
    });

    it('returns empty when search finds no matches', function () {
        Task::factory()->count(3)->create();

        $response = $this->getJson(
            '/api/v1/tasks?search=xyzzy_does_not_exist'
        );

        $response->assertStatus(200)
                 ->assertJsonPath('meta.total', 0);
    });
});

// ===========================================================================
// GET /api/v1/tasks — Filter by assignee
// ===========================================================================

describe('GET /api/v1/tasks assignee filters', function () {
    it('filters tasks by exact assignee string', function () {
        Task::factory()->create(['assignee' => 'Backend team']);
        Task::factory()->create(['assignee' => 'Frontend team']);
        Task::factory()->create(['assignee' => 'Backend team']);

        $response = $this->getJson(
            '/api/v1/tasks?assignee=' . rawurlencode('Backend team')
        );

        $response->assertStatus(200)
                 ->assertJsonPath('meta.total', 2);

        collect($response->json('data'))->each(
            fn ($task) => expect($task['assignee'])->toBe('Backend team')
        );
    });

    it('filters unassigned tasks when unassigned=1', function () {
        Task::factory()->create(['assignee' => null]);
        Task::factory()->create(['assignee' => '']);
        Task::factory()->create(['assignee' => 'Backend team']);

        $response = $this->getJson('/api/v1/tasks?unassigned=1');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.total', 2);
    });

    it('prefers unassigned over assignee when both are sent', function () {
        Task::factory()->create(['assignee' => null]);
        Task::factory()->create(['assignee' => 'Backend team']);

        $response = $this->getJson(
            '/api/v1/tasks?unassigned=1&assignee=Backend+team'
        );

        $response->assertStatus(200)
                 ->assertJsonPath('meta.total', 1);
    });
});

// ===========================================================================
// GET /api/v1/tasks/assignees
// ===========================================================================

describe('GET /api/v1/tasks/assignees', function () {
    it('returns distinct assignee labels for the current user', function () {
        Task::factory()->create(['assignee' => 'Alpha']);
        Task::factory()->create(['assignee' => 'Beta']);
        Task::factory()->create(['assignee' => 'Alpha']);
        Task::factory()->create(['assignee' => null]);

        $response = $this->getJson('/api/v1/tasks/assignees');

        $response->assertStatus(200)
                 ->assertJsonPath('data', ['Alpha', 'Beta']);
    });

    it('does not include assignees from other users', function () {
        $other = User::factory()->create();
        Task::factory()->create([
            'assignee' => 'Mine',
            'user_id' => auth()->id(),
        ]);
        Task::factory()->create([
            'assignee' => 'Theirs',
            'user_id' => $other->id,
        ]);

        $response = $this->getJson('/api/v1/tasks/assignees');

        $response->assertStatus(200)
                 ->assertJsonPath('data', ['Mine']);
    });
});

// ===========================================================================
// GET /api/v1/tasks/{id} — Show
// ===========================================================================

describe('GET /api/v1/tasks/{id}', function () {
    it('returns a single task', function () {
        $task = Task::factory()->create();

        $response = $this->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $task->id)
                 ->assertJsonPath('data.title', $task->title);
    });

    it('returns 404 when the task belongs to another user', function () {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(404);
    });

    it('returns 404 for a non-existent task', function () {
        $response = $this->getJson('/api/v1/tasks/99999');

        $response->assertStatus(404)
                 ->assertJsonPath('message', 'Resource not found.');
    });
});

// ===========================================================================
// PUT /api/v1/tasks/{id} — Update
// ===========================================================================

describe('PUT /api/v1/tasks/{id}', function () {
    it('updates a task', function () {
        $task = Task::factory()->pending()->create();

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'title'    => 'Updated Title',
            'status'   => 'completed',
            'priority' => 'high',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.title', 'Updated Title')
                 ->assertJsonPath('data.status', 'completed')
                 ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('tasks', [
            'id'     => $task->id,
            'title'  => 'Updated Title',
            'status' => 'completed',
        ]);
    });

    it('invalidates stats cache on update', function () {
        Cache::put(statsCacheKey(), ['total' => 1], 60);
        $task = Task::factory()->create();

        $this->putJson("/api/v1/tasks/{$task->id}", ['title' => 'New Title']);

        expect(Cache::has(statsCacheKey()))->toBeFalse();
    });

    it('returns 422 when update title is empty', function () {
        $task = Task::factory()->create();

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'title' => '',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['title']]);
    });

    it('returns 404 for non-existent task', function () {
        $response = $this->putJson('/api/v1/tasks/99999', ['title' => 'Nope']);

        $response->assertStatus(404);
    });
});

// ===========================================================================
// DELETE /api/v1/tasks/{id} — Destroy
// ===========================================================================

describe('DELETE /api/v1/tasks/{id}', function () {
    it('deletes a task and returns 204', function () {
        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    });

    it('invalidates stats cache on delete', function () {
        Cache::put(statsCacheKey(), ['total' => 1], 60);
        $task = Task::factory()->create();

        $this->deleteJson("/api/v1/tasks/{$task->id}");

        expect(Cache::has(statsCacheKey()))->toBeFalse();
    });

    it('returns 404 when deleting non-existent task', function () {
        $response = $this->deleteJson('/api/v1/tasks/99999');

        $response->assertStatus(404);
    });
});

// ===========================================================================
// PATCH /api/v1/tasks/{id}/toggle — Toggle status
// ===========================================================================

describe('PATCH /api/v1/tasks/{id}/toggle', function () {
    it('toggles a todo task to completed', function () {
        $task = Task::factory()->pending()->create();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}/toggle");

        $response->assertStatus(200)
                 ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('tasks', [
            'id'     => $task->id,
            'status' => 'completed',
        ]);
    });

    it('toggles a completed task back to todo', function () {
        $task = Task::factory()->completed()->create();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}/toggle");

        $response->assertStatus(200)
                 ->assertJsonPath('data.status', 'todo');
    });

    it('invalidates stats cache on toggle', function () {
        Cache::put(statsCacheKey(), ['total' => 1, 'todo' => 1], 60);
        $task = Task::factory()->pending()->create();

        $this->patchJson("/api/v1/tasks/{$task->id}/toggle");

        expect(Cache::has(statsCacheKey()))->toBeFalse();
    });

    it('returns 404 when toggling non-existent task', function () {
        $response = $this->patchJson('/api/v1/tasks/99999/toggle');

        $response->assertStatus(404);
    });
});

// ===========================================================================
// GET /api/v1/stats — Stats endpoint
// ===========================================================================

describe('GET /api/v1/stats', function () {
    it('returns correct stats', function () {
        Task::factory()->count(3)->pending()->create([
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ]);
        Task::factory()->count(2)->inProgress()->create([
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ]);
        Task::factory()->count(2)->completed()->create();
        Task::factory()->count(2)->overdue()->create();

        $response = $this->getJson('/api/v1/stats');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'total',
                         'completed',
                         'todo',
                         'in_progress',
                         'overdue',
                     ],
                 ])
                 ->assertJsonPath('data.total', 9)
                 ->assertJsonPath('data.completed', 2)
                 ->assertJsonPath(
                    'data.todo',
                    5
                ) // 3 + 2 overdue (still todo)
                 ->assertJsonPath('data.in_progress', 2)
                 ->assertJsonPath('data.overdue', 2);
    });

    it('returns zeros when no tasks exist', function () {
        $response = $this->getJson('/api/v1/stats');

        $response->assertStatus(200)
                 ->assertJsonPath('data.total', 0)
                 ->assertJsonPath('data.completed', 0)
                 ->assertJsonPath('data.todo', 0)
                 ->assertJsonPath('data.in_progress', 0)
                 ->assertJsonPath('data.overdue', 0);
    });

    it('caches the stats result', function () {
        Task::factory()->count(2)->create();

        // First call — populates cache
        $this->getJson('/api/v1/stats');

        expect(Cache::has(statsCacheKey()))->toBeTrue();
    });

    it('serves cached stats without hitting the database again', function () {
        Cache::put(statsCacheKey(), [
            'total'     => 99,
            'completed' => 50,
            'todo'      => 40,
            'in_progress' => 9,
            'overdue'   => 5,
        ], 60);

        $response = $this->getJson('/api/v1/stats');

        $response->assertStatus(200)
                 ->assertJsonPath('data.total', 99)
                 ->assertJsonPath('data.completed', 50);
    });
});
