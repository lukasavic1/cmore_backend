<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match($this) {
            TaskStatus::Todo       => 'To do',
            TaskStatus::InProgress => 'In progress',
            TaskStatus::Completed => 'Completed',
        };
    }

    public function toggled(): self
    {
        return match($this) {
            TaskStatus::Completed => TaskStatus::Todo,
            default               => TaskStatus::Completed,
        };
    }
}
