<?php

declare(strict_types=1);

namespace BackendTooling\Setup;

use RuntimeException;

final class Initializer
{
    /** @var array<string, string> */
    private const SCRIPTS = [
        'lint:style' => 'php-cs-fixer fix --dry-run --diff',
        'fix:style' => 'php-cs-fixer fix',
        'lint:types' => 'phpstan analyse --no-progress',
        'test' => 'phpunit',
    ];

    public function run(string $projectDirectory): void
    {
        $root = realpath($projectDirectory);

        if ($root === false || !is_file($root . '/composer.json')) {
            throw new RuntimeException('Run init from a project directory containing composer.json.');
        }

        $composerFile = $root . '/composer.json';
        $contents = file_get_contents($composerFile);
        if ($contents === false) {
            throw new RuntimeException('Cannot read composer.json.');
        }

        /** @var mixed $composer */
        $composer = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($composer) || array_is_list($composer)) {
            throw new RuntimeException('composer.json must contain a JSON object.');
        }

        if (!is_dir($root . '/src') && !mkdir($root . '/src', 0777, true) && !is_dir($root . '/src')) {
            throw new RuntimeException('Could not create src directory.');
        }

        $this->createIfMissing($root . '/.php-cs-fixer.dist.php', <<<'CONFIG'
<?php

declare(strict_types=1);

$config = require __DIR__ . '/vendor/marclang64/backend-tooling/configs/php-cs-fixer.php';

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src');

if (is_dir(__DIR__ . '/tests')) {
    $finder->in(__DIR__ . '/tests');
}

return $config->setFinder($finder);
CONFIG
        );

        $this->createSampleTestIfNeeded($root);

        $this->createIfMissing($root . '/phpstan.neon.dist', <<<'CONFIG'
includes:
    - vendor/marclang64/backend-tooling/configs/phpstan.neon

parameters:
    paths:
        - src
        - tests
CONFIG
        );

        $this->createIfMissing($root . '/phpunit.xml.dist', <<<'CONFIG'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Project Tests">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
CONFIG
        );

        $requirements = $composer['require'] ?? [];
        if (!is_array($requirements) || ($requirements !== [] && array_is_list($requirements))) {
            throw new RuntimeException('composer.json require must be an object.');
        }

        if (!array_key_exists('php', $requirements)) {
            $requirements['php'] = '^8.5';
            $composer['require'] = $requirements;
            echo "[OK] PHP requirement set to ^8.5\n";
        }

        $scripts = $composer['scripts'] ?? [];
        if (!is_array($scripts) || ($scripts !== [] && array_is_list($scripts))) {
            throw new RuntimeException('composer.json scripts must be an object.');
        }

        foreach (self::SCRIPTS as $name => $command) {
            if (!array_key_exists($name, $scripts)) {
                $scripts[$name] = $command;
            }
        }

        $composer['scripts'] = $scripts;
        if ($composer !== json_decode($contents, true, 512, JSON_THROW_ON_ERROR)) {
            $encoded = json_encode(
                $composer,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );

            if (file_put_contents($composerFile, $encoded . "\n") === false) {
                throw new RuntimeException('Could not update composer.json.');
            }

            echo "[OK] Updated composer.json\n";
        } else {
            echo "[SKIP] composer.json already configured\n";
        }

        $this->updateGitignore($root);
        echo "\nBackend Tooling initialized.\n";
        echo "Run: composer update --lock\n";
        echo "Then: composer lint:style, composer lint:types, composer test\n";
    }

    private function createIfMissing(string $file, string $contents): void
    {
        if (file_exists($file)) {
            echo '[SKIP] ' . basename($file) . " already exists\n";
            return;
        }
        if (file_put_contents($file, $contents . "\n") === false) {
            throw new RuntimeException('Could not create ' . $file);
        }
        echo '[OK] Created ' . basename($file) . "\n";
    }

    private function createSampleTestIfNeeded(string $root): void
    {
        $tests = $root . '/tests';
        if (!is_dir($tests) && !mkdir($tests, 0777, true) && !is_dir($tests)) {
            throw new RuntimeException('Could not create tests directory.');
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tests, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && (str_ends_with($file->getFilename(), 'Test.php') || str_ends_with($file->getFilename(), '.phpt'))) {
                echo "[SKIP] Existing tests found\n";
                return;
            }
        }

        $this->createIfMissing($tests . '/ExampleTest.php', <<<'TEST'
<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ExampleTest extends TestCase
{
    public function testComposerAutoloaderExists(): void
    {
        self::assertFileExists(
            dirname(__DIR__) . '/vendor/autoload.php'
        );
    }
}
TEST
        );
    }

    private function updateGitignore(string $root): void
    {
        $path = $root . '/.gitignore';
        $contents = is_file($path) ? file_get_contents($path) : '';
        if ($contents === false) {
            throw new RuntimeException('Cannot read .gitignore.');
        }
        $required = ['/vendor/', '/.php-cs-fixer.cache', '/.php-cs-fixer.php', '/.phpunit.cache/', '/.phpunit.result.cache'];
        $lines = preg_split('/\R/', $contents) ?: [];
        $missing = array_values(array_filter($required, static fn(string $entry): bool => !in_array($entry, $lines, true)));
        if ($missing === []) {
            echo "[SKIP] .gitignore already configured\n";
            return;
        }
        $newContents = rtrim($contents, "\r\n");
        if ($newContents !== '') {
            $newContents .= "\n\n";
        }
        $newContents .= "# Backend Tooling\n" . implode("\n", $missing) . "\n";
        if (file_put_contents($path, $newContents) === false) {
            throw new RuntimeException('Could not update .gitignore.');
        }
        echo "[OK] Updated .gitignore\n";
    }
}
