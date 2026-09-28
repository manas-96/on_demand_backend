<?php

namespace App\Enums;

enum ApprovalType: string
{
    case InitialQuote = 'initial_quote';
    case AdditionalCharge = 'additional_charge';
}
