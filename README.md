# PHP Tooling [![Version](https://img.shields.io/github/v/tag/marclang64/php-tooling?sort=semver&label=version)](https://github.com/marclang64/php-tooling/releases)

Reusable PHP development tooling for consistent code style, static analysis, automated refactoring, and testing.

## Features

- **PHP-CS-Fixer** — PSR-12-based formatting with additional opinionated rules.
- **PHPStan** — Static analysis at level 6.
- **Rector** — Automated refactoring with a conservative shared rule set.
- **PHPUnit** — Automated testing.
- **Initializer** — Generates project configuration and scripts without overwriting existing files.
- **GitHub Actions** — Continuous integration for package quality checks.

## Requirements

- PHP ^8.5
- Composer 2

## Installation

Add the GitHub repository to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/marclang64/php-tooling"
        }
    ]
}
```

Install the package as a development dependency:

```bash
composer require --dev "marclang64/php-tooling:^2.0"
vendor/bin/php-tooling init
composer update --lock
```

On Windows PowerShell, run `vendor\bin\php-tooling init`. If PowerShell strips the caret, use `composer --% require --dev marclang64/php-tooling:^2.0`.

All four quality tools are installed automatically. The initializer creates missing configuration files, adds Composer scripts, updates `.gitignore` and `.gitattributes`, and creates a sample test if no PHPUnit tests exist. Existing files and scripts are preserved. Review the generated paths for your project layout.

## Available scripts

| Command | Description |
|---|---|
| `composer lint:style` | Check PHP coding standards |
| `composer fix:style` | Automatically fix coding style |
| `composer lint:types` | Run PHPStan |
| `composer lint:rector` | Preview refactorings without changing files |
| `composer fix:rector` | Apply refactorings |
| `composer test` | Run PHPUnit tests |

## Configuration

The initializer generates `.php-cs-fixer.dist.php`, `phpstan.neon.dist`, `phpunit.xml.dist`, and `rector.php`. The shared PHP-CS-Fixer, PHPStan and Rector configuration lives in `configs/` inside the installed package. PHPUnit configuration is local to each project.

### Rector

The default rule converts straightforward constructor property assignments into constructor property promotion. No broad rule sets are enabled by default. The generated configuration scans `src/` and `tests/` and imports the shared rules.

To opt into additional PHP modernization rules, add `->withPhpSets()` to your local configuration. Rector can derive the PHP target from the project's Composer requirement. Review the proposed diff before applying any changes.

Recommended workflow:

```bash
composer lint:rector
composer fix:rector
composer fix:style
composer lint:style
composer lint:types
composer test
```

Commit the reviewed changes only after tests pass.

## Migrating from Backend Tooling v1.x

PHP Tooling v2.0.0 replaces the former `marclang64/backend-tooling` package. The package name, CLI and PHP namespace have changed; quality scripts and shared rules remain unchanged.

In your project's `composer.json`, change the VCS repository URL to `https://github.com/marclang64/php-tooling` and replace the old development dependency with `"marclang64/php-tooling": "^2.0"`. Then run:

```bash
composer update marclang64/backend-tooling marclang64/php-tooling --with-all-dependencies
```

In existing `.php-cs-fixer.dist.php`, `phpstan.neon.dist` and `rector.php` files, replace `vendor/marclang64/backend-tooling/` with `vendor/marclang64/php-tooling/`. Update any custom references to the old `BackendTooling` namespace or `backend-tooling` CLI as well. Preserve your project-specific settings.

```bash
vendor/bin/php-tooling init
composer update --lock
composer lint:style
composer lint:types
composer lint:rector
composer test
```

The initializer deliberately does not overwrite existing configuration files. The generated sample test verifies PHPUnit setup only; replace it with real project tests.

## Development

Clone the repository and run `composer install`. The same quality scripts are available in the package repository.

GitHub Actions runs coding style, static analysis, Rector dry-run and PHPUnit. Integration tests create temporary consumer projects, install only PHP Tooling and exercise the installed tools and initializer, including Rector's dry-run and apply behavior.

## License

MIT
