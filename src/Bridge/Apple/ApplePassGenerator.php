<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet\Bridge\Apple;

use LauLamanApps\ApplePassbook\BoardingPassbook;
use LauLamanApps\ApplePassbook\Build\Compiler;
use LauLamanApps\ApplePassbook\CouponPassbook;
use LauLamanApps\ApplePassbook\EventTicketPassbook;
use LauLamanApps\ApplePassbook\GenericPassbook;
use LauLamanApps\ApplePassbook\MetaData\Barcode as AppleBarcode;
use LauLamanApps\ApplePassbook\MetaData\BoardingPass\TransitType;
use LauLamanApps\ApplePassbook\MetaData\Field\Field as AppleField;
use LauLamanApps\ApplePassbook\MetaData\Image\LocalImage;
use LauLamanApps\ApplePassbook\MetaData\Location as AppleLocation;
use LauLamanApps\ApplePassbook\MetaData\RelevantDate;
use LauLamanApps\ApplePassbook\Passbook;
use LauLamanApps\ApplePassbook\StoreCardPassbook;
use LauLamanApps\ApplePassbook\Style\BarcodeFormat as AppleBarcodeFormat;
use LauLamanApps\ApplePassbook\Style\Color\Hex;
use LauLamanApps\Wallet\GeneratedPass;
use LauLamanApps\Wallet\MetaData\Barcode;
use LauLamanApps\Wallet\MetaData\BarcodeFormat;
use LauLamanApps\Wallet\MetaData\Field;
use LauLamanApps\Wallet\MetaData\FieldSection;
use LauLamanApps\Wallet\MetaData\Image;
use LauLamanApps\Wallet\MetaData\ImageType;
use LauLamanApps\Wallet\Pass;
use LauLamanApps\Wallet\PassGenerator;
use LauLamanApps\Wallet\PassType;

/**
 * Generates Apple Wallet .pkpass bundles using laulamanapps/apple-passbook.
 *
 * The pass type identifier, team identifier and signing certificate are
 * expected to be configured on the Compiler (see CompilerFactory).
 */
final class ApplePassGenerator implements PassGenerator
{
    public const PLATFORM = 'apple';

    public const MIME_TYPE = 'application/vnd.apple.pkpass';

    public function __construct(
        private readonly Compiler $compiler,
    ) {
    }

    public function getPlatform(): string
    {
        return self::PLATFORM;
    }

    public function generate(Pass $pass): GeneratedPass
    {
        $passbook = $this->createPassbook($pass);

        return GeneratedPass::file(
            self::PLATFORM,
            $this->compiler->compile($passbook),
            self::MIME_TYPE,
            'pass.pkpass'
        );
    }

    private function createPassbook(Pass $pass): Passbook
    {
        $passbook = match ($pass->getType()) {
            PassType::Generic => new GenericPassbook($pass->getId()),
            PassType::EventTicket => new EventTicketPassbook($pass->getId()),
            PassType::BoardingPass => new BoardingPassbook($pass->getId(), TransitType::Generic),
            PassType::Coupon => new CouponPassbook($pass->getId()),
            PassType::LoyaltyCard => new StoreCardPassbook($pass->getId()),
        };

        $passbook->setOrganizationName($pass->getOrganizationName());
        $passbook->setDescription($pass->getDescription());

        if ($pass->getLogoText() !== null) {
            $passbook->setLogoText($pass->getLogoText());
        }

        if ($pass->getBackgroundColor() !== null) {
            $passbook->setBackgroundColor($this->color($pass->getBackgroundColor()));
        }

        if ($pass->getForegroundColor() !== null) {
            $passbook->setForegroundColor($this->color($pass->getForegroundColor()));
        }

        if ($pass->getLabelColor() !== null) {
            $passbook->setLabelColor($this->color($pass->getLabelColor()));
        }

        foreach ($pass->getBarcodes() as $barcode) {
            $passbook->setBarcode($this->barcode($barcode));
        }

        foreach (FieldSection::cases() as $section) {
            foreach ($pass->getFields($section) as $field) {
                $this->addField($passbook, $section, $field);
            }
        }

        foreach ($pass->getImages() as $image) {
            $localImage = $this->image($image);
            if ($localImage !== null) {
                $passbook->addImage($localImage);
            }
        }

        foreach ($pass->getLocations() as $location) {
            $passbook->addLocation(new AppleLocation($location->getLatitude(), $location->getLongitude()));
        }

        if ($pass->getRelevantDate() !== null) {
            $passbook->addRelevantDate(RelevantDate::forDate($pass->getRelevantDate()));
        }

        if ($pass->getExpirationDate() !== null) {
            $passbook->setExpirationDate($pass->getExpirationDate());
        }

        if ($pass->isVoided()) {
            $passbook->voided();
        }

        return $passbook;
    }

    private function color(string $hex): Hex
    {
        return new Hex(ltrim($hex, '#'));
    }

    private function barcode(Barcode $barcode): AppleBarcode
    {
        $format = match ($barcode->getFormat()) {
            BarcodeFormat::Qr => AppleBarcodeFormat::Qr,
            BarcodeFormat::Pdf417 => AppleBarcodeFormat::Pdf417,
            BarcodeFormat::Aztec => AppleBarcodeFormat::Aztec,
            BarcodeFormat::Code128 => AppleBarcodeFormat::Code128,
        };

        return new AppleBarcode($format, $barcode->getMessage(), $barcode->getAltText());
    }

    private function addField(Passbook $passbook, FieldSection $section, Field $field): void
    {
        $value = $field->getValue();
        if (is_float($value)) {
            $value = (string) $value;
        }

        $appleField = new AppleField($field->getKey(), $value, $field->getLabel());

        match ($section) {
            FieldSection::Header => $passbook->addHeaderField($appleField),
            FieldSection::Primary => $passbook->addPrimaryField($appleField),
            FieldSection::Secondary => $passbook->addSecondaryField($appleField),
            FieldSection::Auxiliary => $passbook->addAuxiliaryField($appleField),
            FieldSection::Back => $passbook->addBackField($appleField),
        };
    }

    private function image(Image $image): ?LocalImage
    {
        if ($image->getLocalPath() === null || $image->getType() === ImageType::Hero) {
            return null;
        }

        return new LocalImage($image->getLocalPath(), $image->getType()->value . '.png');
    }
}
