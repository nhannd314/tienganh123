<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a request through only for a member (or an admin): a guest is sent to sign in, anyone else to buy the membership.
 */
class EnsureMember
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $request->isMethodSafe() ? redirect()->guest(route('login')) : abort(401);
        }

        if (! $user->hasMemberAccess()) {
            return redirect()->route('membership.enroll');
        }

        return $next($request);
    }
}
