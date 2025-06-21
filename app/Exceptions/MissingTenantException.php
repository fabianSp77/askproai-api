<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MissingTenantException extends Exception
{
    protected $message = 'No tenant context found. Company ID is required for this operation.';
    
    public function __construct(string $message = null)
    {
        parent::__construct($message ?? $this->message);
    }
    
    /**
     * Report the exception
     */
    public function report(): void
    {
        \Log::critical('Missing tenant context', [
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'stack_trace' => $this->getTraceAsString(),
        ]);
    }
    
    /**
     * Render the exception into an HTTP response
     */
    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Missing Tenant Context',
                'message' => $this->getMessage(),
                'code' => 'TENANT_REQUIRED',
            ], 403);
        }
        
        // For Livewire requests, throw the exception to be handled by Livewire
        if ($request->header('X-Livewire')) {
            throw new \Exception('Bitte wählen Sie ein Unternehmen aus.');
        }
        
        // For web requests, redirect to dashboard or login
        if (auth()->check()) {
            session()->flash('error', 'Bitte wählen Sie ein Unternehmen aus.');
            return redirect('/admin');
        }
        
        return redirect('/admin/login');
    }
}