<?php

namespace App\Exceptions;

use App\Services\ErrorMonitoringService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        QueryException::class => 'critical',
        AuthenticationException::class => 'warning',
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        HttpException::class,
        ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'api_key',
        'secret',
        'token',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Use ErrorMonitoringService for intelligent error tracking
            if (!$this->shouldntReport($e)) {
                app(ErrorMonitoringService::class)->trackError($e, [
                    'request' => request()->all(),
                    'session' => request()->hasSession() ? request()->session()->getId() : null,
                    'user' => auth()->user()?->id,
                ]);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Handle ModelNotFoundException in Filament admin panel
        // FIX: Prevents 500 errors when accessing non-existent or cross-company resources
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            if ($request->is('admin/*')) {
                // Return 403 Forbidden for cross-tenant/non-existent resources in admin panel
                abort(403, 'Sie haben keinen Zugriff auf diese Ressource oder sie existiert nicht.');
            }
        }

        // API responses with detailed error information
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderJsonResponse($e);
        }

        // Custom error pages for production
        if (!config('app.debug') && $e instanceof HttpException) {
            return $this->renderCustomErrorPage($e);
        }

        return parent::render($request, $e);
    }

    /**
     * Render JSON error response
     */
    private function renderJsonResponse(Throwable $e)
    {
        $status = $this->getStatusCode($e);
        $response = [
            'error' => true,
            'message' => $e->getMessage() ?: 'An error occurred',
            'code' => $e->getCode() ?: $status,
        ];

        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice($e->getTrace(), 0, 5)
            ];
        }

        // Add validation errors if applicable
        if ($e instanceof ValidationException) {
            $response['errors'] = $e->errors();
        }

        return response()->json($response, $status);
    }

    /**
     * Render custom error page
     */
    private function renderCustomErrorPage(HttpException $e)
    {
        $status = $e->getStatusCode();

        if (view()->exists("errors.{$status}")) {
            return response()->view("errors.{$status}", [
                'exception' => $e
            ], $status);
        }

        return response()->view('errors.default', [
            'exception' => $e,
            'status' => $status
        ], $status);
    }

    /**
     * Get HTTP status code from exception
     */
    private function getStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        if ($e instanceof AuthenticationException) {
            return 401;
        }

        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof QueryException) {
            return 503;
        }

        return 500;
    }
}