<?php

declare(strict_types=1);

namespace LauLamanApps\Wallet;

use DateTimeImmutable;
use LauLamanApps\Wallet\Exception\InvalidPassException;
use LauLamanApps\Wallet\MetaData\Barcode;
use LauLamanApps\Wallet\MetaData\Field;
use LauLamanApps\Wallet\MetaData\FieldSection;
use LauLamanApps\Wallet\MetaData\Image;
use LauLamanApps\Wallet\MetaData\Location;

/**
 * Platform-agnostic description of a mobile wallet pass.
 */
final class Pass
{
    /** @var Barcode[] */
    private array $barcodes = [];
    /** @var array<string, Field[]> */
    private array $fields = [];
    /** @var Image[] */
    private array $images = [];
    /** @var Location[] */
    private array $locations = [];
    private ?string $backgroundColor = null;
    private ?string $foregroundColor = null;
    private ?string $labelColor = null;
    private ?string $logoText = null;
    private ?DateTimeImmutable $relevantDate = null;
    private ?DateTimeImmutable $expirationDate = null;
    private ?string $webServiceUrl = null;
    private ?string $webServiceAuthenticationToken = null;
    private bool $voided = false;

    public function __construct(
        private readonly string $id,
        private readonly PassType $type,
        private readonly string $organizationName,
        private readonly string $description,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): PassType
    {
        return $this->type;
    }

    public function getOrganizationName(): string
    {
        return $this->organizationName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setLogoText(string $logoText): void
    {
        $this->logoText = $logoText;
    }

    public function getLogoText(): ?string
    {
        return $this->logoText;
    }

    public function setBackgroundColor(string $color): void
    {
        $this->backgroundColor = $this->normalizeColor($color);
    }

    /**
     * @return string|null Hex color in '#rrggbb' notation.
     */
    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setForegroundColor(string $color): void
    {
        $this->foregroundColor = $this->normalizeColor($color);
    }

    public function getForegroundColor(): ?string
    {
        return $this->foregroundColor;
    }

    public function setLabelColor(string $color): void
    {
        $this->labelColor = $this->normalizeColor($color);
    }

    public function getLabelColor(): ?string
    {
        return $this->labelColor;
    }

    public function addBarcode(Barcode $barcode): void
    {
        $this->barcodes[] = $barcode;
    }

    /**
     * @return Barcode[]
     */
    public function getBarcodes(): array
    {
        return $this->barcodes;
    }

    public function addField(FieldSection $section, Field $field): void
    {
        $this->fields[$section->value][] = $field;
    }

    /**
     * @return Field[]
     */
    public function getFields(FieldSection $section): array
    {
        return $this->fields[$section->value] ?? [];
    }

    /**
     * @return Field[]
     */
    public function getAllFields(): array
    {
        return array_merge(...array_values($this->fields) ?: [[]]);
    }

    public function addImage(Image $image): void
    {
        $this->images[] = $image;
    }

    /**
     * @return Image[]
     */
    public function getImages(): array
    {
        return $this->images;
    }

    public function addLocation(Location $location): void
    {
        $this->locations[] = $location;
    }

    /**
     * @return Location[]
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    public function setRelevantDate(DateTimeImmutable $relevantDate): void
    {
        $this->relevantDate = $relevantDate;
    }

    public function getRelevantDate(): ?DateTimeImmutable
    {
        return $this->relevantDate;
    }

    public function setExpirationDate(DateTimeImmutable $expirationDate): void
    {
        $this->expirationDate = $expirationDate;
    }

    public function getExpirationDate(): ?DateTimeImmutable
    {
        return $this->expirationDate;
    }

    /**
     * Registers a PassKit Web Service for pass updates and install/uninstall
     * tracking. Only used by Apple Wallet: devices register themselves against
     * the url using the token (Google tracks saves/deletions through class
     * callbacks instead). Apple requires an authentication token of at least
     * 16 characters.
     *
     * @throws InvalidPassException
     */
    public function setWebService(string $url, string $authenticationToken): void
    {
        if (strlen($authenticationToken) < 16) {
            throw InvalidPassException::authenticationTokenTooShort();
        }

        $this->webServiceUrl = $url;
        $this->webServiceAuthenticationToken = $authenticationToken;
    }

    public function getWebServiceUrl(): ?string
    {
        return $this->webServiceUrl;
    }

    public function getWebServiceAuthenticationToken(): ?string
    {
        return $this->webServiceAuthenticationToken;
    }

    public function void(): void
    {
        $this->voided = true;
    }

    public function isVoided(): bool
    {
        return $this->voided;
    }

    /**
     * @throws InvalidPassException
     */
    private function normalizeColor(string $color): string
    {
        $hex = ltrim($color, '#');

        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            throw InvalidPassException::invalidColor($color);
        }

        return '#' . strtolower($hex);
    }
}
