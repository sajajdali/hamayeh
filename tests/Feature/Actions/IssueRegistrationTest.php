<?php

use App\Actions\IssueRegistration;
use App\Models\Blogger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('issues fifty unique sequential ticket codes for one blogger', function () {
    $blogger = Blogger::factory()->create(['code' => 'a10']);
    $issueRegistration = app(IssueRegistration::class);

    $ticketCodes = collect(range(1, 50))
        ->map(fn (): string => $issueRegistration->handle($blogger, registrationData())->ticket_code);

    expect($ticketCodes->unique())->toHaveCount(50);
    expect($ticketCodes->all())->toBe(collect(range(1, 50))
        ->map(fn (int $sequence): string => "a10-{$sequence}")
        ->all());

    $this->assertDatabaseCount('registrations', 50);
});

it('uses the direct registration sequence when no blogger is provided', function () {
    $issueRegistration = app(IssueRegistration::class);

    $firstRegistration = $issueRegistration->handle(null, registrationData());
    $secondRegistration = $issueRegistration->handle(null, registrationData());

    expect($firstRegistration->ticket_code)->toBe('x-1');
    expect($secondRegistration->ticket_code)->toBe('x-2');
    $this->assertDatabaseHas('settings', ['key' => 'registration_sequence', 'value' => 2]);
});

/**
 * @return array<string, string|float>
 */
function registrationData(): array
{
    return [
        'full_name' => 'دانش‌آموز آزمون',
        'phone' => '09123456789',
        'grade' => '12',
        'field' => 'science',
        'school' => 'دبیرستان نمونه',
        'gpa' => 19.25,
        'study_city' => 'تهران',
        'father_job' => 'آموزگار',
        'province' => 'تهران',
        'city' => 'تهران',
        'area' => 'سعادت‌آباد',
        'guardian_name' => 'ولی آزمون',
        'guardian_phone' => '09129876543',
    ];
}
