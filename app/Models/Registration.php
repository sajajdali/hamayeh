<?php

namespace App\Models;

use App\Enums\Grade;
use App\Enums\RegistrationStatus;
use App\Enums\StudyField;
use Database\Factories\RegistrationFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['ticket_code', 'blogger_id', 'seq', 'full_name', 'phone', 'grade', 'field', 'school', 'gpa', 'study_city', 'father_job', 'province', 'city', 'area', 'guardian_name', 'guardian_phone', 'status', 'ticket_path'])]
class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory, SoftDeletes;

    /** @return BelongsTo<Blogger, $this> */
    public function blogger(): BelongsTo
    {
        return $this->belongsTo(Blogger::class);
    }

    /** @return HasMany<ActivityLog, $this> */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /** @return HasMany<SmsMessage, $this> */
    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    /** @param Builder<Registration> $query */
    #[Scope]
    protected function visibleTo(Builder $query, Authenticatable $actor): void
    {
        if ($actor instanceof Blogger) {
            $query->whereBelongsTo($actor, 'blogger');
        }
    }

    protected function casts(): array
    {
        return [
            'field' => StudyField::class,
            'gpa' => 'decimal:2',
            'grade' => Grade::class,
            'status' => RegistrationStatus::class,
        ];
    }
}
