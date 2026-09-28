<?php

declare(strict_types=1);

namespace PayloadShield\LaravelPS\Tests;

/**
 * Generates test PEM key files if they don't exist.
 * Keys are stored in tests/keys/ and ignored by .gitignore.
 */
final class KeyGenerator
{
    private const KEYS_DIR = __DIR__ . '/keys';

    public static function ensureKeysExist(): void
    {
        if (!is_dir(self::KEYS_DIR)) {
            mkdir(self::KEYS_DIR, 0755, true);
        }

        self::generateRSAKeys();
        self::generateECKeys();
        self::generateX25519Keys();
    }

    /**
     * Return the PayloadShieldEnc-compatible config array pointing at
     * the test key files + a symmetric key.
     *
     * @return array<string, string|null>
     */
    public static function getTestConfig(): array
    {
        return [
            'Key'            => '12345678901234567890123456789012', // 32 bytes
            'PrivateKey'     => self::KEYS_DIR . '/rsa_private.pem',
            'PublicKey'      => self::KEYS_DIR . '/rsa_public.pem',
            'ECPrivateKey'   => self::KEYS_DIR . '/ec_private.pem',
            'ECPublicKey'    => self::KEYS_DIR . '/ec_public.pem',
            'HPKEPrivateKey' => self::KEYS_DIR . '/x25519_private.pem',
            'HPKEPublicKey'  => self::KEYS_DIR . '/x25519_public.pem',
        ];
    }

    /**
     * Return the Laravel config/payloadshield.php-compatible array.
     */
    public static function getLaravelConfig(): array
    {
        return [
            'default'          => 'base64',
            'key'              => '12345678901234567890123456789012',
            'private_key'      => self::KEYS_DIR . '/rsa_private.pem',
            'public_key'       => self::KEYS_DIR . '/rsa_public.pem',
            'ec_private_key'   => self::KEYS_DIR . '/ec_private.pem',
            'ec_public_key'    => self::KEYS_DIR . '/ec_public.pem',
            'hpke_private_key' => self::KEYS_DIR . '/x25519_private.pem',
            'hpke_public_key'  => self::KEYS_DIR . '/x25519_public.pem',
        ];
    }

    // ========================================================================
    // RSA 2048
    // ========================================================================

    private static function generateRSAKeys(): void
    {
        $privatePath = self::KEYS_DIR . '/rsa_private.pem';
        $publicPath  = self::KEYS_DIR . '/rsa_public.pem';

        if (file_exists($privatePath) && file_exists($publicPath)) {
            return;
        }

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($key, $privatePem);
        $details   = openssl_pkey_get_details($key);
        $publicPem = $details['key'];

        file_put_contents($privatePath, $privatePem);
        file_put_contents($publicPath, $publicPem);
    }

    // ========================================================================
    // EC P-256
    // ========================================================================

    private static function generateECKeys(): void
    {
        $privatePath = self::KEYS_DIR . '/ec_private.pem';
        $publicPath  = self::KEYS_DIR . '/ec_public.pem';

        if (file_exists($privatePath) && file_exists($publicPath)) {
            return;
        }

        $key = openssl_pkey_new([
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        openssl_pkey_export($key, $privatePem);
        $details   = openssl_pkey_get_details($key);
        $publicPem = $details['key'];

        file_put_contents($privatePath, $privatePem);
        file_put_contents($publicPath, $publicPem);
    }

    // ========================================================================
    // X25519 (via sodium)
    // ========================================================================

    private static function generateX25519Keys(): void
    {
        $privatePath = self::KEYS_DIR . '/x25519_private.pem';
        $publicPath  = self::KEYS_DIR . '/x25519_public.pem';

        if (file_exists($privatePath) && file_exists($publicPath)) {
            return;
        }

        if (!extension_loaded('sodium')) {
            // Skip HPKE key generation if sodium is not available
            return;
        }

        // Generate X25519 key pair
        $keypair    = sodium_crypto_box_keypair();
        $privateKey = sodium_crypto_box_secretkey($keypair);
        $publicKey  = sodium_crypto_box_publickey($keypair);

        // Wrap in PKCS#8 / SubjectPublicKeyInfo DER, then PEM-encode.
        // X25519 private key PKCS#8 DER prefix (RFC 8410):
        //   SEQUENCE { SEQUENCE { OID 1.3.101.110 }, OCTET STRING { OCTET STRING { <32 bytes> } } }
        $privateDerPrefix = hex2bin('302e020100300506032b656e042204200000000000000000000000000000000000000000000000000000000000000000');
        $privateDer = substr($privateDerPrefix, 0, -32) . $privateKey;
        $privatePem = "-----BEGIN PRIVATE KEY-----\n" . chunk_split(base64_encode($privateDer), 64) . "-----END PRIVATE KEY-----\n";

        // X25519 public key SubjectPublicKeyInfo DER prefix (RFC 8410):
        //   SEQUENCE { SEQUENCE { OID 1.3.101.110 }, BIT STRING { <32 bytes> } }
        $publicDerPrefix = hex2bin('302a300506032b656e03210000000000000000000000000000000000000000000000000000000000000000');
        $publicDer = substr($publicDerPrefix, 0, -32) . $publicKey;
        $publicPem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($publicDer), 64) . "-----END PUBLIC KEY-----\n";

        file_put_contents($privatePath, $privatePem);
        file_put_contents($publicPath, $publicPem);
    }
}