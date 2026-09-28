<?php

declare(strict_types=1);

namespace PayloadShield\LaravelPS\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PayloadShield\ComPHPPS\PayloadShieldEnc;
use PayloadShield\LaravelPS\PayloadShieldServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        // Generate test keys if they don't exist
        KeyGenerator::ensureKeysExist();

        parent::setUp();
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            PayloadShieldServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        $config = KeyGenerator::getLaravelConfig();

        $app['config']->set('payloadshield', $config);

        // Re-initialize PayloadShieldEnc with test keys
        PayloadShieldEnc::init(KeyGenerator::getTestConfig());
    }
}