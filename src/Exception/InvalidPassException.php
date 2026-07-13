<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\Exception;

use Exception;

final class InvalidPassException extends Exception implements WalletException
{
    public static function invalidColor(string $color): self
    {
        return new self(sprintf('Color \'%s\' is not a valid hex color; expected \'#rrggbb\' or \'rrggbb\'.', $color));
    }

    public static function imageDoesNotExist(string $path): self
    {
        return new self(sprintf('Image \'%s\' does not exist.', $path));
    }

    public static function authenticationTokenTooShort(): self
    {
        return new self('The web service authentication token must be at least 16 characters long.');
    }
}
