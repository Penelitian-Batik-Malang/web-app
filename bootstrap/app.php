<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'menu.access' => \App\Http\Middleware\CheckMenuAccess::class,
            'menu.access_or_guest' => \App\Http\Middleware\CheckMenuAccessOrGuest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            $uploadLimit = ini_get('upload_max_filesize') ?: 'unknown';
            $postLimit = ini_get('post_max_size') ?: 'unknown';
            $message = "Ukuran upload terlalu besar untuk server (upload_max_filesize={$uploadLimit}, post_max_size={$postLimit}).";

            if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 413);
            }

            return response($message, 413);
        });
    })->create();
