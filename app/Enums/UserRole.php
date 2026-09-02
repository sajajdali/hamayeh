<?php

namespace App\Enums;

enum UserRole: string
{
    case Super = 'super';
    case Mid = 'mid';

    public function label(): string
    {
        return __('enums.user_role.'.$this->value);
    }
}
