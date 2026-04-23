<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuAccessOrGuest
{
    /**
     * $menuCodes: satu atau lebih kode menu dipisah koma.
     * User diizinkan jika memiliki akses ke SALAH SATU dari kode tersebut.
     */
    public function handle(Request $request, Closure $next, string ...$menuCodes): Response
    {
        // Guest boleh akses sesuai kebutuhan fitur publik.
        if (!auth()->check()) {
            return $next($request);
        }

        foreach ($menuCodes as $menuCode) {
            if (auth()->user()->hasMenuAccess($menuCode)) {
                return $next($request);
            }
        }

        // Tidak ada menu yang cocok — tolak akses.
        $codesStr = implode(' atau ', $menuCodes);
        $isApiRequest = $request->expectsJson()
            || str_starts_with($request->path(), 'api/')
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if ($isApiRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak. Anda tidak memiliki izin untuk modul (' . $codesStr . ').',
            ], 403);
        }

        abort(403, 'Akses Ditolak. Anda tidak memiliki izin untuk modul (' . $codesStr . ').');
    }
}
