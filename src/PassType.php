<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet;

enum PassType: string
{
    case Generic = 'generic';
    case EventTicket = 'event_ticket';
    case BoardingPass = 'boarding_pass';
    case Coupon = 'coupon';
    case LoyaltyCard = 'loyalty_card';
}
