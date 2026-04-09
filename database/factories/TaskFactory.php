<?php
/**
 * date: 9.4.2026.
 * owner: lukasavic18@gmail.com
 *
 * Generates realistic task model data for tests and database seeding
 * scenarios.
 */

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'user_id'     => fn () => auth()->check()
                ? auth()->id()
                : User::factory()->create()->id,
            'title'       => $this->faker->sentence(4),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'assignee'    => $this->faker->optional(0.75)->randomElement([
                'Frontend team',
                'Backend team',
                'DevOps',
                'External IT firm',
                'QA',
                'Product ops',
            ]),
            'status'      => $this->faker
                ->randomElement(TaskStatus::cases())
                ->value,
            'priority'    => $this->faker
                ->randomElement(TaskPriority::cases())
                ->value,
            'due_date'    => $this->faker
                ->optional(0.6)
                ->dateTimeBetween('-1 week', '+2 months')?->format('Y-m-d'),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => TaskStatus::Todo->value]);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => TaskStatus::InProgress->value]);
    }

    public function completed(): static
    {
        return $this->state(['status' => TaskStatus::Completed->value]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status'   => TaskStatus::Todo->value,
            'due_date' => now()->subDays(3)->format('Y-m-d'),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => TaskPriority::High->value]);
    }
}
