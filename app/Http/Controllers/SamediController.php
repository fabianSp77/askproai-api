<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SamediController extends Controller
{
    public function testConnection()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('SAMEDI_API_KEY'),
                'Accept' => 'application/json',
            ])->get(env('SAMEDI_BASE_URL') . '/health');

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ]);
        } catch (\Exception $e) {
            Log::error('Samedi Verbindung fehlgeschlagen: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Samedi Verbindung fehlgeschlagen'
            ], 500);
        }
    }
}
