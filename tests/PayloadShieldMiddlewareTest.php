<?php

declare(strict_types=1);

namespace PayloadShield\LaravelPS\Tests;

use Illuminate\Support\Facades\Route;
use PayloadShield\ComPHPPS\Crypto;
use PayloadShield\LaravelPS\PayloadShield;

/**
 * Integration tests for all PayloadShield middleware with test routes.
 *
 * For each of the 8 built-in encryption handlers, three routes are registered:
 *   GET  /{prefix}       -> encrypt response only
 *   POST /{prefix}/dec   -> decrypt request only
 *   POST /{prefix}/cry   -> decrypt request + encrypt response
 */
class PayloadShieldMiddlewareTest extends TestCase
{
    /**
     * Maps handler name -> route prefix (mirrors FastAPIPS example).
     */
    private const ROUTE_PREFIX = [
        'base64'            => 'base',
        'fernet'            => 'fernet',
        'aes-gcm-256'       => 'aes',
        'chacha20-poly1305' => 'chacha',
        'rsa-hybrid'        => 'rsa',
        'ecdh-aes-gcm'      => 'ecdh',
        'ecies'             => 'ecies',
        'hpke'              => 'hpke',
    ];

    private const SAMPLE_PAYLOAD = ['message' => 'Hello, PayloadShield!', 'id' => 42];

    /**
     * Define test routes for all handlers.
     */
    protected function defineRoutes($router): void
    {
        foreach (self::ROUTE_PREFIX as $handler => $prefix) {
            // GET /{prefix} -> encrypt response
            $router->get("/{$prefix}", function () {
                return response()->json(self::SAMPLE_PAYLOAD);
            })->middleware(PayloadShield::encrypt($handler));

            // POST /{prefix}/dec -> decrypt request
            $router->post("/{$prefix}/dec", function (\Illuminate\Http\Request $request) {
                return response()->json(['received' => $request->all()]);
            })->middleware(PayloadShield::decrypt($handler));

            // POST /{prefix}/cry -> decrypt request + encrypt response
            $router->post("/{$prefix}/cry", function (\Illuminate\Http\Request $request) {
                return response()->json(['echo' => $request->all()]);
            })->middleware(PayloadShield::crypt($handler));
        }

        // Health check (no middleware)
        $router->get('/health', function () {
            return response()->json(['status' => 'ok']);
        });
    }

    // ========================================================================
    // Health check
    // ========================================================================

