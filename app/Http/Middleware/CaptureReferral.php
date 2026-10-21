<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers the referral code of a visitor who came in through a link like /?ref=CODE, so the affiliate is credited
 * when the visitor registers later.
 */
class CaptureReferral
{
    public const COOKIE = 'ref';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $code = $request->query(self::COOKIE);

        if ($request->isMethod('GET') && is_string($code) && preg_match('/^[A-Za-z0-9]{4,20}$/', $code) === 1) {
            $response->headers->setCookie(cookie(self::COOKIE, strtoupper($code), (int) config('affiliate.cookie_days') * 24 * 60));
        }

        return $response;
    }
}
