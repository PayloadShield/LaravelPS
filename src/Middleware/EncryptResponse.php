<?php

declare(strict_types=1);

namespace PayloadShield\LaravelPS\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PayloadShield\ComPHPPS\Crypto;
use PayloadShield\ComPHPPS\PayloadShieldEnc;

/**
 * Middleware that encrypts the outgoing response payload.
 *
 * The response data is encoded with the configured handler and returned as:
 *   {"encrypted": "<encoded-data>"}
 *
 * Usage in routes:
 *   Route::get('/api/data', fn () => ['msg' => 'hi'])
 *       ->middleware('payloadshield.encrypt:aes-gcm-256');
 */
class EncryptResponse
{
    public function handle(Request $request, Closure $next, ?string $encryptionType = null): mixed
    {
        $response = $next($request);

        $handlerName = $encryptionType ?? config('payloadshield.default', 'base64');

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
