## Laravel Content Importer

Import and save contents of files form local or remote storage
## Installation

You can install the package via composer:

```bash
composer require 64robots/content-import
```

## Usage

Publish Migrations and default configurations.

```bash
php artisan vendor:publish --provider="R64\ContentImport\ContentImportServiceProvider"
```

This package exposes 2 commands 

Import files from a disk
``` bash
php artisan files:import 
```

Read and save contents imported files 

```bash
php artisan files:process
```

## Supported File formats
 - csv

## Available Configs

- directory `parent directory where files to be imported are stored. defaults to imports`
- chunck_size ``
- heading_row: ` a heading row for your files (a row in which each cells indicates the purpose of that column)`


### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email agyenim@64robots.com instead of using the issue tracker.

## Credits

- [Agyenim Boateng](https://github.com/64robots)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
