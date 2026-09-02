<?php

namespace App\Actions;

use App\Enums\ActivityType;
use App\Enums\RegistrationStatus;
use App\Models\ActivityLog;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordRegistrationCall
{
    public function __construct(private ChangeRegistrationStatus $changeRegistrationStatus) {}

    public function handle(Registration $registration, User $actor, string $result, ?string $note): Registration
    {
        return DB::transaction(function () use ($registration, $actor, $result, $note): Registration {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);
            $status = match ($result) {
                'پاسخ داد — تأیید کرد' => RegistrationStatus::Approved,
                'منصرف شد' => RegistrationStatus::Canceled,
                default => RegistrationStatus::Calling,
            };

            ActivityLog::query()->create([
                'registration_id' => $registration->id,
                'actor_type' => $actor->getMorphClass(),
                'actor_id' => $actor->getKey(),
                'type' => ActivityType::Call,
                'body' => 'نتیجه تماس: '.$result.($note ? "\n".$note : ''),
            ]);

            $this->changeRegistrationStatus->handle($registration, $actor, $status);

            return $registration->fresh();
        });
    }
}
