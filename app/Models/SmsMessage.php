<?php

namespace App\Models;

use App\Enums\SmsStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_id', 'sms_template_id', 'to', 'body', 'provider_message_id', 'status', 'error', 'sent_at'])]
class SmsMessage extends Model
{
    /** @return BelongsTo<Registration, $this> */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /** @return BelongsTo<SmsTemplate, $this> */
    public function smsTemplate(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class);
    }

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'status' => SmsStatus::class,
        ];
    }
}
