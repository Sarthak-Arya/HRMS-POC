<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Services\Observability\DomainTelemetry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles  Role slugs from {@see UserRole}
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        app(DomainTelemetry::class)->securityDenied('access.role.denied', [
            'policy' => 'role:'.implode('|', $roles),
            'route.template' => '/'.ltrim((string) optional($request->route())->uri(), '/'),
        ]);

        abort(403, 'You do not have permission to access this page.');
    }
}
