<?php

namespace App\Enums;

enum TaskStatus: string
{
    case CANCELLED = 'cancelled';
    case CANCELLING = 'cancelling';
    case COMPLETE = 'complete';
    case FAILED = 'failed';
    case PENDING = 'pending';
    case RUNNING = 'running';
}
