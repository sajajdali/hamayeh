<?php

namespace App\Actions;

use App\Models\Blogger;
use App\Models\Registration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class IssueRegistration
{
    private const FIELDS = [
        'full_name',
        'phone',
        'grade',
        'field',
        'school',
        'gpa',
        'study_city',
        'father_job',
        'province',
        'city',
        'area',
        'guardian_name',
        'guardian_phone',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(?Blogger $blogger, array $data): Registration
    {
        return DB::transaction(function () use ($blogger, $data): Registration {
            [$prefix, $sequence, $bloggerId] = $blogger instanceof Blogger
                ? $this->nextBloggerSequence($blogger)
                : $this->nextDirectSequence();

            return Registration::query()->create([
                ...Arr::only($data, self::FIELDS),
                'blogger_id' => $bloggerId,
                'seq' => $sequence,
                'ticket_code' => "{$prefix}-{$sequence}",
            ]);
        });
    }

    /**
     * @return array{0: string, 1: int, 2: int}
     */
    private function nextBloggerSequence(Blogger $blogger): array
    {
        $lockedBlogger = Blogger::query()->whereKey($blogger->getKey())->lockForUpdate()->firstOrFail();
        $lockedBlogger->increment('seq');

        return [$lockedBlogger->code, (int) $lockedBlogger->seq, $lockedBlogger->getKey()];
    }

    /**
     * @return array{0: string, 1: int, 2: null}
     */
    private function nextDirectSequence(): array
    {
        DB::table('settings')->insertOrIgnore([
            'key' => 'registration_sequence',
            'value' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $setting = DB::table('settings')
            ->where('key', 'registration_sequence')
            ->lockForUpdate()
            ->firstOrFail();

        $sequence = (int) $setting->value + 1;

        DB::table('settings')
            ->where('key', 'registration_sequence')
            ->update(['value' => $sequence, 'updated_at' => now()]);

        return ['x', $sequence, null];
    }
}
