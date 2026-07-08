<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\Exception;

use Exception;

final class UnsupportedPlatformException extends Exception implements WalletException
{
    /**
     * @param string[] $available
     */
    public static function forPlatform(string $platform, array $available): self
    {
        return new self(sprintf(
            'No pass generator registered for platform \'%s\'. Available platforms: %s.',
            $platform,
            $available === [] ? '(none)' : implode(', ', $available)
        ));
    }
}
