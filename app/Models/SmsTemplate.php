<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'body', 'is_active'])]
class SmsTemplate extends Model
{
    use SoftDeletes;

    /** @return HasMany<SmsMessage, $this> */
    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
