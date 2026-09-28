<?php

declare(strict_types=1);

namespace PayloadShield\LaravelPS\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PayloadShield\ComPHPPS\Crypto;
use PayloadShield\ComPHPPS\PayloadShieldEnc;

/**
 * Middleware that decrypts the incoming request payload AND encrypts
 * the outgoing response payload using the same handler.
 *
 * Expects: {"encrypted": "<encoded-data>"}
 * Returns: {"encrypted": "<encoded-data>"}
 *
 * Usage in routes:
 *   Route::post('/api/secure', [Controller::class, 'secure'])
 *       ->middleware('payloadshield.crypt:aes-gcm-256');
 */
class CryptPayload
{
    public function handle(Request $request, Closure $next, ?string $encryptionType = null): mixed
    {
        $handlerName = $encryptionType ?? config('payloadshield.default', 'base64');

        // --- Decrypt incoming request ---
        $encrypted = $request->input('encrypted');

        if ($encrypted !== null && is_string($encrypted)) {
            try {
                $decrypted = Crypto::decode($handlerName, $encrypted);

                if (is_array($decrypted)) {
                    $request->merge($decrypted);
                    $request->replace($decrypted);
                }
            } catch (\Throwable $e) {
                return new JsonResponse(
                    ['error' => 'Failed to decrypt request: ' . $e->getMessage()],
                    400
                );
            }
        }

        $response = $next($request);

        // --- Encrypt outgoing response ---
        $data = $this->extractResponseData($response);
        if ($data === null) {
            return $response;
        }

        $encoded = Crypto::encode($handlerName, $data);

        return new JsonResponse(['encrypted' => $encoded]);
    }

    /**
     * Extract JSON-serializable data from various response types.
     */
    private function extractResponseData(mixed $response): mixed
    {
        if ($response instanceof JsonResponse) {
            return $response->getData(true);
        }

        if ($response instanceof \Illuminate\Http\Response) {
            $content = $response->getContent();
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
        }

        return null;
    }
}
