<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class TestMLController extends Controller
{
    public function index()
    {
        $stats = [
            'total_calls' => DB::table('calls')->count(),
            'with_transcript' => DB::table('calls')->whereNotNull('transcript')->count(),
            'analyzed' => DB::table('ml_call_predictions')->count(),
        ];
        
        return view('test-ml-dashboard', compact('stats'));
    }
}