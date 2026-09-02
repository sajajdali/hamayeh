<?php

namespace App\Enums;

enum ActivityType: string
{
    case Call = 'call';
    case Sms = 'sms';
    case Status = 'status';
    case System = 'system';

    public function label(): string
    {
        return __('enums.activity_type.'.$this->value);
    }
}
