<?php

namespace Database\Factories;

use App\Enums\Grade;
use App\Enums\RegistrationStatus;
use App\Enums\StudyField;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_code' => fake()->unique()->bothify('x-#####'),
            'blogger_id' => null,
            'seq' => fake()->numberBetween(1, 99999),
            'full_name' => fake()->name(),
            'phone' => '09'.fake()->numerify('#########'),
            'grade' => fake()->randomElement(Grade::cases()),
            'field' => fake()->randomElement(StudyField::cases()),
            'school' => fake()->company().' دبیرستان',
            'gpa' => fake()->randomFloat(2, 10, 20),
            'study_city' => fake()->city(),
            'father_job' => fake()->jobTitle(),
            'province' => fake()->randomElement(['تهران', 'اصفهان', 'فارس']),
            'city' => fake()->city(),
            'area' => fake()->optional()->citySuffix(),
            'guardian_name' => fake()->name(),
            'guardian_phone' => '09'.fake()->numerify('#########'),
            'status' => RegistrationStatus::Pending,
            'ticket_path' => null,
        ];
    }
}
