<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ConsumerTest extends TestCase
{
    public function testFixtureExists(): void
    {
        $fixture = dirname(__DIR__)
            . '/Fixtures/Calculator.php';

        $this->assertFileExists($fixture);
    }
}
