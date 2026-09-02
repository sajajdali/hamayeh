<?php

namespace Database\Factories;

use App\Models\SmsTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SmsTemplate> */
class SmsTemplateFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true), 'body' => 'سلام {نام}، کد شما {کد} است.', 'is_active' => true];
    }
}
