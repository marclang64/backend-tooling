<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ConsumerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir()
            . '/backend-tooling-test-'
            . bin2hex(random_bytes(8));

        if (!mkdir($this->tempDir, 0777, true)) {
            throw new RuntimeException(
                'Could not create temporary directory.'
            );
        }
    }

    protected function tearDown(): void
    {
        try {
            $this->removeDirectory($this->tempDir);
        } finally {
            parent::tearDown();
        }
    }

    public function testTemporaryDirectoryExists(): void
    {
        $nestedDirectory = $this->tempDir . '/nested';

        mkdir($nestedDirectory);

        file_put_contents(
            $nestedDirectory . '/example.txt',
            'Hello from PHPUnit!'
        );

        $this->assertDirectoryExists($nestedDirectory);
        $this->assertFileExists(
            $nestedDirectory . '/example.txt'
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->removeDirectory($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }

    public function testCanExecutePhp(): void
    {
        $process = new Process([
            PHP_BINARY,
            '--version',
        ]);

        $process->run();

        $this->assertTrue($process->isSuccessful());
        $this->assertStringContainsString(
            'PHP',
            $process->getOutput()
        );
    }

    public function testCanInstallBackendTooling(): void
    {
        $packagePath = dirname(__DIR__, 2);

        $composerJson = [
            'name' => 'marclang64/backend-tooling-test',
            'type' => 'project',
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => $packagePath,
                    'options' => [
                        'symlink' => false,
                    ],
                ],
            ],
            'require-dev' => [
                'marclang64/backend-tooling' => '@dev',
            ],
        ];

        file_put_contents(
            $this->tempDir . '/composer.json',
            json_encode(
                $composerJson,
                JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
            )
        );

        $process = Process::fromShellCommandline(
            'composer install --no-interaction --no-progress',
            $this->tempDir
        );

        $process->setTimeout(120);
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            $process->getOutput() . $process->getErrorOutput()
        );

        $this->assertFileExists(
            $this->tempDir
            . '/vendor/marclang64/backend-tooling/configs/phpstan.neon'
        );

        $this->assertFileExists(
            $this->tempDir
            . '/vendor/marclang64/backend-tooling/configs/php-cs-fixer.php'
        );
    }
}
