<?php

namespace App\Shared\Enums;

enum VehicleType: string
{
    case Truck = 'truck';
    case Van = 'van';
    case Tippers = 'tippers';
    case Flatbed = 'flatbed';
    case Refrigerated = 'refrigerated';
}
