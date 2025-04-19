<?php // app/Http/Middleware/IdentifyTenant.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Throwable;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;
        $apiKey = null;
        $logContext = ['uri' => $request->getRequestUri()];

        try {
            // Log::channel('single')->debug('IdentifyTenant Middleware: Starting.', $logContext); // Optional: kann wieder aktiviert werden

            $apiKey = $request->header('X-Tenant-Api-Key');
            $logContext['api_key_present'] = !empty($apiKey);

            if ($apiKey) {
                // Log::channel('single')->debug('IdentifyTenant Middleware: Attempting to find Tenant by API Key.', $logContext);
                // Datenbank sollte jetzt durch RefreshDatabase migriert sein!
                $tenant = Tenant::where('api_key', $apiKey)
                                ->where('is_active', true)
                                ->first();

                if (!$tenant) {
                     $logContext['api_key_prefix'] = substr($apiKey, 0, 5).'...';
                     Log::channel('single')->warning('IdentifyTenant Middleware: Invalid or inactive API Key provided.', $logContext);
                     return response()->json(['error' => 'Unauthorized: Invalid API Key.'], 401);
                }
                $logContext['tenant_id'] = $tenant->id;
                Log::channel('single')->info('IdentifyTenant Middleware: Tenant identified.', $logContext);
            }

            if (!$tenant) {
                 Log::channel('single')->warning('IdentifyTenant Middleware: Tenant could not be identified (No API Key header?).', $logContext);
                 return response()->json(['error' => 'Unauthorized: Tenant could not be identified.'], 401);
            }

            // Log::channel('single')->debug('IdentifyTenant Middleware: Binding Tenant to container.', $logContext);
            App::singleton(Tenant::class, fn() => $tenant);
            // Log::channel('single')->debug('IdentifyTenant Middleware: Tenant bound. Proceeding.', $logContext);

            return $next($request);

        } catch (Throwable $e) {
            $logContext['error_message'] = $e->getMessage();
            $logContext['error_file'] = $e->getFile();
            $logContext['error_line'] = $e->getLine();
            $logContext['api_key_prefix'] = $apiKey ? substr($apiKey, 0, 5).'...' : 'N/A';
            Log::channel('single')->critical('IdentifyTenant Middleware: CRITICAL ERROR!', $logContext);
            return response()->json(['error' => 'Internal Server Error during tenant identification.'], 500);
        }
    }
}
