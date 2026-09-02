<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Calling = 'calling';
    case Approved = 'approved';
    case Canceled = 'canceled';

    public function label(): string
    {
        return __('enums.registration_status.'.$this->value);
    }
}
