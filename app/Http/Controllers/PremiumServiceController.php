<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PremiumService;

class PremiumServiceController extends Controller
{
    public function index()
    {
        $services = PremiumService::where('active', true)->get();
        return view('premium.index', compact('services'));
    }
    
    public function show($id)
    {
        $service = PremiumService::findOrFail($id);
        return view('premium.show', compact('service'));
    }
}
