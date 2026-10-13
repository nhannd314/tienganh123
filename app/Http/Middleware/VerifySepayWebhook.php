<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a request through only when it really comes from SePay.
 *
 * With SEPAY_WEBHOOK_SECRET set it checks the HMAC-SHA256 signature (`X-SePay-Signature` over `{timestamp}.{raw body}`,
 * with `X-SePay-Timestamp` no more than five minutes off). Otherwise, with SEPAY_API_KEY set, it checks the
 * `Authorization: Apikey ...` header. With neither set the webhook is closed, so a forged payment is never accepted.
 */
class VerifySepayWebhook
{
    private const TIMESTAMP_TOLERANCE_SECONDS = 300;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.sepay.webhook_secret');
        $apiKey = config('services.sepay.api_key');

        if (filled($secret)) {
            return $this->hasValidSignature($request, $secret) ? $next($request) : $this->rejected();
        }

        if (filled($apiKey)) {
            return $this->hasValidApiKey($request, $apiKey) ? $next($request) : $this->rejected();
        }

        return response()->json(['success' => false, 'message' => 'The SePay webhook is not configured.'], 503);
    }

    private function hasValidSignature(Request $request, string $secret): bool
    {
        $timestamp = (string) $request->header('X-SePay-Timestamp');
        $signature = (string) $request->header('X-SePay-Signature');

        if (! ctype_digit($timestamp) || abs(now()->timestamp - (int) $timestamp) > self::TIMESTAMP_TOLERANCE_SECONDS) {
            return false;
        }

        return hash_equals('sha256='.hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret), $signature);
    }

    private function hasValidApiKey(Request $request, string $apiKey): bool
    {
        $header = (string) $request->header('Authorization');

        return str_starts_with($header, 'Apikey ') && hash_equals($apiKey, substr($header, strlen('Apikey ')));
    }

    private function rejected(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
    }
}
