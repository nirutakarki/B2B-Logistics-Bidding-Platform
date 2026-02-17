<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Shared\Enums\BusinessStatus;
use App\Shared\Enums\BusinessType;

class Business extends Model
{
    protected $fillable = [
        'name',
        'type',
        'status',
        'logo_path',
        'address'
    ];

    protected function casts(): array
    {
        return [
            'type' => BusinessType::class,
            'status' => BusinessStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
