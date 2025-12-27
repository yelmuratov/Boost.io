<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use App\Exceptions\ApiException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Handle Model Not Found (findOrFail)
                if ($e instanceof ModelNotFoundException) {
                    $modelName = class_basename($e->getModel());
                    return response()->json([
                        'success' => false,
                        'message' => "{$modelName} not found",
                    ], 404);
                }

                // Handle Validation Errors
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $e->errors(),
                    ], 422);
                }

                // Handle Authentication Errors
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated',
                    ], 401);
                }

                // Handle Custom API Exceptions
                if ($e instanceof ApiException) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ], $e->getStatusCode());
                }

                // Handle all other exceptions in production
                if (!config('app.debug')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'An unexpected error occurred',
                    ], 500);
                }
            }
        });
    })->create();

