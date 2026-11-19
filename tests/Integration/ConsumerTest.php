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
        $this->createConsumerProject();
        $this->installConsumerDependencies();

        $this->assertFileExists(
            $this->tempDir
            . '/vendor/marclang64/backend-tooling/configs/phpstan.neon'
        );

        $this->assertFileExists(
            $this->tempDir
            . '/vendor/marclang64/backend-tooling/configs/php-cs-fixer.php'
        );
    }

    public function testPhpCsFixerFormatsCode(): void
    {
        $this->createConsumerProject([
            'friendsofphp/php-cs-fixer' => '^3.95',
        ]);

        $this->installConsumerDependencies();

        $this->assertFileExists(
            $this->tempDir . '/vendor/bin/php-cs-fixer'
        );

        $sourceDirectory = $this->tempDir . '/src';
        mkdir($sourceDirectory);

        $sourceFile = $sourceDirectory . '/Example.php';

        file_put_contents(
            $sourceFile,
            "<?php\n\n\$message = \"Hello World\";\n"
        );

        $config = <<<'PHP'
<?php

declare(strict_types=1);

$config = require __DIR__
    . '/vendor/marclang64/backend-tooling/configs/php-cs-fixer.php';

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src');

return $config->setFinder($finder);
PHP;

        file_put_contents(
            $this->tempDir . '/.php-cs-fixer.dist.php',
            $config
        );

        $process = new Process(
            [
                PHP_BINARY,
                'vendor/bin/php-cs-fixer',
                'fix',
                '--using-cache=no',
                '--no-interaction',
            ],
            $this->tempDir
        );

        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            $process->getOutput() . $process->getErrorOutput()
        );

        $formattedCode = file_get_contents($sourceFile);

        $this->assertIsString($formattedCode);

        $this->assertStringContainsString(
            "'Hello World'",
            $formattedCode
        );

        $this->assertStringNotContainsString(
            '"Hello World"',
            $formattedCode
        );
    }

    private function createConsumerProject(
        array $dependencies = []
    ): void {
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
            'require-dev' => array_merge(
                [
                    'marclang64/backend-tooling' => '@dev',
                ],
                $dependencies
            ),
        ];

        file_put_contents(
            $this->tempDir . '/composer.json',
            json_encode(
                $composerJson,
                JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
            )
        );
    }

    private function installConsumerDependencies(): void
    {
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
}
