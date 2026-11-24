<?php

namespace App\Enums;

enum NotificationType: string
{
    case Booking = 'booking';
    case Message = 'message';
    case Promotional = 'promotional';
}
