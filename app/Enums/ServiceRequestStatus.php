<?php

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case Submitted = 'submitted';
    case Matching = 'matching';
    case Invited = 'invited';
    case InNegotiation = 'in_negotiation';
    case Awarded = 'awarded';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Matching => 'Matching Providers',
            self::Invited => 'Invited',
            self::InNegotiation => 'In Negotiation',
            self::Awarded => 'Awarded',
            self::Assigned => 'Technician Assigned',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}
