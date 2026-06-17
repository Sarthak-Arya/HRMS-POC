<?php

namespace App\Http\Middleware;

use App\Services\Auth\RolePermissionSync;
use App\Services\Auth\UserRoleService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * @param  string  ...$permissions  Permission names; user needs any one of them.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        $user = UserRoleService::ensureDefaultRole($user);

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        $role = $user->roles()->first();

        if ($role) {
            RolePermissionSync::syncRole($role);
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $user->load('roles.permissions');

            foreach ($permissions as $permission) {
                if ($user->can($permission)) {
                    return $next($request);
                }
            }
        }

        abort(403, 'You do not have permission to access this feature.');
    }
}
