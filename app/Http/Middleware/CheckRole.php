<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$allowedRoles
    ): Response {
        $userRoles = $request->attributes->get('keycloak_roles', []);

        if (!is_array($userRoles) || empty($userRoles)) {
            return response()->json([
                'message' => 'Pengguna tidak memiliki role yang valid.',
            ], 403);
        }

        $hasAccess = !empty(
            array_intersect($allowedRoles, $userRoles)
        );

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak akses ke resource ini.',
            ], 403);
        }

        return $next($request);
    }
}