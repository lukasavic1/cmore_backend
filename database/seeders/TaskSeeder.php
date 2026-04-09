<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\ExampleTasksService;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Local/demo seed: one user with the same starter tasks as a new signup.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'google_id' => 'seed-demo-'.uniqid(),
            'name'      => 'Demo user',
            'email'     => 'demo-seed-'.uniqid().'@example.com',
        ]);

        app(ExampleTasksService::class)->seedForUser($user);
    }
}
