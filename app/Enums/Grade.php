<?php

namespace App\Enums;

enum Grade: string
{
    case Ten = '10';
    case Eleven = '11';
    case Twelve = '12';
    case Alumni = 'alumni';

    public function label(): string
    {
        return __('enums.grade.'.$this->value);
    }
}
