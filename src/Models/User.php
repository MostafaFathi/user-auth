<?php

namespace MostafaFathi\UserAuth\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'user_type_id',
        'sso_id',
        'sso_provider',
        'sso_attributes',
        'is_active',
        'email_verified_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'sso_attributes' => 'array',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    public function userType()
    {
        return $this->belongsTo(UserType::class);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->userType->hasPermission($permission);
    }

    public function isAdmin(): bool
    {
        return $this->userType->name === 'admin';
    }
}
