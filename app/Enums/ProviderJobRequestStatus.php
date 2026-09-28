<?php

namespace App\Enums;

enum ProviderJobRequestStatus: string
{
    case Sent = 'sent';
    case Viewed = 'viewed';
    case BidSubmitted = 'bid_submitted';
    case Declined = 'declined';
    case Expired = 'expired';
    case Selected = 'selected';
    case Rejected = 'rejected';
}
