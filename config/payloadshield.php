<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Encryption Handler
    |--------------------------------------------------------------------------
    |
    | The encryption handler used when none is specified on the middleware.
    | Must be one of: base64, fernet, aes-gcm-256, chacha20-poly1305,
    | rsa-hybrid, ecdh-aes-gcm, ecies, hpke
    |
    */
    'default' => env('PAYLOADSHIELD_DEFAULT', 'base64'),

    /*
    |--------------------------------------------------------------------------
    | Symmetric Key
    |--------------------------------------------------------------------------
    |
    | Used by the "fernet", "aes-gcm-256" and "chacha20-poly1305" handlers.
    | Must resolve to exactly 32 bytes (raw string or base64-encoded).
    |
    */
    'key' => env('PAYLOADSHIELD_KEY'),

    /*
    |--------------------------------------------------------------------------
    | RSA Keys (rsa-hybrid)
    |--------------------------------------------------------------------------
    |
    | PEM file paths OR raw PEM content for the rsa-hybrid handler.
    |
    */
    'private_key' => env('PAYLOADSHIELD_PRIVATE_KEY'),
    'public_key'  => env('PAYLOADSHIELD_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | EC P-256 Keys (ecdh-aes-gcm, ecies)
    |--------------------------------------------------------------------------
    |
    | PEM file paths OR raw PEM content for the ecdh-aes-gcm / ecies handlers.
    |
    */
    'ec_private_key' => env('PAYLOADSHIELD_EC_PRIVATE_KEY'),
    'ec_public_key'  => env('PAYLOADSHIELD_EC_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | X25519 Keys (hpke)
    |--------------------------------------------------------------------------
    |
    | PEM file paths OR raw PEM content for the hpke handler.
    | Requires the ext-sodium PHP extension.
    |
    */
    'hpke_private_key' => env('PAYLOADSHIELD_HPKE_PRIVATE_KEY'),
    'hpke_public_key'  => env('PAYLOADSHIELD_HPKE_PUBLIC_KEY'),
];
