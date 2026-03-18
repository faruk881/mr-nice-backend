<?php

use App\Http\Middleware\CheckCourierDocument;
use App\Http\Middleware\CheckUserType;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\StatusMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'status' => StatusMiddleware::class,
            'courier.status' => CheckCourierDocument::class,
        ]);
    })
    // ->withExceptions(function (Exceptions $exceptions): void {
    //     //
    // })->create();

    ->withExceptions(function (Exceptions $exceptions): void {

    $exceptions->render(function (Throwable $e, Request $request) {

        // if ($request->expectsJson()) {

            $status = 500;
            $message = 'Backend server error.';
            $code = "BACKEND_SERVER_ERROR";
            $errors = [];

            // Validation error
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $status = 422;
                $message = 'Something went wrong.';
                $code = 'VALIDATION_ERROR';
                $errors = $e->errors();
            }

            // Model not found
            elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $status = 404;
                $message = 'Resource not found.';
                $code = 'RESOURCE_NOT_FOUND';

            }

            // Authentication
            elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                $status = 401;
                $message = 'Unauthenticated.';
                $code = 'UNAUTHENTICATED';
            }

            // Debug mode
            if (config('app.debug')) {
                $message = $e->getMessage();
                $errors = [
                    'trace' => $e->getTraceAsString(),
                ];
            }

            return response()->json([
                'status'  => 'error',
                'message' => $message,
                'code'    => $code,
                'errors'  => $errors,
            ], $status);
        // }
    });

})
->create();
