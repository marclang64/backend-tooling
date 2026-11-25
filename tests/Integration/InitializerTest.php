<?php

declare(strict_types=1);

use BackendTooling\Setup\Initializer;
use PHPUnit\Framework\TestCase;

final class InitializerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/backend-tooling-init-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0777, true);
        file_put_contents($this->directory . '/composer.json', "{\n    \"name\": \"example/project\"\n}\n");
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->directory);
    }

    public function testCreatesConfigurationScriptsAndSampleTest(): void
    {
        (new Initializer())->run($this->directory);

        $expectedFiles = [
            '.php-cs-fixer.dist.php',
            'phpstan.neon.dist',
            'phpunit.xml.dist',
            'rector.php',
            'tests/ExampleTest.php',
            '.gitignore',
            '.gitattributes',
        ];

        foreach ($expectedFiles as $file) {
            self::assertFileExists($this->directory . '/' . $file);
        }

        $composer = json_decode(
            (string) file_get_contents($this->directory . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        self::assertSame('^8.5', $composer['require']['php']);
        self::assertSame('rector process --dry-run', $composer['scripts']['lint:rector']);
        self::assertSame('rector process', $composer['scripts']['fix:rector']);
        self::assertSame('phpunit', $composer['scripts']['test']);
        self::assertSame('phpstan analyse --no-progress', $composer['scripts']['lint:types']);
    }

    public function testRunningTwiceDoesNotModifyExistingFiles(): void
    {
        $initializer = new Initializer();
        $initializer->run($this->directory);

        $first = [];

        $files = [
            'composer.json',
            '.gitignore',
            '.gitattributes',
            '.php-cs-fixer.dist.php',
            'phpstan.neon.dist',
            'phpunit.xml.dist',
            'rector.php',
            'tests/ExampleTest.php',
        ];

        foreach ($files as $file) {
            $first[$file] = file_get_contents($this->directory . '/' . $file);
        }

        $initializer->run($this->directory);

        foreach ($first as $file => $contents) {
            self::assertSame(
                $contents,
                file_get_contents($this->directory . '/' . $file),
                $file
            );
        }
    }

    public function testPreservesExistingTestsAndScripts(): void
    {
        mkdir($this->directory . '/tests');

        file_put_contents(
            $this->directory . '/tests/RouterTest.php',
            '<?php // existing test'
        );

        file_put_contents(
            $this->directory . '/composer.json',
            '{"scripts":{"test":"custom-test"}}'
        );

        (new Initializer())->run($this->directory);

        self::assertFileDoesNotExist($this->directory . '/tests/ExampleTest.php');

        $composer = json_decode(
            (string) file_get_contents($this->directory . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        self::assertSame('custom-test', $composer['scripts']['test']);
    }

    public function testPreservesExistingRectorConfigurationAndScripts(): void
    {
        $path = $this->directory . '/rector.php';
        $existing = '<?php // custom configuration';
        file_put_contents($path, $existing);
        file_put_contents(
            $this->directory . '/composer.json',
            '{"scripts":{"lint:rector":"custom-lint","fix:rector":"custom-fix"}}'
        );

        (new Initializer())->run($this->directory);

        self::assertSame($existing, file_get_contents($path));
        $composer = json_decode(
            (string) file_get_contents($this->directory . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        self::assertSame('custom-lint', $composer['scripts']['lint:rector']);
        self::assertSame('custom-fix', $composer['scripts']['fix:rector']);
    }

    public function testCreatesGitAttributesWithRequiredRules(): void
    {
        (new Initializer())->run($this->directory);

        $path = $this->directory . '/.gitattributes';

        self::assertFileExists($path);

        $contents = (string) file_get_contents($path);

        self::assertStringContainsString('* text=auto', $contents);
        self::assertStringContainsString('*.php text eol=lf', $contents);
    }

    public function testPreservesExistingGitAttributes(): void
    {
        $path = $this->directory . '/.gitattributes';

        $existing = "*.md text eol=lf\n* text=auto\n*.php text eol=lf\n";

        file_put_contents($path, $existing);

        (new Initializer())->run($this->directory);

        self::assertSame($existing, file_get_contents($path));
    }

    public function testAddsMissingGitAttributesWithoutDuplicates(): void
    {
        $path = $this->directory . '/.gitattributes';

        file_put_contents(
            $path,
            "*.md text eol=lf\n* text=auto\n"
        );

        $initializer = new Initializer();

        $initializer->run($this->directory);
        $initializer->run($this->directory);

        $contents = (string) file_get_contents($path);
        $lines = preg_split('/\R/', $contents) ?: [];

        self::assertContains('*.md text eol=lf', $lines);
        self::assertSame(1, count(array_keys($lines, '* text=auto', true)));
        self::assertSame(1, count(array_keys($lines, '*.php text eol=lf', true)));
    }
}
