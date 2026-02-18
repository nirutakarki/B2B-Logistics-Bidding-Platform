<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Shared\Enums\SupportTicketStatus;
use App\Shared\Enums\SupportTicketPriority;

class SupportTicket extends Model
{
    protected $fillable = [
        'raised_by_user_id',
        'subject',
        'description',
        'status',
        'priority',
        'assigned_to_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'priority' => SupportTicketPriority::class,
        ];
    }

    public function raisedBy()
    {
        return $this->belongsTo(User::class, 'raised_by_user_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