    public function test_health_check(): void
    {
        $response = $this->getJson('/health');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    // ========================================================================
    // Base64
    // ========================================================================

    public function test_base64_encrypt_response(): void
    {
        $response = $this->getJson('/base');
        $response->assertStatus(200);
        $response->assertJsonStructure(['encrypted']);

        $decoded = Crypto::decode('base64', $response->json('encrypted'));
        $this->assertEquals(self::SAMPLE_PAYLOAD, $decoded);
    }

    public function test_base64_decrypt_request(): void
    {
        $encoded = Crypto::encode('base64', ['username' => 'admin', 'password' => 'secret']);
        $response = $this->postJson('/base/dec', ['encrypted' => $encoded]);
        $response->assertStatus(200);
        $response->assertJson(['received' => ['username' => 'admin', 'password' => 'secret']]);
    }

    public function test_base64_crypt_roundtrip(): void
    {
        $payload = ['name' => 'Alice'];
        $encoded = Crypto::encode('base64', $payload);
        $response = $this->postJson('/base/cry', ['encrypted' => $encoded]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['encrypted']);

        $decoded = Crypto::decode('base64', $response->json('encrypted'));
        $this->assertEquals(['echo' => $payload], $decoded);
    }

    // ========================================================================
    // Fernet
    // ========================================================================

    public function test_fernet_encrypt_response(): void
    {
        $response = $this->getJson('/fernet');
        $response->assertStatus(200);
        $response->assertJsonStructure(['encrypted']);

        $decoded = Crypto::decode('fernet', $response->json('encrypted'));
        $this->assertEquals(self::SAMPLE_PAYLOAD, $decoded);
    }

    public function test_fernet_decrypt_request(): void
    {
        $encoded = Crypto::encode('fernet', ['key' => 'value']);
        $response = $this->postJson('/fernet/dec', ['encrypted' => $encoded]);
        $response->assertStatus(200);
        $response->assertJson(['received' => ['key' => 'value']]);
    }

    public function test_fernet_crypt_roundtrip(): void
    {
        $payload = ['secret' => 'data'];
        $encoded = Crypto::encode('fernet', $payload);
        $response = $this->postJson('/fernet/cry', ['encrypted' => $encoded]);
        $response->assertStatus(200);

        $decoded = Crypto::decode('fernet', $response->json('encrypted'));
        $this->assertEquals(['echo' => $payload], $decoded);
    }

    // ========================================================================
    // AES-GCM-256
    // ========================================================================

    public function test_aesgcm256_encrypt_response(): void
    {
        $response = $this->getJson('/aes');
        $response->assertStatus(200);
        $response->assertJsonStructure(['encrypted']);

        $decoded = Crypto::decode('aes-gcm-256', $response->json('encrypted'));
        $this->assertEquals(self::SAMPLE_PAYLOAD, $decoded);
    }

    public function test_aesgcm256_decrypt_request(): void
    {
        $encoded = Crypto::encode('aes-gcm-256', ['id' => 1]);
        $response = $this->postJson('/aes/dec', ['encrypted' => $encoded]);
        $response->assertStatus(200);
        $response->assertJson(['received' => ['id' => 1]]);
    }

    public function test_aesgcm256_crypt_roundtrip(): void
    {
        $payload = ['action' => 'process'];
        $encoded = Crypto::encode('aes-gcm-256', $payload);
        $response = $this->postJson('/aes/cry', ['encrypted' => $encoded]);
        $response->assertStatus(200);

        $decoded = Crypto::decode('aes-gcm-256', $response->json('encrypted'));
        $this->assertEquals(['echo' => $payload], $decoded);
    }

    // ========================================================================
    // ChaCha20-Poly1305
    // ========================================================================

    public function test_chacha_encrypt_response(): void
    {
        $response = $this->getJson('/chacha');
        $response->assertStatus(200);
        $response->assertJsonStructure(['encrypted']);

        $decoded = Crypto::decode('chacha20-poly1305', $response->json('encrypted'));
        $this->assertEquals(self::SAMPLE_PAYLOAD, $decoded);
    }

    public function test_chacha_decrypt_request(): void
    {
        $encoded = Crypto::encode('chacha20-poly1305', ['msg' => 'hi']);
        $response = $this->postJson('/chacha/dec', ['encrypted' => $encoded]);
        $response->assertStatus(200);
        $response->assertJson(['received' => ['msg' => 'hi']]);
    }

    public function test_chacha_crypt_roundtrip(): void
    {
        $payload = ['stream' => 'cipher'];
        $encoded = Crypto::encode('chacha20-poly1305', $payload);
        $response = $this->postJson('/chacha/cry', ['encrypted' => $encoded]);
        $response->assertStatus(200);

        $decoded = Crypto::decode('chacha20-poly1305', $response->json('encrypted'));
        $this->assertEquals(['echo' => $payload], $decoded);
    }

    // ========================================================================
    // RSA Hybrid
    // ========================================================================

    public function test_rsa_hybrid_encrypt_response(): void
    {
        $response = $this->getJson('/rsa');
        $response->assertStatus(200);
        $response->assertJsonStructure(['encrypted']);

        $decoded = Crypto::decode('rsa-hybrid', $response->json('encrypted'));
        $this->assertEquals(self::SAMPLE_PAYLOAD, $decoded);
    }

    public function test_rsa_hybrid_decrypt_request(): void
    {
        $encoded = Crypto::encode('rsa-hybrid', ['sensitive' => 'payload']);
        $response = $this->postJson('/rsa/dec', ['encrypted' => $encoded]);
        $response->assertStatus(200);
        $response->assertJson(['received' => ['sensitive' => 'payload']]);
    }

    public function test_rsa_hybrid_crypt_roundtrip(): void
    {
        $payload = ['asymmetric' => 'test'];
        $encoded = Crypto::encode('rsa-hybrid', $payload);
        $response = $this->postJson('/rsa/cry', ['encrypted' => $encoded]);
        $response->assertStatus(200);

        $decoded = Crypto::decode('rsa-hybrid', $response->json('encrypted'));
        $this->assertEquals(['echo' => $payload], $decoded);
    }

    // ========================================================================
    // ECDH-AES-GCM
    // ========================================================================

    public function test_ecdh_encrypt_response(): void
    {
        $response = $this->getJson('/ecdh');
        $response->assertStatus(200);
        $response->assertJsonStructure(['encrypted']);

        $decoded = Crypto::decode('ecdh-aes-gcm', $response->json('encrypted'));
        $this->assertEquals(self::SAMPLE_PAYLOAD, $decoded);
    }

    public function test_ecdh_decrypt_request(): void
    {
        $encoded = Crypto::encode('ecdh-aes-gcm', ['data' => 'value']);
        $response = $this->postJson('/ecdh/dec', ['encrypted' => $encoded]);
        $response->assertStatus(200);
        $response->assertJson(['received' => ['data' => 'value']]);
    }

    public function test_ecdh_crypt_roundtrip(): void
    {
        $payload = ['ecdh' => 'round-trip'];
        $encoded = Crypto::encode('ecdh-aes-gcm', $payload);
        $response = $this->postJson('/ecdh/cry', ['encrypted' => $encoded]);
        $response->assertStatus(200);

        $decoded = Crypto::decode('ecdh-aes-gcm', $response->json('encrypted'));
        $this->assertEquals(['echo' => $payload], $decoded);
    }

    // ========================================================================
    // ECIES
    // ========================================================================

    public function test_ecies_encrypt_response(): void
    {
        $response = $this->getJson('/ecies');
        $response->assertStatus(200);
        $response->assertJsonStructure(['encrypted']);

        $decoded = Crypto::decode('ecies', $response->json('encrypted'));
        $this->assertEquals(self::SAMPLE_PAYLOAD, $decoded);
    }

    public function test_ecies_decrypt_request(): void
    {
        $encoded = Crypto::encode('ecies', ['field' => 'value']);
        $response = $this->postJson('/ecies/dec', ['encrypted' => $encoded]);
        $response->assertStatus(200);
        $response->assertJson(['received' => ['field' => 'value']]);
    }

    public function test_ecies_crypt_roundtrip(): void
    {
        $payload = ['ecies' => 'test'];
        $encoded = Crypto::encode('ecies', $payload);
        $response = $this->postJson('/ecies/cry', ['encrypted' => $encoded]);
        $response->assertStatus(200);

        $decoded = Crypto::decode('ecies', $response->json('encrypted'));
        $this->assertEquals(['echo' => $payload], $decoded);
    }

    // ========================================================================
    // HPKE
    // ========================================================================

    public function test_hpke_encrypt_response(): void
    {
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('ext-sodium is required for HPKE tests');
        }

        $response = $this->getJson('/hpke');
        $response->assertStatus(200);
        $response->assertJsonStructure(['encrypted']);

        $decoded = Crypto::decode('hpke', $response->json('encrypted'));
        $this->assertEquals(self::SAMPLE_PAYLOAD, $decoded);
    }

