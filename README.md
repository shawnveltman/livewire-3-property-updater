# A package to update Livewire 2 computed properties to use the Livewire 3 syntax

[![Latest Version on Packagist](https://img.shields.io/packagist/v/shawnveltman/livewire-3-property-updater.svg?style=flat-square)](https://packagist.org/packages/shawnveltman/livewire-3-property-updater)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/shawnveltman/livewire-3-property-updater/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/shawnveltman/livewire-3-property-updater/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/shawnveltman/livewire-3-property-updater/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/shawnveltman/livewire-3-property-updater/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/shawnveltman/livewire-3-property-updater.svg?style=flat-square)](https://packagist.org/packages/shawnveltman/livewire-3-property-updater)

In Livewire 2, a computed property "foo" would be defined like this:

```php
public function getFooProperty()
{
    return 'bar';
}
```

In Livewire 3, the same property would be defined like this:

```php
#[Computed]
public function foo()
{
    return 'bar';
}
```
This package automates that update in your Livewire components folder.

## Installation

You can install the package via composer:

```bash
composer require shawnveltman/livewire-3-property-updater
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="livewire-3-property-updater-config"
```

This is the contents of the published config file:

```php
return [
    'start_directory' => base_path('app/Livewire'),  // Defaulting to the app/Livewire directory as per Livewire 3 convention, but users can change this.
];
```

## Usage

```bash
php artisan shawnveltman:livewire-3-property-updater
```

## Testing

```bash
composer test
```

## Credits

- [Shawn Veltman](https://github.com/shawnveltman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
