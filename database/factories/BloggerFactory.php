<?php

namespace Database\Factories;

use App\Models\Blogger;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Blogger>
 */
class BloggerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'code' => fake()->unique()->bothify('b###'),
            'slug' => fake()->unique()->slug(2),
            'phone' => '09'.fake()->numerify('#########'),
            'avatar_path' => null,
            'password' => Hash::make('password'),
            'is_active' => true,
            'seq' => 0,
        ];
    }
}
