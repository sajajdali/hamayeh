<?php

namespace Database\Seeders;

use App\Actions\IssueRegistration;
use App\Enums\UserRole;
use App\Models\Blogger;
use App\Models\User;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->super()->create([
            'name' => 'مدیر کل',
            'username' => 'superadmin',
        ]);

        User::factory()->create([
            'name' => 'مدیر میانی',
            'username' => 'manager',
            'role' => UserRole::Mid,
        ]);

        $bloggers = Blogger::factory()->count(3)->create();
        $issueRegistration = app(IssueRegistration::class);

        foreach (range(1, 30) as $index) {
            $registration = $issueRegistration->handle(
                $bloggers->random(),
                RegistrationFactory::new()->definition(),
            );

            $registration->forceFill([
                'created_at' => now()->subDays($index),
                'updated_at' => now()->subDays($index),
            ])->saveQuietly();
        }
    }
}
