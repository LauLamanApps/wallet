<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\MetaData;

enum FieldSection: string
{
    case Header = 'header';
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Auxiliary = 'auxiliary';
    case Back = 'back';
}
