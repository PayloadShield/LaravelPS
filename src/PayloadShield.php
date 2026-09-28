<?php

declare(strict_types=1);

namespace PayloadShield\LaravelPS;

use PayloadShield\LaravelPS\Middleware\CryptPayload;
use PayloadShield\LaravelPS\Middleware\DecryptRequest;
use PayloadShield\LaravelPS\Middleware\EncryptResponse;

/**
 * Static helper for applying PayloadShield middleware to routes.
 *
 * Mirrors the FastAPIPS decorator pattern:
 *   Python:  @PayloadShield.encrypt("aes-gcm-256")
 *   PHP:     ->middleware(PayloadShield::encrypt('aes-gcm-256'))
 *
 * Usage:
 *   use PayloadShield\LaravelPS\PayloadShield;
 *
 *   Route::get('/api/data', fn () => ['msg' => 'hi'])
 *       ->middleware(PayloadShield::encrypt('aes-gcm-256'));
 *
 *   Route::post('/api/process', [Controller::class, 'process'])
 *       ->middleware(PayloadShield::decrypt('aes-gcm-256'));
 *
 *   Route::post('/api/secure', [Controller::class, 'secure'])
 *       ->middleware(PayloadShield::crypt('aes-gcm-256'));
 */
final class PayloadShield
{
    private function __construct()
    {
    }

    /**
     * Return the middleware string for encrypting the response.
     *
     * @param string $encryptionType Handler name (e.g. "aes-gcm-256", "fernet").
     * @return string Middleware identifier for use in ->middleware().
     */
    public static function encrypt(string $encryptionType = 'base64'): string
    {
        return EncryptResponse::class . ':' . $encryptionType;
    }

    /**
     * Return the middleware string for decrypting the request.
     *
     * @param string $encryptionType Handler name (e.g. "aes-gcm-256", "fernet").
     * @return string Middleware identifier for use in ->middleware().
     */
    public static function decrypt(string $encryptionType = 'base64'): string
    {
        return DecryptRequest::class . ':' . $encryptionType;
    }

    /**
     * Return the middleware string for decrypting the request
     * AND encrypting the response.
     *
     * @param string $encryptionType Handler name (e.g. "aes-gcm-256", "fernet").
     * @return string Middleware identifier for use in ->middleware().
     */
    public static function crypt(string $encryptionType = 'base64'): string
    {
        return CryptPayload::class . ':' . $encryptionType;
    }
}
