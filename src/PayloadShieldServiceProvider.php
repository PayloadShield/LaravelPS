<?php

declare(strict_types=1);

namespace PayloadShield\LaravelPS;

use Illuminate\Support\ServiceProvider;
use PayloadShield\ComPHPPS\PayloadShieldEnc;

class PayloadShieldServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/payloadshield.php', 'payloadshield');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish the config file
        $this->publishes([
            __DIR__ . '/../config/payloadshield.php' => config_path('payloadshield.php'),
        ], 'payloadshield-config');

        // Initialize PayloadShieldEnc with Laravel config values
        $cfg = (array) config('payloadshield', []);

        PayloadShieldEnc::init([
            'Key'            => $cfg['key'] ?? null,
            'PrivateKey'     => $cfg['private_key'] ?? null,
            'PublicKey'      => $cfg['public_key'] ?? null,
            'ECPrivateKey'   => $cfg['ec_private_key'] ?? null,
            'ECPublicKey'    => $cfg['ec_public_key'] ?? null,
            'HPKEPrivateKey' => $cfg['hpke_private_key'] ?? null,
            'HPKEPublicKey'  => $cfg['hpke_public_key'] ?? null,
        ]);

        // Register middleware aliases
        $router = $this->app['router'];
        $router->aliasMiddleware('payloadshield.encrypt', Middleware\EncryptResponse::class);
        $router->aliasMiddleware('payloadshield.decrypt', Middleware\DecryptRequest::class);
        $router->aliasMiddleware('payloadshield.crypt', Middleware\CryptPayload::class);
    }
}
