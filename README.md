Wallet
===============
Platform-agnostic PHP SDK for generating mobile wallet passes. Describe a pass once, generate it
for every supported platform:

- **Apple Wallet** (`.pkpass` files) via [laulamanapps/apple-passbook](https://github.com/LauLamanApps/apple-passbook)
- **Google Wallet** ("Save to Google Wallet" links) via [laulamanapps/google-wallet](https://github.com/LauLamanApps/google-wallet)

The core of this package contains no Apple- or Google-specific code: it defines a generic `Pass`
model, a `PassGenerator` contract, and a `Wallet` orchestrator. The platform bridges live behind
that contract, and the platform packages are optional dependencies — install only the engines you
need.

Installation
---
```bash
composer require laulamanapps/wallet

# For Apple Wallet passes:
composer require laulamanapps/apple-passbook

# For Google Wallet passes:
composer require laulamanapps/google-wallet
```

Or use one of the framework integrations, which wire the `Wallet` service and the platform
bridges from configuration:

- **Symfony**: [laulamanapps/wallet-symfony](https://github.com/LauLamanApps/wallet-symfony)
- **Laravel**: [laulamanapps/wallet-laravel](https://github.com/LauLamanApps/wallet-laravel)

Describe a pass
---
```php
use LauLamanApps\Wallet\MetaData\Barcode;
use LauLamanApps\Wallet\MetaData\BarcodeFormat;
use LauLamanApps\Wallet\MetaData\Field;
use LauLamanApps\Wallet\MetaData\FieldSection;
use LauLamanApps\Wallet\MetaData\Image;
use LauLamanApps\Wallet\MetaData\ImageType;
use LauLamanApps\Wallet\MetaData\Location;
use LauLamanApps\Wallet\Pass;
use LauLamanApps\Wallet\PassType;

$pass = new Pass('ticket-8j23fm3', PassType::EventTicket, 'Toy Town', 'Toy Town Concert Ticket');
$pass->setBackgroundColor('#1a1a2e');
$pass->setLogoText('Toy Town');
$pass->addBarcode(new Barcode(BarcodeFormat::Qr, '123456789'));
$pass->addField(FieldSection::Primary, new Field('event', 'The Beach Boys', 'Event'));
$pass->addField(FieldSection::Header, new Field('seat', '12A', 'Seat'));
$pass->setRelevantDate(new DateTimeImmutable('2026-08-01T20:00:00+02:00'));

// Surface the pass on the lock screen / as a notification near the venue (max 10 locations):
$pass->addLocation(new Location(52.3676, 4.9041));

// Enable pass updates and install/uninstall tracking (Apple PassKit Web Service; Google
// tracks saves/deletions through class callbacks instead — see the google-wallet package):
$pass->setWebService('https://passes.example.com/passkit', '<AuthenticationToken>');

// Apple embeds image files in the pass; Google references public URLs.
// Provide whichever the platforms you target need:
$pass->addImage(Image::fromLocalPath(ImageType::Icon, __DIR__ . '/images/icon.png'));
$pass->addImage(Image::fromUrl(ImageType::Logo, 'https://example.com/logo.png'));
```

Generate for every platform
---
```php
use LauLamanApps\ApplePassbook\Build\CompilerFactory;
use LauLamanApps\GoogleWallet\SaveUrlFactory;
use LauLamanApps\GoogleWallet\ServiceAccount;
use LauLamanApps\Wallet\Bridge\Apple\ApplePassGenerator;
use LauLamanApps\Wallet\Bridge\Google\GooglePassGenerator;
use LauLamanApps\Wallet\Wallet;

$compiler = (new CompilerFactory())->getCompiler('<PathToCertificate>', '<CertificatePassword>');
$compiler->setPassTypeIdentifier('pass.com.toytown');
$compiler->setTeamIdentifier('9X3HHK8VXA');

$serviceAccount = ServiceAccount::fromJsonFile('<PathToServiceAccountKey.json>');

$wallet = new Wallet([
    new ApplePassGenerator($compiler),
    new GooglePassGenerator(new SaveUrlFactory($serviceAccount), '<GoogleIssuerId>'),
]);

// One platform:
$apple = $wallet->generate('apple', $pass);   // GeneratedPass::isFile() — .pkpass binary
$google = $wallet->generate('google', $pass); // GeneratedPass::isUrl() — Save to Google Wallet link

// Or all registered platforms at once:
foreach ($wallet->generateForAllPlatforms($pass) as $platform => $generated) {
    if ($generated->isFile()) {
        file_put_contents($generated->getFilename(), $generated->getContent());
    } else {
        echo $platform . ': ' . $generated->getUrl() . PHP_EOL;
    }
}
```

Serving the result
---
`GeneratedPass` tells you how the pass reaches the user:

| | Apple | Google |
|---|---|---|
| Delivery | `Delivery::File` | `Delivery::Url` |
| Usage | Serve `getContent()` with `getMimeType()` (`application/vnd.apple.pkpass`) | Redirect or link the user to `getUrl()` |

Custom platforms
---
Implement `LauLamanApps\Wallet\PassGenerator` and register it:

```php
final class MyPlatformGenerator implements PassGenerator
{
    public function getPlatform(): string { return 'my-platform'; }

    public function generate(Pass $pass): GeneratedPass
    {
        return GeneratedPass::url('my-platform', 'https://wallet.example.com/save/...');
    }
}

$wallet->registerGenerator(new MyPlatformGenerator());
```

Lifecycle events
---
The package ships two platform-agnostic notification events:

- `LauLamanApps\Wallet\Event\PassInstalledEvent` — a pass was added to a wallet
- `LauLamanApps\Wallet\Event\PassUninstalledEvent` — a pass was removed from a wallet

Each event carries the platform (`'apple'`, `'google'`, ...), the platform pass id (Apple serial
number, Google object id) and the native platform event for consumers that need platform detail.
The framework integrations dispatch them automatically: on Apple after a successful PassKit
device (un)registration, on Google after a signature-verified save/delete callback. Listen once,
cover every platform.

Platform notes
---
- **Apple**: the pass type identifier, team identifier and signing certificate are configured on
  the apple-passbook `Compiler`; the bridge maps `PassType::LoyaltyCard` to Apple's `storeCard`
  style and `PassType::BoardingPass` to a generic transit type. Only images with a local path are
  embedded (`ImageType::Hero` is Google-only). Locations become pass `locations` (lock-screen
  relevance); `setWebService()` becomes `webServiceURL`/`authenticationToken` in the pass.
- **Google**: passes are generated as signed "Save to Google Wallet" links (no API call needed);
  all pass types are mapped to Google's generic pass with your fields as text modules. Only images
  with a public URL are used (`ImageType::Logo` and `ImageType::Hero`/`ImageType::Strip`).
  Locations become `merchantLocations` ("Nearby Passes" notifications, first 10 locations);
  `setWebService()` is ignored — Google pushes updates through its REST API and reports
  saves/deletions via class callbacks. For Google-specific pass types (event ticket, offer,
  loyalty, transit objects) use laulamanapps/google-wallet directly.

Credits
---
[Laurens Laman](https://github.com/LauLaman)

License
---
This package is licensed under the [MIT license](LICENSE).
