# Backend Tooling [![Version](https://img.shields.io/github/v/tag/marclang64/backend-tooling?sort=semver&label=version)](https://github.com/marclang64/backend-tooling/releases)

Reusable PHP development tooling configurations for consistent code style, static analysis, and testing.

This package provides shared configurations for **PHP-CS-Fixer** and **PHPStan**, alongside integration tests that verify their compatibility with real consumer projects.

## Features

- **PHP-CS-Fixer** — PSR-12-based formatting with additional opinionated rules.
- **PHPStan** — Static analysis with a shared baseline configuration.
- **PHPUnit** — Automated integration tests for package installation and tooling compatibility.
- **GitHub Actions** — Continuous integration for coding standards, static analysis, and tests.
- **Composer** — Installable directly from GitHub without Packagist.

## Requirements

- PHP 8.5 or newer, within the supported Composer version constraint
- Composer 2

## Installation

Add the GitHub repository to your project's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/marclang64/backend-tooling"
    }
  ]
}
```

Install the package and the development tools:

```bash
composer require --dev marclang64/backend-tooling:@dev
composer require --dev friendsofphp/php-cs-fixer phpstan/phpstan phpunit/phpunit
```

The development tools are installed separately because Composer does not install the `require-dev` dependencies of other packages.

## PHP-CS-Fixer

Create `.php-cs-fixer.dist.php` in your project root:

```php
<?php

declare(strict_types=1);

$config = require __DIR__
    . '/vendor/marclang64/backend-tooling/configs/php-cs-fixer.php';

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src');

return $config->setFinder($finder);
```

Check coding style:

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Automatically fix formatting:

```bash
vendor/bin/php-cs-fixer fix
```

## PHPStan

Create `phpstan.neon.dist` in your project root:

```neon
includes:
    - vendor/marclang64/backend-tooling/configs/phpstan.neon

parameters:
    paths:
        - src
```

Run static analysis:

```bash
vendor/bin/phpstan analyse --no-progress
```

The shared configuration currently uses PHPStan level 6.

## PHPUnit

PHPUnit is used to test this package and verify that consumer projects can execute their own test suites.

Consumer projects maintain their own PHPUnit configuration.

Run your project's tests using:

```bash
vendor/bin/phpunit
```

## Development

Clone the repository and install its dependencies:

```bash
composer install
```

Available Composer scripts:

| Command | Description |
|---|---|
| `composer lint:style` | Check PHP coding standards |
| `composer fix:style` | Automatically fix coding style |
| `composer lint:types` | Run PHPStan |
| `composer test` | Run PHPUnit tests |

## Continuous Integration

GitHub Actions automatically runs coding-style checks, static analysis, and PHPUnit tests on pushes and pull requests.

The integration tests create isolated temporary consumer projects, install the package through Composer, and verify the tooling configurations.

## Quick setup for consumer projects (v1.1.0)

Install the package as a development dependency, then initialize the project:

```bash
composer require --dev marclang64/backend-tooling:^1.1
vendor/bin/backend-tooling init
```

On Windows PowerShell, run `vendor\bin\backend-tooling init`.

The initializer creates `.php-cs-fixer.dist.php`, `phpstan.neon.dist`, and
`phpunit.xml.dist`; registers missing Composer scripts; updates `.gitignore`;
and creates `tests/ExampleTest.php` only when no PHPUnit tests exist.
It creates `src/` when missing, does not overwrite existing configuration files,
and can be run repeatedly. Review generated files for your project's layout.

Then run `composer lint:style`, `composer lint:types`, and `composer test`.

**Note:** The sample test verifies PHPUnit setup only; replace it with real
project tests. Composer scripts are added to the root project's `composer.json`.

## License

MIT
