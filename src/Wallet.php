<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet;

use LauLamanApps\Wallet\Exception\UnsupportedPlatformException;

final class Wallet
{
    /** @var array<string, PassGenerator> */
    private array $generators = [];

    /**
     * @param iterable<PassGenerator> $generators
     */
    public function __construct(iterable $generators = [])
    {
        foreach ($generators as $generator) {
            $this->registerGenerator($generator);
        }
    }

    public function registerGenerator(PassGenerator $generator): void
    {
        $this->generators[$generator->getPlatform()] = $generator;
    }

    /**
     * @return string[]
     */
    public function getPlatforms(): array
    {
        return array_keys($this->generators);
    }

    public function supports(string $platform): bool
    {
        return isset($this->generators[$platform]);
    }

    /**
     * @throws UnsupportedPlatformException
     */
    public function generate(string $platform, Pass $pass): GeneratedPass
    {
        $generator = $this->generators[$platform]
            ?? throw UnsupportedPlatformException::forPlatform($platform, $this->getPlatforms());

        return $generator->generate($pass);
    }

    /**
     * @return array<string, GeneratedPass> Generated passes indexed by platform.
     */
    public function generateForAllPlatforms(Pass $pass): array
    {
        $passes = [];
        foreach ($this->generators as $platform => $generator) {
            $passes[$platform] = $generator->generate($pass);
        }

        return $passes;
    }
}
