<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\Tests\Unit\Bridge\Google;

use DateTimeImmutable;
use LauLamanApps\GoogleWallet\SaveUrlFactory;
use LauLamanApps\GoogleWallet\ServiceAccount;
use LauLamanApps\Wallet\Bridge\Google\GooglePassGenerator;
use LauLamanApps\Wallet\MetaData\Barcode;
use LauLamanApps\Wallet\MetaData\BarcodeFormat;
use LauLamanApps\Wallet\MetaData\Field;
use LauLamanApps\Wallet\MetaData\FieldSection;
use LauLamanApps\Wallet\MetaData\Image;
use LauLamanApps\Wallet\MetaData\ImageType;
use LauLamanApps\Wallet\Pass;
use LauLamanApps\Wallet\PassType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GooglePassGenerator::class)]
final class GooglePassGeneratorTest extends TestCase
{
    private const SAVE_URL_PREFIX = 'https://pay.google.com/gp/v/save/';

    public function testPlatform(): void
    {
        $generator = new GooglePassGenerator($this->createSaveUrlFactory(), '3388000000012345678');

        self::assertSame('google', $generator->getPlatform());
    }

    public function testGenerateReturnsSaveUrl(): void
    {
        $pass = new Pass('serial-1', PassType::Generic, 'Toy Town', 'Toy Town Membership');

        $generated = (new GooglePassGenerator($this->createSaveUrlFactory(), '3388000000012345678'))->generate($pass);

        self::assertTrue($generated->isUrl());
        self::assertSame('google', $generated->getPlatform());
        self::assertStringStartsWith(self::SAVE_URL_PREFIX, $generated->getUrl());
    }

    public function testGenerateMapsThePass(): void
    {
        $pass = new Pass('serial-1', PassType::EventTicket, 'Toy Town', 'Toy Town Concert');
        $pass->setLogoText('Toy Town');
        $pass->setBackgroundColor('#AABBCC');
        $pass->addBarcode(new Barcode(BarcodeFormat::Qr, '123456789', 'alt text'));
        $pass->addField(FieldSection::Primary, new Field('event', 'The Beach Boys', 'Event'));
        $pass->addField(FieldSection::Back, new Field('terms', 'No refunds'));
        $pass->addImage(Image::fromUrl(ImageType::Logo, 'https://example.com/logo.png'));
        $pass->addImage(Image::fromUrl(ImageType::Hero, 'https://example.com/hero.png'));
        $pass->setRelevantDate(new DateTimeImmutable('2026-08-01T20:00:00+02:00'));
        $pass->setExpirationDate(new DateTimeImmutable('2026-08-02T02:00:00+02:00'));

        $generator = new GooglePassGenerator($this->createSaveUrlFactory(), '3388000000012345678');
        $claims = $this->decodeJwtClaims($generator->generate($pass)->getUrl());

        self::assertSame('service-account@example.iam.gserviceaccount.com', $claims['iss']);
        self::assertSame('google', $claims['aud']);
        self::assertSame('savetowallet', $claims['typ']);

        $payload = $claims['payload'];
        self::assertSame([['id' => '3388000000012345678.event_ticket']], $payload['genericClasses']);

        $object = $payload['genericObjects'][0];
        self::assertSame('3388000000012345678.serial-1', $object['id']);
        self::assertSame('3388000000012345678.event_ticket', $object['classId']);
        self::assertSame('ACTIVE', $object['state']);
        self::assertSame('Toy Town', $object['cardTitle']['defaultValue']['value']);
        self::assertSame('Toy Town Concert', $object['header']['defaultValue']['value']);
        self::assertSame('Toy Town', $object['subheader']['defaultValue']['value']);
        self::assertSame('#aabbcc', $object['hexBackgroundColor']);
        self::assertSame(
            ['type' => 'QR_CODE', 'value' => '123456789', 'alternateText' => 'alt text'],
            $object['barcode']
        );
        self::assertSame('https://example.com/logo.png', $object['logo']['sourceUri']['uri']);
        self::assertSame('https://example.com/hero.png', $object['heroImage']['sourceUri']['uri']);
        self::assertSame(
            [
                ['header' => 'Event', 'body' => 'The Beach Boys', 'id' => 'event'],
                ['header' => 'terms', 'body' => 'No refunds', 'id' => 'terms'],
            ],
            $object['textModulesData']
        );
        self::assertSame('2026-08-01T20:00:00+02:00', $object['validTimeInterval']['start']['date']);
        self::assertSame('2026-08-02T02:00:00+02:00', $object['validTimeInterval']['end']['date']);
    }

    public function testVoidedPassIsInactive(): void
    {
        $pass = new Pass('serial-1', PassType::Generic, 'Toy Town', 'Toy Town Membership');
        $pass->void();

        $generator = new GooglePassGenerator($this->createSaveUrlFactory(), '3388000000012345678');
        $claims = $this->decodeJwtClaims($generator->generate($pass)->getUrl());

        self::assertSame('INACTIVE', $claims['payload']['genericObjects'][0]['state']);
    }

    public function testCustomClassSuffix(): void
    {
        $pass = new Pass('serial-1', PassType::Generic, 'Toy Town', 'Toy Town Membership');

        $generator = new GooglePassGenerator($this->createSaveUrlFactory(), '3388000000012345678', 'membership-v2');
        $claims = $this->decodeJwtClaims($generator->generate($pass)->getUrl());

        self::assertSame(
            '3388000000012345678.membership-v2',
            $claims['payload']['genericObjects'][0]['classId']
        );
    }

    public function testLocalOnlyImagesAreSkipped(): void
    {
        $pass = new Pass('serial-1', PassType::Generic, 'Toy Town', 'Toy Town Membership');
        $pngFixture = __DIR__ . '/../../../../vendor/laulamanapps/apple-passbook/tests/files/MetaData/Image/valid_1px_red.png';
        $pass->addImage(Image::fromLocalPath(ImageType::Logo, $pngFixture));

        $generator = new GooglePassGenerator($this->createSaveUrlFactory(), '3388000000012345678');
        $claims = $this->decodeJwtClaims($generator->generate($pass)->getUrl());

        self::assertArrayNotHasKey('logo', $claims['payload']['genericObjects'][0]);
    }

    private function createSaveUrlFactory(): SaveUrlFactory
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        openssl_pkey_export($key, $privateKeyPem);

        return new SaveUrlFactory(ServiceAccount::fromArray([
            'client_email' => 'service-account@example.iam.gserviceaccount.com',
            'private_key' => $privateKeyPem,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJwtClaims(string $saveUrl): array
    {
        $jwt = substr($saveUrl, strlen(self::SAVE_URL_PREFIX));
        $segments = explode('.', $jwt);
        self::assertCount(3, $segments);

        $claims = json_decode(
            (string) base64_decode(strtr($segments[1], '-_', '+/'), true),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertIsArray($claims);

        return $claims;
    }
}
