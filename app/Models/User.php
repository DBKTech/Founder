<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'user_type',
        'is_active',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ✅ Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // ✅ Role helpers
    public function isHq(): bool
    {
        return $this->user_type === 'platform_admin';
    }

    public function isSeller(): bool
    {
        return $this->user_type === 'tenant_user';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'platform') {
            return $this->isHq();
        }

        if ($panel->getId() === 'app') {
            return $this->isSeller();
        }

        return false;
    }
}
