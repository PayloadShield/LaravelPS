<?php

declare(strict_types=1);

namespace PayloadShield\LaravelPS\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \PayloadShield\ComPHPPS\EncryptionHandlerInterface getHandler(string $name)
 * @method static string encode(string $handlerName, mixed $data)
 * @method static mixed decode(string $handlerName, string $encodedData)
 * @method static void registerHandler(string $name, \PayloadShield\ComPHPPS\EncryptionHandlerInterface $handler)
 * @method static string[] getAvailableHandlers()
 *
 * @see \PayloadShield\ComPHPPS\Crypto
 */
class PayloadShield extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \PayloadShield\ComPHPPS\Crypto::class;
    }

    /**
     * Encrypt data using the named handler.
     */
    public static function encrypt(string $handlerName, mixed $data): string
    {
        return \PayloadShield\ComPHPPS\Crypto::encode($handlerName, $data);
    }

    /**
     * Decrypt data using the named handler.
     */
    public static function decrypt(string $handlerName, string $encodedData): mixed
    {
        return \PayloadShield\ComPHPPS\Crypto::decode($handlerName, $encodedData);
    }
}
