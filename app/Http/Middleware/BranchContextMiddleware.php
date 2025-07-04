<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\BranchContextManager;

class BranchContextMiddleware
{
    protected BranchContextManager $branchContext;
    
    public function __construct(BranchContextManager $branchContext)
    {
        $this->branchContext = $branchContext;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if branch parameter is in the query string
        if ($request->has('branch')) {
            $branchId = $request->get('branch');
            
            if ($branchId === 'all') {
                // Set to all branches view
                $this->branchContext->setCurrentBranch(null);
            } else {
                // Set specific branch
                $this->branchContext->setCurrentBranch($branchId);
            }
            
            // Get the current path ensuring it doesn't include any malformed segments
            $currentPath = $request->path();
            
            // If we're on a resource page with a record ID, preserve the full path
            // This handles cases like /admin/branches/1/edit correctly
            if (preg_match('/^admin\/[^\/]+\/[^\/]+/', $currentPath)) {
                // This is a resource detail/edit page, keep the full path
                $cleanUrl = url($currentPath);
            } else {
                // For other pages, just use the base path
                $cleanUrl = url($currentPath);
            }
            
            // Log for debugging
            \Log::info('BranchContextMiddleware redirect', [
                'original_path' => $currentPath,
                'clean_url' => $cleanUrl,
                'branch_id' => $branchId,
                'request_segments' => $request->segments(),
            ]);
            
            return redirect()->to($cleanUrl);
        }
        
        return $next($request);
    }
}