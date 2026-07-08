<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\MetaData;

final class Field
{
    public function __construct(
        private readonly string $key,
        private readonly string|int|float|bool $value,
        private readonly ?string $label = null,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): string|int|float|bool
    {
        return $this->value;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
}
