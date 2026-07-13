<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\Tests\Unit\Bridge\Apple;

use DateTimeImmutable;
use LauLamanApps\ApplePassbook\Build\Compiler;
use LauLamanApps\ApplePassbook\Passbook;
use LauLamanApps\Wallet\Bridge\Apple\ApplePassGenerator;
use LauLamanApps\Wallet\MetaData\Barcode;
use LauLamanApps\Wallet\MetaData\BarcodeFormat;
use LauLamanApps\Wallet\MetaData\Field;
use LauLamanApps\Wallet\MetaData\FieldSection;
use LauLamanApps\Wallet\MetaData\Image;
use LauLamanApps\Wallet\MetaData\ImageType;
use LauLamanApps\Wallet\MetaData\Location;
use LauLamanApps\Wallet\Pass;
use LauLamanApps\Wallet\PassType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApplePassGenerator::class)]
final class ApplePassGeneratorTest extends TestCase
{
    private const PNG_FIXTURE = __DIR__ . '/../../../../vendor/laulamanapps/apple-passbook/tests/files/MetaData/Image/valid_1px_red.png';

    public function testPlatform(): void
    {
        $generator = new ApplePassGenerator($this->createMock(Compiler::class));

        self::assertSame('apple', $generator->getPlatform());
    }

    public function testGenerateReturnsPkpassFile(): void
    {
        $pass = new Pass('serial-1', PassType::Generic, 'Toy Town', 'Toy Town Membership');

        $generated = (new ApplePassGenerator($this->createCompiler($passbook)))->generate($pass);

        self::assertTrue($generated->isFile());
        self::assertSame('apple', $generated->getPlatform());
        self::assertSame('<pkpass binary>', $generated->getContent());
        self::assertSame('application/vnd.apple.pkpass', $generated->getMimeType());
        self::assertSame('pass.pkpass', $generated->getFilename());
    }

    public function testGenerateMapsThePass(): void
    {
        $pass = new Pass('serial-1', PassType::EventTicket, 'Toy Town', 'Toy Town Membership');
        $pass->setLogoText('Toy Town');
        $pass->setBackgroundColor('#aabbcc');
        $pass->setForegroundColor('#001122');
        $pass->setLabelColor('#334455');
        $pass->addBarcode(new Barcode(BarcodeFormat::Qr, '123456789', 'alt text'));
        $pass->addField(FieldSection::Header, new Field('gate', 'A55', 'Gate'));
        $pass->addField(FieldSection::Primary, new Field('event', 'The Beach Boys'));
        $pass->addField(FieldSection::Back, new Field('terms', 'No refunds'));
        $pass->addImage(Image::fromLocalPath(ImageType::Icon, self::PNG_FIXTURE));
        $pass->addImage(Image::fromUrl(ImageType::Logo, 'https://example.com/logo.png'));
        $pass->addLocation(new Location(52.3676, 4.9041));
        $pass->setRelevantDate(new DateTimeImmutable('2026-08-01T20:00:00+02:00'));
        $pass->setExpirationDate(new DateTimeImmutable('2026-08-02T02:00:00+02:00'));
        $pass->setWebService('https://example.com/passkit', 'vxwxd7J8AlNNFPS8k0a0FfUFtq0ewzFdc');
        $pass->void();

        (new ApplePassGenerator($this->createCompiler($passbook)))->generate($pass);

        self::assertInstanceOf(Passbook::class, $passbook);
        $passbook->setPassTypeIdentifier('pass.com.toytown');
        $passbook->setTeamIdentifier('9X3HHK8VXA');
        $data = $passbook->getData();

        self::assertSame('serial-1', $data['serialNumber']);
        self::assertArrayHasKey('eventTicket', $data);
        self::assertSame('Toy Town', $data['organizationName']);
        self::assertSame('Toy Town Membership', $data['description']);
        self::assertSame('Toy Town', $data['logoText']);
        self::assertSame('#aabbcc', $data['backgroundColor']);
        self::assertSame('#001122', $data['foregroundColor']);
        self::assertSame('#334455', $data['labelColor']);
        self::assertSame(
            ['format' => 'PKBarcodeFormatQR', 'message' => '123456789', 'messageEncoding' => 'iso-8859-1', 'altText' => 'alt text'],
            $data['barcode']
        );
        self::assertSame(
            [['key' => 'gate', 'value' => 'A55', 'label' => 'Gate']],
            $data['eventTicket']['headerFields']
        );
        self::assertSame(
            [['key' => 'event', 'value' => 'The Beach Boys']],
            $data['eventTicket']['primaryFields']
        );
        self::assertSame(
            [['key' => 'terms', 'value' => 'No refunds']],
            $data['eventTicket']['backFields']
        );
        self::assertSame([['latitude' => 52.3676, 'longitude' => 4.9041]], $data['locations']);
        self::assertSame([['date' => '2026-08-01T20:00:00+02:00']], $data['relevantDates']);
        self::assertSame('2026-08-02T02:00:00+02:00', $data['expirationDate']);
        self::assertSame('https://example.com/passkit', $data['webServiceURL']);
        self::assertSame('vxwxd7J8AlNNFPS8k0a0FfUFtq0ewzFdc', $data['authenticationToken']);
        self::assertTrue($data['voided']);

        $images = $passbook->getImages();
        self::assertCount(1, $images);
        self::assertSame('icon.png', $images[0]->getFilename());
    }

    #[DataProvider('passTypeProvider')]
    public function testGenerateMapsPassTypesToPassbookStyles(PassType $type, string $expectedStyleKey): void
    {
        $pass = new Pass('serial-1', $type, 'Toy Town', 'Toy Town Membership');

        (new ApplePassGenerator($this->createCompiler($passbook)))->generate($pass);

        self::assertInstanceOf(Passbook::class, $passbook);
        $passbook->setPassTypeIdentifier('pass.com.toytown');
        $passbook->setTeamIdentifier('9X3HHK8VXA');

        self::assertArrayHasKey($expectedStyleKey, $passbook->getData());
    }

    /**
     * @return array<string, array{PassType, string}>
     */
    public static function passTypeProvider(): array
    {
        return [
            'generic' => [PassType::Generic, 'generic'],
            'event ticket' => [PassType::EventTicket, 'eventTicket'],
            'boarding pass' => [PassType::BoardingPass, 'boardingPass'],
            'coupon' => [PassType::Coupon, 'coupon'],
            'loyalty card' => [PassType::LoyaltyCard, 'storeCard'],
        ];
    }

    private function createCompiler(?Passbook &$passbook): Compiler
    {
        $compiler = $this->createMock(Compiler::class);
        $compiler
            ->expects($this->once())
            ->method('compile')
            ->willReturnCallback(function (Passbook $compiled) use (&$passbook): string {
                $passbook = $compiled;

                return '<pkpass binary>';
            });

        return $compiler;
    }
}
