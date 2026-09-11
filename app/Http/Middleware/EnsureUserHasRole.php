<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: `->middleware('role:chair,admin')`.
 *
 * Deliberately plain. Fine-grained rules (can this faculty member review this
 * particular portfolio?) belong in policies, not here.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole($roles)) {
            abort(403, 'Your account does not have access to this area.');
        }

        return $next($request);
    }
}
