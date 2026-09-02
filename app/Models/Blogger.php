<?php

namespace App\Models;

use Database\Factories\BloggerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'code', 'slug', 'phone', 'avatar_path', 'password', 'is_active', 'seq'])]
#[Hidden(['password', 'remember_token'])]
class Blogger extends Authenticatable
{
    /** @use HasFactory<BloggerFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /** @return HasMany<Registration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
