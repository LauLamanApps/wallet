<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\Tests\Unit;

use LauLamanApps\Wallet\Exception\UnsupportedPlatformException;
use LauLamanApps\Wallet\GeneratedPass;
use LauLamanApps\Wallet\Pass;
use LauLamanApps\Wallet\PassGenerator;
use LauLamanApps\Wallet\PassType;
use LauLamanApps\Wallet\Wallet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Wallet::class)]
#[CoversClass(UnsupportedPlatformException::class)]
final class WalletTest extends TestCase
{
    public function testGenerateDispatchesToTheMatchingGenerator(): void
    {
        $pass = $this->createPass();
        $generated = GeneratedPass::url('acme', 'https://wallet.example.com/save/abc');

        $wallet = new Wallet([$this->createGenerator('acme', $pass, $generated)]);

        self::assertTrue($wallet->supports('acme'));
        self::assertFalse($wallet->supports('other'));
        self::assertSame(['acme'], $wallet->getPlatforms());
        self::assertSame($generated, $wallet->generate('acme', $pass));
    }

    public function testGenerateThrowsForUnknownPlatform(): void
    {
        $wallet = new Wallet();

        $this->expectException(UnsupportedPlatformException::class);
        $this->expectExceptionMessage("No pass generator registered for platform 'apple'. Available platforms: (none).");

        $wallet->generate('apple', $this->createPass());
    }

    public function testGenerateForAllPlatforms(): void
    {
        $pass = $this->createPass();
        $fileResult = GeneratedPass::file('one', '<binary>', 'application/octet-stream', 'pass.bin');
        $urlResult = GeneratedPass::url('two', 'https://example.com/save');

        $wallet = new Wallet();
        $wallet->registerGenerator($this->createGenerator('one', $pass, $fileResult));
        $wallet->registerGenerator($this->createGenerator('two', $pass, $urlResult));

        self::assertSame(
            ['one' => $fileResult, 'two' => $urlResult],
            $wallet->generateForAllPlatforms($pass)
        );
    }

    private function createGenerator(string $platform, Pass $expectedPass, GeneratedPass $result): PassGenerator
    {
        $generator = $this->createMock(PassGenerator::class);
        $generator->method('getPlatform')->willReturn($platform);
        $generator->method('generate')->with($expectedPass)->willReturn($result);

        return $generator;
    }

    private function createPass(): Pass
    {
        return new Pass('serial-1', PassType::Generic, 'Toy Town', 'Toy Town Membership');
    }
}
