<?php
/**
 * date: 9.4.2026.
 * owner: lukasavic18@gmail.com
 *
 * Generates fake user records for tests and seed data.
 */

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'google_id' => 'google_'.fake()->unique()->uuid(),
            'name'      => fake()->name(),
            'email'     => fake()->unique()->safeEmail(),
            'avatar'    => null,
        ];
    }
}
