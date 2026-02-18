<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalLog extends Model
{
    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'old_status',
        'new_status',
        'approved_by',
        'reason',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
