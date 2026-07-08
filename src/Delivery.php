<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet;

enum Delivery: string
{
    case File = 'file';
    case Url = 'url';
}
