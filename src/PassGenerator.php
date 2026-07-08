<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet;

interface PassGenerator
{
    /**
     * Unique identifier of the target platform, e.g. 'apple' or 'google'.
     */
    public function getPlatform(): string;

    public function generate(Pass $pass): GeneratedPass;
}
