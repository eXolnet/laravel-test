# eXolnet Laravel Test

[![Latest Stable Version](https://poser.pugx.org/eXolnet/laravel-test/v/stable?format=flat-square)](https://packagist.org/packages/eXolnet/laravel-test)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/github/workflow/status/eXolnet/laravel-test/tests?label=tests&style=flat-square)](https://github.com/eXolnet/laravel-test/actions?query=workflow%3Atests)
[![Total Downloads](https://img.shields.io/packagist/dt/eXolnet/laravel-test.svg?style=flat-square)](https://packagist.org/packages/eXolnet/laravel-test)

Extends Laravel’s TestCase to accelerate the SQLite database creation by using a cached version.

## Installation

Require this package with composer:

```bash
composer require --dev exolnet/laravel-test
```

In your application `TestCase`, extends the package’s `TestCase` instead of Laravel’s version.

```php
use Exolnet\Test\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}
```

## Usage

This package will cache a migrated and seeded version of the testing SQLite database in order
to restore it faster. This is useful if your migration process is slow.

## Testing

To run the PHPUnit tests, please use:

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE OF CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email security@exolnet.com instead of using the issue tracker.

## Credits

- [Tom Rochette](https://github.com/tomzx)
- [Alexandre D'Eschambeault](https://github.com/xel1045)
- [Simon Gaudreau](https://github.com/Gandhi11)
- [All Contributors](../../contributors)

## License

This code is licensed under the [MIT license](http://choosealicense.com/licenses/mit/). 
Please see the [license file](LICENSE) for more information.
