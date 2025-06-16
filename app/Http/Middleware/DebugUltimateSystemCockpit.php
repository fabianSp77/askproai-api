<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class DebugUltimateSystemCockpit
{
    public function handle(Request $request, Closure $next): Response
    {
        $isTargetRoute = str_contains($request->path(), 'ultimate-system-cockpit');
        
        if ($isTargetRoute) {
            $debugLog = storage_path('logs/ultimate-cockpit-requests.log');
            $logEntry = sprintf(
                "[%s] Request to %s\n" .
                "Method: %s\n" .
                "User: %s\n" .
                "IP: %s\n" .
                "Headers: %s\n\n",
                now()->toDateTimeString(),
                $request->fullUrl(),
                $request->method(),
                auth()->check() ? auth()->user()->email : 'not-authenticated',
                $request->ip(),
                json_encode($request->headers->all(), JSON_PRETTY_PRINT)
            );
            file_put_contents($debugLog, $logEntry, FILE_APPEND | LOCK_EX);
        }
        
        try {
            $response = $next($request);
            
            if ($isTargetRoute) {
                $debugLog = storage_path('logs/ultimate-cockpit-requests.log');
                $statusCode = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 'unknown';
                
                $logEntry = sprintf(
                    "[%s] Response: %s\n",
                    now()->toDateTimeString(),
                    $statusCode
                );
                
                if ($statusCode >= 500) {
                    $content = method_exists($response, 'getContent') ? $response->getContent() : '';
                    if (strlen($content) > 2000) {
                        $content = substr($content, 0, 2000) . '... (truncated)';
                    }
                    $logEntry .= "Content: " . $content . "\n";
                    
                    // Try to get exception
                    if (property_exists($response, 'exception') && $response->exception) {
                        $logEntry .= sprintf(
                            "Exception: %s\nFile: %s:%d\n",
                            $response->exception->getMessage(),
                            $response->exception->getFile(),
                            $response->exception->getLine()
                        );
                    }
                }
                
                $logEntry .= "\n";
                file_put_contents($debugLog, $logEntry, FILE_APPEND | LOCK_EX);
            }
            
            return $response;
            
        } catch (\Throwable $e) {
            if ($isTargetRoute) {
                $debugLog = storage_path('logs/ultimate-cockpit-requests.log');
                $logEntry = sprintf(
                    "[%s] EXCEPTION in middleware:\n%s\nFile: %s:%d\nTrace:\n%s\n\n",
                    now()->toDateTimeString(),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                );
                file_put_contents($debugLog, $logEntry, FILE_APPEND | LOCK_EX);
            }
            
            throw $e;
        }
    }
}