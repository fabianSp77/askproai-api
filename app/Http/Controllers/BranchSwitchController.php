<?php

namespace App\Http\Controllers;

use App\Services\BranchContextManager;
use Illuminate\Http\Request;

class BranchSwitchController extends Controller
{
    protected BranchContextManager $branchContext;

    public function __construct(BranchContextManager $branchContext)
    {
        $this->branchContext = $branchContext;
    }

    public function switch(Request $request)
    {
        $branchId = $request->input('branch_id');
        
        // Empty string means "All Branches"
        $branchId = $branchId === '' ? null : $branchId;
        
        if ($this->branchContext->setCurrentBranch($branchId)) {
            session()->flash('success', $branchId === null 
                ? 'Sie sehen jetzt alle Filialen' 
                : 'Filiale erfolgreich gewechselt');
        } else {
            session()->flash('error', 'Sie haben keinen Zugriff auf diese Filiale');
        }
        
        return redirect()->back();
    }
}