<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet;

use LogicException;

/**
 * The platform-specific result of generating a pass: a downloadable file
 * (e.g. an Apple Wallet .pkpass bundle) or a URL the user opens to add the
 * pass (e.g. a "Save to Google Wallet" link).
 */
final class GeneratedPass
{
    private function __construct(
        private readonly string $platform,
        private readonly Delivery $delivery,
        private readonly ?string $content,
        private readonly ?string $mimeType,
        private readonly ?string $filename,
        private readonly ?string $url,
    ) {
    }

    public static function file(string $platform, string $content, string $mimeType, string $filename): self
    {
        return new self($platform, Delivery::File, $content, $mimeType, $filename, null);
    }

    public static function url(string $platform, string $url): self
    {
        return new self($platform, Delivery::Url, null, null, null, $url);
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getDelivery(): Delivery
    {
        return $this->delivery;
    }

    public function isFile(): bool
    {
        return $this->delivery === Delivery::File;
    }

    public function isUrl(): bool
    {
        return $this->delivery === Delivery::Url;
    }

    public function getContent(): string
    {
        return $this->content ?? throw new LogicException('This pass is delivered as a URL; call getUrl().');
    }

    public function getMimeType(): string
    {
        return $this->mimeType ?? throw new LogicException('This pass is delivered as a URL; call getUrl().');
    }

    public function getFilename(): string
    {
        return $this->filename ?? throw new LogicException('This pass is delivered as a URL; call getUrl().');
    }

    public function getUrl(): string
    {
        return $this->url ?? throw new LogicException('This pass is delivered as a file; call getContent().');
    }
}
