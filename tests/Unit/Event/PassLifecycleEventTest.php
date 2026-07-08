<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\Tests\Unit\Event;

use LauLamanApps\Wallet\Event\PassInstalledEvent;
use LauLamanApps\Wallet\Event\PassUninstalledEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(PassInstalledEvent::class)]
#[CoversClass(PassUninstalledEvent::class)]
final class PassLifecycleEventTest extends TestCase
{
    public function testPassInstalledEvent(): void
    {
        $nativeEvent = new stdClass();
        $event = new PassInstalledEvent('apple', 'serial-1', $nativeEvent);

        self::assertSame('apple', $event->getPlatform());
        self::assertSame('serial-1', $event->getPassId());
        self::assertSame($nativeEvent, $event->getNativeEvent());
    }

    public function testPassUninstalledEventWithoutNativeEvent(): void
    {
        $event = new PassUninstalledEvent('google', '3388000000012345678.serial-1');

        self::assertSame('google', $event->getPlatform());
        self::assertSame('3388000000012345678.serial-1', $event->getPassId());
        self::assertNull($event->getNativeEvent());
    }
}
