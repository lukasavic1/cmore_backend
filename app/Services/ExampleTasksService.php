<?php

namespace App\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\User;

class ExampleTasksService
{
    public function __construct(private readonly TaskService $taskService)
    {
    }

    /**
     * A few starter tasks so new accounts see the kanban columns without clutter.
     */
    public function seedForUser(User $user): void
    {
        $rows = [
            [
                'title'       => 'Plan sustainability report',
                'description' => 'Example in To do — drag this to In progress or Completed.',
                'assignee'    => 'Luka',
                'status'      => TaskStatus::Todo->value,
                'priority'    => TaskPriority::Medium->value,
                'due_date'    => now()->addWeek()->format('Y-m-d'),
            ],
            [
                'title'       => 'Review ESG metrics',
                'description' => 'Example in progress — move it across columns to show the flow.',
                'assignee'    => 'Luka',
                'status'      => TaskStatus::InProgress->value,
                'priority'    => TaskPriority::High->value,
                'due_date'    => now()->addDays(3)->format('Y-m-d'),
            ],
            [
                'title'       => 'Publish quarterly update',
                'description' => 'Example completed — drag back to To do if you want to demo again.',
                'assignee'    => 'Vladimir',
                'status'      => TaskStatus::Completed->value,
                'priority'    => TaskPriority::Low->value,
                'due_date'    => now()->subDays(2)->format('Y-m-d'),
            ],
        ];

        foreach ($rows as $row) {
            $this->taskService->create(array_merge($row, ['user_id' => $user->id]));
        }
    }
}
