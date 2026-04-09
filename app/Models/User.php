<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'role_id',
        'status',
        'invite_token',
        'invite_expires_at',
        'invitation_accepted_at',
        'invited_by',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'invite_token',
    ];

    protected $with = ['role', 'company'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invite_expires_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class);
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === 'admin';
    }

    public function isOperative(): bool
    {
        return $this->role?->slug === 'operative';
    }

    public function isReadOnly(): bool
    {
        return $this->role?->slug === 'readonly';
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isInvited(): bool
    {
        return $this->status === 'invited';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
