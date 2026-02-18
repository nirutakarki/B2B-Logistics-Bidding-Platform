<?php

namespace App\Shared\Enums;

enum VehicleStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';
}
