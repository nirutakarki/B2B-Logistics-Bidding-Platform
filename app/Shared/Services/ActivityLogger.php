<?php

namespace App\Shared\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public function log(
        string $action,
        string $description,
        mixed $loggable = null,
        ?User $user = null
    ): ActivityLog {
        $userId = null;
        if ($user) {
            $userId = $user->id;
        } elseif (Auth::check()) {
            $userId = Auth::id();
        }
        
        $data = [
            'action' => $action,
            'description' => $description,
            'user_id' => $userId,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ];

        if ($loggable) {
            $data['loggable_type'] = get_class($loggable);
            $data['loggable_id'] = $loggable->id;
        }

        $activityLog = ActivityLog::create($data);

        Log::info("{$action}: {$description}", [
            'user_id' => $data['user_id'],
            'loggable' => $loggable ? get_class($loggable) . '#' . $loggable->id : null,
        ]);

        return $activityLog;
    }
}
