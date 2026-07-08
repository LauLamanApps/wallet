<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\MetaData;

final class Barcode
{
    public function __construct(
        private readonly BarcodeFormat $format,
        private readonly string $message,
        private readonly ?string $altText = null,
    ) {
    }

    public function getFormat(): BarcodeFormat
    {
        return $this->format;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }
}
