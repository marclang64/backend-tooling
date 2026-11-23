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

        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist', 'tests/ExampleTest.php', '.gitignore'] as $file) {
            self::assertFileExists($this->directory . '/' . $file);
        }
        $composer = json_decode((string) file_get_contents($this->directory . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('phpunit', $composer['scripts']['test']);
        self::assertSame('phpstan analyse --no-progress', $composer['scripts']['lint:types']);
    }

    public function testRunningTwiceDoesNotModifyExistingFiles(): void
    {
        $initializer = new Initializer();
        $initializer->run($this->directory);
        $first = [];
        foreach (['composer.json', '.gitignore', '.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist', 'tests/ExampleTest.php'] as $file) {
            $first[$file] = file_get_contents($this->directory . '/' . $file);
        }
        $initializer->run($this->directory);
        foreach ($first as $file => $contents) {
            self::assertSame($contents, file_get_contents($this->directory . '/' . $file), $file);
        }
    }

    public function testPreservesExistingTestsAndScripts(): void
    {
        mkdir($this->directory . '/tests');
        file_put_contents($this->directory . '/tests/RouterTest.php', '<?php // existing test');
        file_put_contents($this->directory . '/composer.json', '{"scripts":{"test":"custom-test"}}');
        (new Initializer())->run($this->directory);
        self::assertFileDoesNotExist($this->directory . '/tests/ExampleTest.php');
        $composer = json_decode((string) file_get_contents($this->directory . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('custom-test', $composer['scripts']['test']);
    }
}
