<?php
/**
 * date: 9.4.2026.
 * owner: lukasavic18@gmail.com
 *
 * Adds assignee support to tasks and backfills built-in example
 * assignments.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'assignee')) {
                $table->string('assignee', 255)
                    ->nullable()
                    ->after('description');
            }
        });

        // Backfill the built-in example tasks
        // so analysis has meaningful assignees.
        // We only touch rows that have NULL/empty assignee
        // to avoid overwriting real user data.
        $map = [
            'Set up project repository'          => 'Platform team',
            'Design database schema'             => 'Data engineering',
            'Implement user authentication'      => 'Backend team',
            'Build task CRUD endpoints'          => 'Backend team',
            'Write API documentation'            => 'Product ops',
            'Add search and filtering'           => 'Backend team',
            'Set up caching layer'               => 'Backend team',
            'Write feature tests'                => 'QA',
            'Configure CORS for React frontend'  => 'Frontend team',
            'Deploy to staging environment'      => 'DevOps',
            'Performance profiling'              => 'External IT firm',
            'Set up error monitoring'            => 'DevOps',
            'Code review session'                => 'Engineering leadership',
            'Update frontend components'         => 'Frontend team',
            'Add pagination to list endpoints'   => 'Backend team',
            'Security audit' => 'External security partner',
            'Write onboarding guide'             => 'Developer experience',
            'Implement rate limiting'            => 'Backend team',
            'Add soft deletes to tasks'          => 'Backend team',
            'Production go-live'                 => 'Release management',
        ];

        foreach ($map as $title => $assignee) {
            DB::table('tasks')
                ->where('title', $title)
                ->where(function ($q) {
                    $q->whereNull('assignee')->orWhere('assignee', '');
                })
                ->update(['assignee' => $assignee]);
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'assignee')) {
                $table->dropColumn('assignee');
            }
        });
    }
};

