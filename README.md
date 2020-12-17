# Identicon

Identicon with variable complexity.


## Samples

https://github.com/ranvis/identicon/wiki/Samples


## License

BSD 2-Clause License


## Installation

`
composer.phar require ranvis/identicon:1.0.*
`

1.1 or later may have visual incompatibility with 1.0.

## Upgrading

1.0.x is fully compatible with 1.0.0.

See [CHANGES.md](CHANGES.md) for the details.

## Example Usage

```php
use Ranvis\Identicon;

require_once(__DIR__ . '/vendor/autoload.php');

//$hash = md5($userId . 'YOUR_RANDOM_SALT_HERE_&ar/1R#S[|=hDF');

$hash = isset($_GET['hash']) ? $_GET['hash'] : '';
//$hash = $_GET['hash'] ?? ''; // PHP7+
if (!preg_match('/^[0-9a-f]{32}$/D', $hash)) {
    http_response_code(404);
    exit;
}

$tile = new Identicon\Tile();
$identicon = new Identicon\Identicon(64, $tile);
header('Cache-Control: public, max-age=31556952');
$identicon->draw($hash)->output();
```

## Quick Reference

### Identicon::__construct()

`__construct($maxSize, TileInterface $tile, $tiles = 6, $colors = 2, $highQuality = true)`

* int $maxSize maximum size of the icon to draw
* TileInterface $tile tile to use
* int $tiles complexity of the icon
* int $colors maximum usable colors
* bool $highQuality prefer quality over memory and speed

### Identicon->getMinimumHashLength()

`getMinimumHashLength()`

get number of hex characters required to draw icon.

### Identicon->draw()

`draw($hash)`

draw icon to internal buffer.

* string $hash arbitrary hex string

returns $this.

### Identicon->output()

`output($size = null, $compression = -1, $filters = -1)`

print PNG image to stdout with Content-Type header.

* int $size image size
* int $compression PNG compression level
* int $filters PNG filter flags to use

returns true on success.

### Identicon->save()

`save($filePath, $size = null, $compression = -1, $filters = -1)`

save PNG image to file.

* string $filePath file path to save
* int $size image size
* int $compression PNG compression level
* int $filters PNG filter flags to use

returns true on success.

### Identicon->getImage()

get icon image.

* int $size image size

returns GD image.
