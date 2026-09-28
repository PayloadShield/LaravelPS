# LaravelPS

Pluggable Laravel middleware for encrypting/decrypting request and response payloads — the PHP equivalent of [FastAPIPS](https://github.com/PayloadShield/FastAPIPS).

[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-red)](https://laravel.com)
[![License](https://img.shields.io/badge/License-Apache%202.0-green)](LICENSE)

## Key Features

- **Pluggable encryption**: base64, Fernet, AES-GCM-256, ChaCha20-Poly1305,
  Hybrid RSA+AES, ECDH+AES-GCM, ECIES, and HPKE (RFC 9180) ship out of the
  box; register your own with `Crypto::registerHandler(...)`.
- **One-time key configuration**: set keys in `config/payloadshield.php` or
  `.env` — they initialize automatically via the service provider.
- **Route-agnostic**: no changes needed to your controller logic besides
  adding a middleware.
- **Three middleware modes**: `encrypt` (response only), `decrypt` (request
  only), `crypt` (both).

## Installation

```bash
composer require payloadshield/laravelps
```

Publish the config file:

```bash
php artisan vendor:publish --tag=payloadshield-config
```

## Configuration

Add the keys you need to your `.env`:

```env
# Default handler (used when no handler name is passed to middleware)
PAYLOADSHIELD_DEFAULT=aes-gcm-256

# Symmetric key — used by fernet, aes-gcm-256, chacha20-poly1305
PAYLOADSHIELD_KEY=your-32-byte-symmetric-key......

# RSA keys — used by rsa-hybrid (file path or raw PEM)
PAYLOADSHIELD_PRIVATE_KEY=/path/to/rsa_private.pem
PAYLOADSHIELD_PUBLIC_KEY=/path/to/rsa_public.pem

# EC P-256 keys — used by ecdh-aes-gcm, ecies (file path or raw PEM)
PAYLOADSHIELD_EC_PRIVATE_KEY=/path/to/ec_private.pem
PAYLOADSHIELD_EC_PUBLIC_KEY=/path/to/ec_public.pem

# X25519 keys — used by hpke (file path or raw PEM)
PAYLOADSHIELD_HPKE_PRIVATE_KEY=/path/to/x25519_private.pem
PAYLOADSHIELD_HPKE_PUBLIC_KEY=/path/to/x25519_public.pem
```

> Only set the keys required by the handlers you actually use.

Or configure directly in `config/payloadshield.php`:

```php
return [
    'default'          => 'aes-gcm-256',
    'key'              => env('PAYLOADSHIELD_KEY'),
    'private_key'      => env('PAYLOADSHIELD_PRIVATE_KEY'),
    'public_key'       => env('PAYLOADSHIELD_PUBLIC_KEY'),
    'ec_private_key'   => env('PAYLOADSHIELD_EC_PRIVATE_KEY'),
    'ec_public_key'    => env('PAYLOADSHIELD_EC_PUBLIC_KEY'),
    'hpke_private_key' => env('PAYLOADSHIELD_HPKE_PRIVATE_KEY'),
    'hpke_public_key'  => env('PAYLOADSHIELD_HPKE_PUBLIC_KEY'),
];
```

## Quick Start

### Using the static `PayloadShield` helper (recommended)

```php
use PayloadShield\LaravelPS\PayloadShield;

// Encrypt response only
Route::get('/api/data', function () {
    return response()->json(['message' => 'Hello, PayloadShield!']);
})->middleware(PayloadShield::encrypt('aes-gcm-256'));

// Decrypt request only
Route::post('/api/process', function (Request $request) {
    return response()->json(['received' => $request->all()]);
})->middleware(PayloadShield::decrypt('aes-gcm-256'));

// Decrypt request + encrypt response
Route::post('/api/secure', function (Request $request) {
    return response()->json(['echo' => $request->all()]);
})->middleware(PayloadShield::crypt('aes-gcm-256'));
```

### Using middleware aliases

The middleware aliases are registered automatically:

```php
// In routes/api.php
Route::get('/api/data', [DataController::class, 'index'])
    ->middleware('payloadshield.encrypt:aes-gcm-256');

Route::post('/api/process', [DataController::class, 'store'])
    ->middleware('payloadshield.decrypt:aes-gcm-256');

Route::post('/api/secure', [DataController::class, 'update'])
    ->middleware('payloadshield.crypt:aes-gcm-256');
```

### Applying to route groups

```php
Route::middleware(PayloadShield::crypt('aes-gcm-256'))->group(function () {
    Route::post('/api/users', [UserController::class, 'store']);
    Route::post('/api/orders', [OrderController::class, 'store']);
});
```

## Middleware Reference

### `payloadshield.encrypt` / `PayloadShield::encrypt($type)`

Encrypts the response payload only.

- **Input**: Normal request
- **Output**: `{"encrypted": "<encoded-data>"}`

### `payloadshield.decrypt` / `PayloadShield::decrypt($type)`

Decrypts the incoming request payload only.

- **Input**: `{"encrypted": "<encoded-data>"}`
- **Output**: Normal response (controller receives decrypted data via `$request->all()`)

### `payloadshield.crypt` / `PayloadShield::crypt($type)`

Decrypts the request AND encrypts the response.

- **Input**: `{"encrypted": "<encoded-data>"}`
- **Output**: `{"encrypted": "<encoded-data>"}`

## Supported Encryption Handlers

| Handler Name        | Algorithm                                           | Key Config Required                     |
|---------------------|-----------------------------------------------------|-----------------------------------------|
| `base64`            | Base64 encoding (obfuscation only)                  | None                                    |
| `fernet`            | Fernet (AES-128-CBC + HMAC-SHA256)                  | `key`                                   |
| `aes-gcm-256`       | AES-256-GCM                                         | `key`                                   |
| `chacha20-poly1305` | ChaCha20-Poly1305                                   | `key`                                   |
| `rsa-hybrid`        | RSA-OAEP-SHA256 + AES-256-GCM                      | `public_key` / `private_key`            |
| `ecdh-aes-gcm`      | Ephemeral ECDH (P-256) + HKDF-SHA256 + AES-256-GCM | `ec_public_key` / `ec_private_key`      |
| `ecies`             | ECIES (P-256, HKDF, AES-256-CTR, HMAC-SHA256)      | `ec_public_key` / `ec_private_key`      |
| `hpke`              | RFC 9180 HPKE (X25519 + ChaCha20-Poly1305)          | `hpke_public_key` / `hpke_private_key`  |

## Using Crypto Directly

You can also use the `Crypto` facade directly in your code (bypassing middleware):

```php
use PayloadShield\ComPHPPS\Crypto;

// Encrypt
$encoded = Crypto::encode('aes-gcm-256', ['user' => 'alice', 'role' => 'admin']);

// Decrypt
$decoded = Crypto::decode('aes-gcm-256', $encoded);
// => ['user' => 'alice', 'role' => 'admin']
```

## Custom Handlers

Implement `EncryptionHandlerInterface` from ComPHPPS and register it:

```php
use PayloadShield\ComPHPPS\Crypto;
use PayloadShield\ComPHPPS\EncryptionHandlerInterface;
use PayloadShield\LaravelPS\PayloadShield;

class MyCustomHandler implements EncryptionHandlerInterface
{
    public function encode(mixed $data, array $config = []): string
    {
        // your encryption logic
    }

    public function decode(string $encodedData, array $config = []): mixed
    {
        // your decryption logic
    }
}

// Register in a service provider's boot() method
Crypto::registerHandler('my-custom', new MyCustomHandler());

// Then use in routes
Route::post('/api/custom', [Controller::class, 'action'])
    ->middleware(PayloadShield::crypt('my-custom'));
```

## Error Handling

| Situation | Behavior |
|---|---|
| Request decryption fails | `400` JSON response: `{"error": "Failed to decrypt request: ..."}` |
| Unknown handler name | `RuntimeException`: `Encryption handler '<name>' not found. Available handlers: ...` |
| Missing required key | `RuntimeException`: `... requires 'Key' to be set via PayloadShieldEnc::init(...)` |

## FastAPIPS Comparison

This package is the Laravel equivalent of [FastAPIPS](https://github.com/PayloadShield/FastAPIPS):

| FastAPIPS (Python)                         | LaravelPS (PHP)                                          |
|--------------------------------------------|----------------------------------------------------------|
| `@PayloadShield.encrypt("aes-gcm-256")`   | `->middleware(PayloadShield::encrypt('aes-gcm-256'))`    |
| `@PayloadShield.decrypt("aes-gcm-256")`   | `->middleware(PayloadShield::decrypt('aes-gcm-256'))`    |
| `@PayloadShield.crypt("aes-gcm-256")`     | `->middleware(PayloadShield::crypt('aes-gcm-256'))`      |
| `PayloadShieldEnc.init({...})`             | `config/payloadshield.php` + `.env`                      |
| `compyps` dependency                       | `payloadshield/comphpps` dependency                      |

Cross-language encrypt/decrypt is fully supported:

```python
# Python (FastAPIPS) — encrypt
from fastapi_payloadshield import PayloadShieldEnc, get_handler
PayloadShieldEnc.init({"Key": "your-32-byte-symmetric-key......"})
encoded = get_handler("aes-gcm-256").encode({"user": "alice"}, PayloadShieldEnc.get_config())
```

```php
// PHP (LaravelPS) — decrypt
// Config in .env: PAYLOADSHIELD_KEY=your-32-byte-symmetric-key......
$decoded = Crypto::decode('aes-gcm-256', $encoded);
// => ['user' => 'alice']
```

## Project Layout

```
├── config/
│   └── payloadshield.php            # Publishable Laravel config
├── src/
│   ├── PayloadShieldServiceProvider.php  # Auto-discovered service provider
│   ├── PayloadShield.php            # Static helper (encrypt/decrypt/crypt)
│   ├── Facades/
│   │   └── PayloadShield.php        # Laravel Facade
│   └── Middleware/
│       ├── EncryptResponse.php      # Encrypts response only
│       ├── DecryptRequest.php       # Decrypts request only
│       └── CryptPayload.php        # Decrypts request + encrypts response
├── tests/
├── composer.json
├── LICENSE
└── README.md
```

## Requirements

- PHP >= 8.1
- Laravel 10, 11, or 12
- `ext-openssl` — required for all handlers except `base64`
- `ext-json` — required for payload serialization
- `ext-sodium` — required only for the `hpke` handler

## License

[Apache License 2.0](LICENSE)
