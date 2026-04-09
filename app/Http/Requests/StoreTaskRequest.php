<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assignee'    => ['nullable', 'string', 'max:255'],
            'status'      => ['nullable', 'string', Rule::enum(TaskStatus::class)],
            'priority'    => ['nullable', 'string', Rule::enum(TaskPriority::class)],
            'due_date'    => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'A task title is required.',
            'title.max'      => 'The title may not be greater than 255 characters.',
            'status.Illuminate\Validation\Rules\Enum' => 'Status must be one of: todo, in_progress, completed.',
            'priority.Illuminate\Validation\Rules\Enum' => 'Priority must be one of: low, medium, high.',
            'due_date.date'  => 'The due date must be a valid date.',
        ];
    }
}
