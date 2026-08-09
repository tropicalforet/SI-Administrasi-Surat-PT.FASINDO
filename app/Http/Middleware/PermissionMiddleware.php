<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     * Cek apakah user memiliki permission tertentu.
     * Role admin/dirut/sekretaris otomatis bypass (punya semua akses).
     */
    public function handle(
        Request $request,
        Closure $next,
        ...$permissions
    ): Response {

        if (!auth()->check()) {
            abort(403);
        }

        $user = auth()->user();

        // Role-role berikut otomatis bypass semua permission
        $bypassRoles = ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'];

        if (in_array(strtolower($user->role), $bypassRoles)) {
            return $next($request);
        }

        // Cek apakah user memiliki salah satu permission yang diminta
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
