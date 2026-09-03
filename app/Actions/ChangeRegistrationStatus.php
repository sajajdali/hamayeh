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
        if (! $this->canTransition($registration, $status)) {
            throw ValidationException::withMessages(['status' => 'این تغییر وضعیت مجاز نیست.']);
        }

        $current = $registration->status;

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

    public function canTransition(Registration $registration, RegistrationStatus $status): bool
    {
        $allowed = match ($registration->status) {
            RegistrationStatus::Pending => [RegistrationStatus::Calling, RegistrationStatus::Approved, RegistrationStatus::Canceled],
            RegistrationStatus::Calling => [RegistrationStatus::Approved, RegistrationStatus::Canceled],
            RegistrationStatus::Approved => [RegistrationStatus::Calling, RegistrationStatus::Canceled],
            RegistrationStatus::Canceled => [RegistrationStatus::Pending, RegistrationStatus::Calling],
        };

        return $registration->status === $status || in_array($status, $allowed, true);
    }
}
