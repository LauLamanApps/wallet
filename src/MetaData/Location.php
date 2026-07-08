<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\MetaData;

final class Location
{
    public function __construct(
        private readonly float $latitude,
        private readonly float $longitude,
    ) {
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }
}
