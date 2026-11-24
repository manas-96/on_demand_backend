<?php

namespace App\Enums;

enum PaymentType: string
{
    /** Upfront booking deposit collected when the customer creates a request. */
    case Deposit = 'deposit';

    /** Remaining balance (invoice total minus deposit, plus tip) collected after job completion. */
    case Balance = 'balance';
}
