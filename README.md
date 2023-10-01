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
composer require --dev shawnveltman/livewire-3-property-updater
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="livewire-3-property-updater-config"
```

This is the contents of the published config file:

```php
return [
    'start_directory' => 'app/Livewire',  // Defaulting to the app directory, but users can change this.
    'disk' => 'base_path',
    'method_name_style' => 'snake_case', // StudlyCase or  snake_case
];
```
I'm the kind of monster that GREATLY prefers snake_case for method names, so that's the default, but it's easy enough to change it to StudlyCase if that's your preference


## Usage for updating computed properties
```bash
php artisan shawnveltman:livewire-3-property-updater
```

## Check For Invalid Attempts To Set Property To Null in Livewire 3
In Livewire 3, setting a computed property to null will cause an error. After updating, you might find instances in your code where properties are explicitly set to null. This command automates the process of identifying and updating these instances to use unset instead.

When running this command, it'll scan the Livewire components in your specified directory and look for computed properties being set to null. It will replace these instances with the unset function, preventing potential errors in Livewire 3.

## Usage for checking null assignments

```bash
php artisan shawnveltman:livewire-null-property-updater
```

## Identify Dispatch Patterns in Livewire
The shawnveltman:dispatch-identifier command scans your Livewire components for specific dispatch patterns. It's especially useful when identifying or refactoring certain dispatch() method usages in Livewire.

The command filters out dispatches with named arguments and presents an output of file paths with the line numbers where the dispatch() method matches the targeted pattern.

## Usage for identifying dispatch patterns

```bash
php artisan shawnveltman:dispatch-identifier
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
