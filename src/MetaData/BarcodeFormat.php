<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\MetaData;

enum BarcodeFormat: string
{
    case Qr = 'qr';
    case Pdf417 = 'pdf417';
    case Aztec = 'aztec';
    case Code128 = 'code128';
}
