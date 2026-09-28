<?php

namespace App\Enums;

enum JobAssignmentStatus: string
{
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case EnRoute = 'en_route';
    case Arrived = 'arrived';
    case Inspecting = 'inspecting';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
