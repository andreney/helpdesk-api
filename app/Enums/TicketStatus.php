<?php

namespace App\Enums;

enum TicketStatus: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case WAITING = 'WAITING';
    case CLOSED = 'CLOSED';
}
