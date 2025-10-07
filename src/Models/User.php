<?php

namespace MostafaFathi\UserAuth\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'sso_attributes' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default user type for new users
        static::creating(function ($user) {
            if (empty($user->user_type_id)) {
                $defaultUserType = UserType::where('name', 'user')->first();
                if ($defaultUserType) {
                    $user->user_type_id = $defaultUserType->id;
                }
            }
        });
    }

    /**
     * Get the user type that owns the user.
     */
    public function userType()
    {
        return $this->belongsTo(UserType::class);
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->userType) {
            return false;
        }

        return $this->userType->hasPermission($permission);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->userType && $this->userType->name === 'admin';
    }

    /**
     * Get SSO attributes with fallback.
     */
    public function getSsoAttribute($key)
    {
        $attributes = $this->sso_attributes ?? [];
        return $attributes[$key] ?? null;
    }

    /**
     * Scope a query to only include users by type.
     */
    public function scopeOfType($query, $typeName)
    {
        return $query->whereHas('userType', function ($q) use ($typeName) {
            $q->where('name', $typeName);
        });
    }
}