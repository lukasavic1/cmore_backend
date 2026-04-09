<?php
/**
 * date: 9.4.2026.
 * owner: lukasavic18@gmail.com
 *
 * Migrates legacy task statuses to kanban statuses with SQLite-safe
 * table rebuild logic.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill legacy statuses from older versions.
     *
     * - pending -> todo
     *
     * Note: On databases where the status column is a strict ENUM that doesn't
     * include the new values yet, you may need to run `migrate:fresh` or
     * manually alter the column before this can update rows successfully.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // SQLite stores `enum()` as TEXT + CHECK constraint,
        // which can't be altered in-place.
        // So we rebuild the table with the new allowed statuses
        // and copy data across.
        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            // Nothing to do if tasks table doesn't exist
            if (!Schema::hasTable('tasks')) {
                Schema::enableForeignKeyConstraints();
                return;
            }

            Schema::rename('tasks', 'tasks_legacy');

            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->enum('status', ['todo', 'in_progress', 'completed'])
                    ->default('todo');
                $table->enum('priority', ['low', 'medium', 'high'])
                    ->default('medium');
                $table->date('due_date')->nullable();
                $table->timestamps();
            });

            DB::statement("
                INSERT INTO tasks (
                    id, title, description, status,
                    priority, due_date, created_at, updated_at
                )
                SELECT
                    id,
                    title,
                    description,
                    CASE
                        WHEN status = 'pending' THEN 'todo'
                        WHEN status = 'completed' THEN 'completed'
                        WHEN status = 'todo' THEN 'todo'
                        WHEN status = 'in_progress' THEN 'in_progress'
                        ELSE 'todo'
                    END AS status,
                    priority,
                    due_date,
                    created_at,
                    updated_at
                FROM tasks_legacy
            ");

            Schema::drop('tasks_legacy');
            Schema::enableForeignKeyConstraints();
            return;
        }

        // MySQL/Postgres: simple backfill is enough
        // (ENUM alteration handled separately if needed)
        DB::table('tasks')
            ->where('status', 'pending')
            ->update(['status' => 'todo']);
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            if (!Schema::hasTable('tasks')) {
                Schema::enableForeignKeyConstraints();
                return;
            }

            Schema::rename('tasks', 'tasks_kanban');

            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->enum('status', ['pending', 'completed'])
                    ->default('pending');
                $table->enum('priority', ['low', 'medium', 'high'])
                    ->default('medium');
                $table->date('due_date')->nullable();
                $table->timestamps();
            });

            DB::statement("
                INSERT INTO tasks (
                    id, title, description, status,
                    priority, due_date, created_at, updated_at
                )
                SELECT
                    id,
                    title,
                    description,
                    CASE
                        WHEN status = 'completed' THEN 'completed'
                        ELSE 'pending'
                    END AS status,
                    priority,
                    due_date,
                    created_at,
                    updated_at
                FROM tasks_kanban
            ");

            Schema::drop('tasks_kanban');
            Schema::enableForeignKeyConstraints();
            return;
        }

        DB::table('tasks')
            ->where('status', 'todo')
            ->update(['status' => 'pending']);
    }
};

