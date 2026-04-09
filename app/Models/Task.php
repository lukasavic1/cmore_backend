<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'assignee',
        'status',
        'priority',
        'due_date',
    ];

    protected $casts = [
        'status'   => TaskStatus::class,
        'priority' => TaskPriority::class,
        'due_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereIn('status', [TaskStatus::Todo->value, TaskStatus::InProgress->value])
                     ->whereNotNull('due_date')
                     ->where('due_date', '<', now()->toDateString());
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('assignee')->orWhere('assignee', '');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isOverdue(): bool
    {
        return in_array($this->status, [TaskStatus::Todo, TaskStatus::InProgress], true)
            && $this->due_date !== null
            && $this->due_date->isPast();
    }
}
