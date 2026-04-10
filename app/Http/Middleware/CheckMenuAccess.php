<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuAccess
{
    public function handle(Request $request, Closure $next, $menuCode): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->hasMenuAccess($menuCode)) {
            abort(403, 'Akses Ditolak. Anda tidak memiliki izin untuk mengelola spesialisasi modul ('. $menuCode .').');
        }

        return $next($request);
    }
}
