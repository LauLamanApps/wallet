<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\MetaData;

enum ImageType: string
{
    case Icon = 'icon';
    case Logo = 'logo';
    case Strip = 'strip';
    case Thumbnail = 'thumbnail';
    case Background = 'background';
    case Footer = 'footer';
    case Hero = 'hero';
}
