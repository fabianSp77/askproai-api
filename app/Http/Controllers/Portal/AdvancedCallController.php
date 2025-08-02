<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdvancedCallController extends Controller
{
    /**
     * Display the advanced React calls view with routing fix
     */
    public function index(Request $request)
    {
        return view('portal.calls.advanced-react');
    }
    
    /**
     * Display the advanced call detail view
     */
    public function show($id)
    {
        return view('portal.calls.advanced-show', compact('id'));
    }
}