<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::all();
        return response()->json([
            'branches' => $branches
        ]);
    }

    public function show($id)
    {
        $branch = Branch::find($id);
        return response()->json([
            'branch' => $branch
        ]);
    }

    public function staff($id)
    {
        $branch = Branch::with('staff')->find($id);
        return response()->json([
            'staff' => $branch ? $branch->staff : []
        ]);
    }

    public function services($id)
    {
        $branch = Branch::with('services')->find($id);
        return response()->json([
            'services' => $branch ? $branch->services : []
        ]);
    }
}