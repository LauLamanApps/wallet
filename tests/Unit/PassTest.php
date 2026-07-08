<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\Tests\Unit;

use DateTimeImmutable;
use LauLamanApps\Wallet\Exception\InvalidPassException;
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

#[CoversClass(Pass::class)]
#[CoversClass(Barcode::class)]
#[CoversClass(Field::class)]
#[CoversClass(Image::class)]
#[CoversClass(Location::class)]
final class PassTest extends TestCase
{
    public function testConstructorValues(): void
    {
        $pass = new Pass('ticket-42', PassType::EventTicket, 'Toy Town', 'Toy Town Membership');

        self::assertSame('ticket-42', $pass->getId());
        self::assertSame(PassType::EventTicket, $pass->getType());
        self::assertSame('Toy Town', $pass->getOrganizationName());
        self::assertSame('Toy Town Membership', $pass->getDescription());
        self::assertFalse($pass->isVoided());
    }

    #[DataProvider('colorProvider')]
    public function testColorsAreNormalized(string $input, string $expected): void
    {
        $pass = $this->createPass();
        $pass->setBackgroundColor($input);
        $pass->setForegroundColor($input);
        $pass->setLabelColor($input);

        self::assertSame($expected, $pass->getBackgroundColor());
        self::assertSame($expected, $pass->getForegroundColor());
        self::assertSame($expected, $pass->getLabelColor());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function colorProvider(): array
    {
        return [
            'with hash' => ['#AABBCC', '#aabbcc'],
            'without hash' => ['aabbcc', '#aabbcc'],
        ];
    }

    #[DataProvider('invalidColorProvider')]
    public function testInvalidColorThrows(string $color): void
    {
        $pass = $this->createPass();

        $this->expectException(InvalidPassException::class);

        $pass->setBackgroundColor($color);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidColorProvider(): array
    {
        return [
            'too short' => ['#abc'],
            'not hex' => ['#zzzzzz'],
            'empty' => [''],
            'rgb notation' => ['rgb(1, 2, 3)'],
        ];
    }

    public function testFieldsAreGroupedBySection(): void
    {
        $pass = $this->createPass();
        $header = new Field('gate', 'A55', 'Gate');
        $back = new Field('terms', 'No refunds');

        $pass->addField(FieldSection::Header, $header);
        $pass->addField(FieldSection::Back, $back);

        self::assertSame([$header], $pass->getFields(FieldSection::Header));
        self::assertSame([$back], $pass->getFields(FieldSection::Back));
        self::assertSame([], $pass->getFields(FieldSection::Primary));
        self::assertSame([$header, $back], $pass->getAllFields());

        self::assertSame('gate', $header->getKey());
        self::assertSame('A55', $header->getValue());
        self::assertSame('Gate', $header->getLabel());
        self::assertNull($back->getLabel());
    }

    public function testBarcodes(): void
    {
        $pass = $this->createPass();
        $barcode = new Barcode(BarcodeFormat::Qr, '123456789', 'alt');

        $pass->addBarcode($barcode);

        self::assertSame([$barcode], $pass->getBarcodes());
        self::assertSame(BarcodeFormat::Qr, $barcode->getFormat());
        self::assertSame('123456789', $barcode->getMessage());
        self::assertSame('alt', $barcode->getAltText());
    }

    public function testImages(): void
    {
        $pass = $this->createPass();
        $image = Image::fromUrl(ImageType::Logo, 'https://example.com/logo.png');

        $pass->addImage($image);

        self::assertSame([$image], $pass->getImages());
        self::assertSame(ImageType::Logo, $image->getType());
        self::assertSame('https://example.com/logo.png', $image->getUrl());
        self::assertNull($image->getLocalPath());
    }

    public function testImageFromMissingLocalPathThrows(): void
    {
        $this->expectException(InvalidPassException::class);

        Image::fromLocalPath(ImageType::Icon, '/nonexistent/icon.png');
    }

    public function testLocations(): void
    {
        $pass = $this->createPass();
        $location = new Location(52.3676, 4.9041);

        $pass->addLocation($location);

        self::assertSame([$location], $pass->getLocations());
        self::assertSame(52.3676, $location->getLatitude());
        self::assertSame(4.9041, $location->getLongitude());
    }

    public function testDatesAndVoid(): void
    {
        $pass = $this->createPass();
        $relevant = new DateTimeImmutable('2026-08-01T20:00:00+02:00');
        $expiration = new DateTimeImmutable('2026-08-02T02:00:00+02:00');

        $pass->setRelevantDate($relevant);
        $pass->setExpirationDate($expiration);
        $pass->setLogoText('Toy Town');
        $pass->void();

        self::assertSame($relevant, $pass->getRelevantDate());
        self::assertSame($expiration, $pass->getExpirationDate());
        self::assertSame('Toy Town', $pass->getLogoText());
        self::assertTrue($pass->isVoided());
    }

    private function createPass(): Pass
    {
        return new Pass('serial-1', PassType::Generic, 'Toy Town', 'Toy Town Membership');
    }
}
