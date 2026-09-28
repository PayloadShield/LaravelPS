<?php

declare(strict_types=1);

namespace PayloadShield\LaravelPS\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PayloadShield\ComPHPPS\Crypto;
use PayloadShield\ComPHPPS\PayloadShieldEnc;

/**
 * Middleware that decrypts the incoming request payload.
 *
 * Expects the request body to be:
 *   {"encrypted": "<encoded-data>"}
 *
 * The decrypted data replaces the request input so the controller
 * receives the plain data.
 *
 * Usage in routes:
 *   Route::post('/api/process', [Controller::class, 'process'])
 *       ->middleware('payloadshield.decrypt:aes-gcm-256');
 */
class DecryptRequest
{
    public function handle(Request $request, Closure $next, ?string $encryptionType = null): mixed
    {
        $handlerName = $encryptionType ?? config('payloadshield.default', 'base64');

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

        return $next($request);
    }
}
