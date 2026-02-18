<?php

namespace App\Shared\Enums;

enum SupportTicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';
}
