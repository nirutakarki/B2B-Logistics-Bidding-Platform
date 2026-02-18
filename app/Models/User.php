<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();

        if ($role) {
            $this->roles()->syncWithoutDetaching($role);
        }
    }


    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // Support Tickets - tickets raised by this user
    public function raisedTickets()
    {
        return $this->hasMany(SupportTicket::class, 'raised_by_user_id');
    }

    // Support Tickets - tickets assigned to this user (for support agents)
    public function assignedTickets()
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to_user_id');
    }

    // Activity Logs
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Approval Logs
    public function approvals()
    {
        return $this->hasMany(ApprovalLog::class, 'approved_by');
    }

    // Helper method - check if user is support agent
    public function isSupportAgent(): bool
    {
        return $this->hasRole('support_agent') || $this->hasRole('platform_admin');
    }
}

