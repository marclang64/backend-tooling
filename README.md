# Backend Tooling [![Version](https://img.shields.io/github/v/tag/marclang64/backend-tooling?sort=semver&label=version)](https://github.com/marclang64/backend-tooling/releases)

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
            "url": "https://github.com/marclang64/backend-tooling"
        }
    ]
}
```

Install the package as a development dependency after v1.3.0 is released:

```bash
composer require --dev "marclang64/backend-tooling:^1.3"
vendor/bin/backend-tooling init
composer update --lock
```

On Windows PowerShell, run `vendor\bin\backend-tooling init`. If PowerShell strips the caret, use `composer --% require --dev marclang64/backend-tooling:^1.3`.

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

## Updating existing projects

Update Backend Tooling, then rerun the initializer to add missing Rector configuration and scripts. Existing configurations are never overwritten.

```bash
composer update marclang64/backend-tooling --with-all-dependencies
vendor/bin/backend-tooling init
composer update --lock
```

Ensure your existing version constraint permits v1.3.0. The generated sample test verifies PHPUnit setup only; replace it with real project tests.

## Development

Clone the repository and run `composer install`. The same quality scripts are available in the package repository.

GitHub Actions runs coding style, static analysis, Rector dry-run and PHPUnit. Integration tests create temporary consumer projects, install only Backend Tooling and exercise the installed tools and initializer, including Rector's dry-run and apply behavior.

## License

MIT
