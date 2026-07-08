<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\Tests\Unit;

use LauLamanApps\Wallet\Delivery;
use LauLamanApps\Wallet\GeneratedPass;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GeneratedPass::class)]
final class GeneratedPassTest extends TestCase
{
    public function testFile(): void
    {
        $pass = GeneratedPass::file('apple', '<binary>', 'application/vnd.apple.pkpass', 'pass.pkpass');

        self::assertSame('apple', $pass->getPlatform());
        self::assertSame(Delivery::File, $pass->getDelivery());
        self::assertTrue($pass->isFile());
        self::assertFalse($pass->isUrl());
        self::assertSame('<binary>', $pass->getContent());
        self::assertSame('application/vnd.apple.pkpass', $pass->getMimeType());
        self::assertSame('pass.pkpass', $pass->getFilename());
    }

    public function testFileThrowsWhenAskedForUrl(): void
    {
        $pass = GeneratedPass::file('apple', '<binary>', 'application/vnd.apple.pkpass', 'pass.pkpass');

        $this->expectException(LogicException::class);

        $pass->getUrl();
    }

    public function testUrl(): void
    {
        $pass = GeneratedPass::url('google', 'https://pay.google.com/gp/v/save/xyz');

        self::assertSame('google', $pass->getPlatform());
        self::assertSame(Delivery::Url, $pass->getDelivery());
        self::assertTrue($pass->isUrl());
        self::assertFalse($pass->isFile());
        self::assertSame('https://pay.google.com/gp/v/save/xyz', $pass->getUrl());
    }

    public function testUrlThrowsWhenAskedForContent(): void
    {
        $pass = GeneratedPass::url('google', 'https://pay.google.com/gp/v/save/xyz');

        $this->expectException(LogicException::class);

        $pass->getContent();
    }
}
