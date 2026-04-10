<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuAccessOrGuest
{
    public function handle(Request $request, Closure $next, $menuCode): Response
    {
        // Guest boleh akses sesuai kebutuhan fitur publik.
        if (!auth()->check()) {
            return $next($request);
        }

        if (!auth()->user()->hasMenuAccess($menuCode)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak. Anda tidak memiliki izin untuk modul (' . $menuCode . ').',
                ], 403);
            }

            abort(403, 'Akses Ditolak. Anda tidak memiliki izin untuk modul (' . $menuCode . ').');
        }

        return $next($request);
    }
}
