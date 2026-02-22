<?php

namespace App\Shared\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case InTransit = 'in_transit';
    case InMaintenance = 'in_maintenance';
    case OutOfService = 'out_of_service';
}
