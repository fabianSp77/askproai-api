<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\BranchContextManager;

class ProcessBranchSwitch
{
    protected BranchContextManager $branchContext;

    public function __construct(BranchContextManager $branchContext)
    {
        $this->branchContext = $branchContext;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if branch parameter is present
        if ($request->has('branch')) {
            $branchParam = $request->get('branch');
            
            // 'all' means show all branches (null branch ID)
            $branchId = $branchParam === 'all' ? null : $branchParam;
            
            // Try to switch branch
            if ($this->branchContext->setCurrentBranch($branchId)) {
                session()->flash('success', $branchId === null 
                    ? 'Sie sehen jetzt alle Filialen' 
                    : 'Filiale erfolgreich gewechselt');
            } else {
                session()->flash('error', 'Sie haben keinen Zugriff auf diese Filiale');
            }
            
            // Redirect to same URL without branch parameter to avoid bookmark issues
            return redirect()->to($request->url());
        }

        return $next($request);
    }
}