    public function test_hpke_decrypt_request(): void
    {
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('ext-sodium is required for HPKE tests');
        }

        $encoded = Crypto::encode('hpke', ['message' => 'encrypted']);
        $response = $this->postJson('/hpke/dec', ['encrypted' => $encoded]);
        $response->assertStatus(200);
        $response->assertJson(['received' => ['message' => 'encrypted']]);
    }

    public function test_hpke_crypt_roundtrip(): void
    {
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('ext-sodium is required for HPKE tests');
        }

        $payload = ['hpke' => 'round-trip'];
        $encoded = Crypto::encode('hpke', $payload);
        $response = $this->postJson('/hpke/cry', ['encrypted' => $encoded]);
        $response->assertStatus(200);

        $decoded = Crypto::decode('hpke', $response->json('encrypted'));
        $this->assertEquals(['echo' => $payload], $decoded);
    }

    // ========================================================================
    // Error handling
    // ========================================================================

    public function test_decrypt_with_invalid_data_returns_400(): void
    {
        $response = $this->postJson('/aes/dec', ['encrypted' => 'not-valid-encrypted-data']);
        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    public function test_decrypt_without_encrypted_field_passes_through(): void
    {
        $response = $this->postJson('/base/dec', ['plain' => 'data']);
        $response->assertStatus(200);
    }
}