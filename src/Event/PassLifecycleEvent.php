<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\Event;

/**
 * Platform-agnostic notification about a pass lifecycle change. Dispatched by
 * the framework integrations after the platform-specific flow completed
 * successfully; the native platform event is available for consumers that
 * need platform detail.
 */
abstract class PassLifecycleEvent
{
    public function __construct(
        private readonly string $platform,
        private readonly string $passId,
        private readonly ?object $nativeEvent = null,
    ) {
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * The platform identifier of the pass: the serial number on Apple,
     * the object id on Google.
     */
    public function getPassId(): string
    {
        return $this->passId;
    }

    public function getNativeEvent(): ?object
    {
        return $this->nativeEvent;
    }
}
