<?php

namespace App\Enums;

enum StudyField: string
{
    case Math = 'math';
    case Science = 'science';

    public function label(): string
    {
        return __('enums.study_field.'.$this->value);
    }
}
