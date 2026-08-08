<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {

        if (!auth()->check()) {
            abort(403);
        }

        // Bandingkan dalam huruf kecil agar konsisten dengan pengecekan role
        // di controller lain yang memakai strtolower()
        $userRole = strtolower(auth()->user()->role ?? '');
        $roles = array_map('strtolower', $roles);

        if (!in_array($userRole, $roles)) {
            abort(403, 'Akses ditolak');
        }

        return $next($request);
    }
}