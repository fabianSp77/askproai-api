<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PremiumService;
use Illuminate\Http\Request;

class PremiumServiceController extends Controller
{
    public function index()
    {
        $services = PremiumService::all();

        return view('admin.premium-services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.premium-services.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        PremiumService::create($validated);

        return redirect()->route('admin.premium-services.index')->with('success', 'Service erstellt!');
    }

    public function edit(PremiumService $premiumService)
    {
        return view('admin.premium-services.edit', compact('premiumService'));
    }

    public function update(Request $request, PremiumService $premiumService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $premiumService->update($validated);

        return redirect()->route('admin.premium-services.index')->with('success', 'Service aktualisiert!');
    }

    public function destroy(PremiumService $premiumService)
    {
        $premiumService->delete();

        return redirect()->route('admin.premium-services.index')->with('success', 'Service gelöscht!');
    }
}
