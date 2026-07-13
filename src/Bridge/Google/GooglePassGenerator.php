<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\Bridge\Google;

use DateTimeImmutable;
use LauLamanApps\GoogleWallet\Object\Barcode as GoogleBarcode;
use LauLamanApps\GoogleWallet\Object\BarcodeType;
use LauLamanApps\GoogleWallet\Object\GenericClass;
use LauLamanApps\GoogleWallet\Object\GenericObject;
use LauLamanApps\GoogleWallet\Object\Image as GoogleImage;
use LauLamanApps\GoogleWallet\Object\State;
use LauLamanApps\GoogleWallet\Object\TextModule;
use LauLamanApps\GoogleWallet\PassPayload;
use LauLamanApps\GoogleWallet\SaveUrlFactory;
use LauLamanApps\Wallet\GeneratedPass;
use LauLamanApps\Wallet\MetaData\Barcode;
use LauLamanApps\Wallet\MetaData\BarcodeFormat;
use LauLamanApps\Wallet\MetaData\FieldSection;
use LauLamanApps\Wallet\MetaData\Image;
use LauLamanApps\Wallet\MetaData\ImageType;
use LauLamanApps\Wallet\Pass;
use LauLamanApps\Wallet\PassGenerator;

/**
 * Generates "Save to Google Wallet" links using laulamanapps/google-wallet.
 *
 * Passes are mapped to Google's generic pass type, with fields emitted as text
 * modules. For Google-specific pass types (event ticket, offer, loyalty,
 * transit) use laulamanapps/google-wallet directly.
 */
final class GooglePassGenerator implements PassGenerator
{
    public const PLATFORM = 'google';

    public function __construct(
        private readonly SaveUrlFactory $saveUrlFactory,
        private readonly string $issuerId,
        private readonly ?string $classSuffix = null,
    ) {
    }

    public function getPlatform(): string
    {
        return self::PLATFORM;
    }

    public function generate(Pass $pass): GeneratedPass
    {
        $classId = sprintf('%s.%s', $this->issuerId, $this->classSuffix ?? $pass->getType()->value);
        $object = $this->createObject($pass, $classId);

        $payload = new PassPayload();
        $payload->addGenericClass(new GenericClass($classId));
        $payload->addGenericObject($object);

        return GeneratedPass::url(self::PLATFORM, $this->saveUrlFactory->create($payload));
    }

    private function createObject(Pass $pass, string $classId): GenericObject
    {
        $object = new GenericObject(sprintf('%s.%s', $this->issuerId, $pass->getId()), $classId);
        $object->setCardTitle($pass->getOrganizationName());
        $object->setHeader($pass->getDescription());

        if ($pass->getLogoText() !== null) {
            $object->setSubheader($pass->getLogoText());
        }

        if ($pass->getBackgroundColor() !== null) {
            $object->setHexBackgroundColor($pass->getBackgroundColor());
        }

        $barcodes = $pass->getBarcodes();
        if ($barcodes !== []) {
            $object->setBarcode($this->barcode($barcodes[0]));
        }

        foreach ($pass->getImages() as $image) {
            $this->addImage($object, $image);
        }

        foreach (FieldSection::cases() as $section) {
            foreach ($pass->getFields($section) as $field) {
                $object->addTextModule(new TextModule(
                    $field->getLabel() ?? $field->getKey(),
                    (string) $field->getValue(),
                    $field->getKey()
                ));
            }
        }

        if ($pass->getExpirationDate() !== null) {
            $object->setValidTimeInterval(
                $pass->getRelevantDate() ?? new DateTimeImmutable('@0'),
                $pass->getExpirationDate()
            );
        }

        foreach (array_slice($pass->getLocations(), 0, GenericObject::MAX_MERCHANT_LOCATIONS) as $location) {
            $object->addMerchantLocation($location->getLatitude(), $location->getLongitude());
        }

        if ($pass->isVoided()) {
            $object->setState(State::Inactive);
        }

        return $object;
    }

    private function barcode(Barcode $barcode): GoogleBarcode
    {
        $type = match ($barcode->getFormat()) {
            BarcodeFormat::Qr => BarcodeType::QrCode,
            BarcodeFormat::Pdf417 => BarcodeType::Pdf417,
            BarcodeFormat::Aztec => BarcodeType::Aztec,
            BarcodeFormat::Code128 => BarcodeType::Code128,
        };

        return new GoogleBarcode($type, $barcode->getMessage(), $barcode->getAltText());
    }

    private function addImage(GenericObject $object, Image $image): void
    {
        if ($image->getUrl() === null) {
            return;
        }

        $googleImage = GoogleImage::fromUri($image->getUrl());

        match ($image->getType()) {
            ImageType::Logo, ImageType::Icon, ImageType::Thumbnail => $object->setLogo($googleImage),
            ImageType::Hero, ImageType::Strip, ImageType::Background => $object->setHeroImage($googleImage),
            ImageType::Footer => null,
        };
    }
}
