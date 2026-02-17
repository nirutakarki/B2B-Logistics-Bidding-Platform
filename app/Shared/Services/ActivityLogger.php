<?php

namespace App\Shared\Services;

use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public function log(string $action, string $description): void
    {
        Log::info("{$action}: {$description}");
    }
}