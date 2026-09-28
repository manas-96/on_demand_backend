<?php

namespace App\Enums;

enum BidOfferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Countered = 'countered';
    case Rejected = 'rejected';
}
