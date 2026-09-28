<?php

namespace App\Enums;

enum ProviderBidStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderNegotiation = 'under_negotiation';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Expired = 'expired';
}
