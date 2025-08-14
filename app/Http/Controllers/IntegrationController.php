<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function index()
    {
        return Integration::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'kunde_id' => 'required|exists:kunden,id',
            'system' => 'required|string',
            'zugangsdaten' => 'required|string',
        ]);

        return Integration::create($request->all());
    }

    public function show(Integration $integrationen)
    {
        return $integrationen;
    }

    public function update(Request $request, Integration $integrationen)
    {
        $request->validate([
            'system' => 'sometimes|required|string',
            'zugangsdaten' => 'sometimes|required|string',
        ]);

        $integrationen->update($request->all());

        return $integrationen;
    }

    public function destroy(Integration $integrationen)
    {
        $integrationen->delete();

        return response(null, 204);
    }
}
