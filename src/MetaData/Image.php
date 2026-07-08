<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\MetaData;

use LauLamanApps\Wallet\Exception\InvalidPassException;

/**
 * A pass image. Apple Wallet embeds image files into the pass bundle (local path),
 * Google Wallet references publicly reachable image URLs. Provide whichever the
 * target platforms need; bridges skip images they cannot use.
 */
final class Image
{
    private function __construct(
        private readonly ImageType $type,
        private readonly ?string $localPath,
        private readonly ?string $url,
    ) {
    }

    public static function fromLocalPath(ImageType $type, string $localPath, ?string $url = null): self
    {
        if (!file_exists($localPath)) {
            throw InvalidPassException::imageDoesNotExist($localPath);
        }

        return new self($type, $localPath, $url);
    }

    public static function fromUrl(ImageType $type, string $url): self
    {
        return new self($type, null, $url);
    }

    public function getType(): ImageType
    {
        return $this->type;
    }

    public function getLocalPath(): ?string
    {
        return $this->localPath;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
