<?php

namespace App\Actions;

use App\Enums\ActivityType;
use App\Enums\RegistrationStatus;
use App\Models\ActivityLog;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ChangeRegistrationStatus
{
    public function handle(Registration $registration, User $actor, RegistrationStatus $status): Registration
    {
        $current = $registration->status;
        $allowed = match ($current) {
            RegistrationStatus::Pending => [RegistrationStatus::Calling, RegistrationStatus::Approved, RegistrationStatus::Canceled],
            RegistrationStatus::Calling => [RegistrationStatus::Approved, RegistrationStatus::Canceled],
            RegistrationStatus::Approved => [RegistrationStatus::Canceled],
            RegistrationStatus::Canceled => [RegistrationStatus::Pending],
        };

        if ($current !== $status && ! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'این تغییر وضعیت مجاز نیست.']);
        }

        if ($current === $status) {
            return $registration;
        }

        $registration->update(['status' => $status]);

        ActivityLog::query()->create([
            'registration_id' => $registration->id,
            'actor_type' => $actor->getMorphClass(),
            'actor_id' => $actor->getKey(),
            'type' => ActivityType::Status,
            'body' => 'تغییر وضعیت به «'.$status->label().'»',
            'meta' => ['from' => $current->value, 'to' => $status->value],
        ]);

        return $registration;
    }
}
