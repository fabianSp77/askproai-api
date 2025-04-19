<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SamediController extends Controller
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $accessToken;

    public function __construct()
    {
        $this->baseUrl = env('SAMEDI_BASE_URL', 'https://api.samedi.de/api/v1');
        $this->clientId = env('SAMEDI_CLIENT_ID', '');
        $this->clientSecret = env('SAMEDI_CLIENT_SECRET', '');
        
        // Versuche, einen Token aus dem Cache zu bekommen oder einen neuen zu generieren
        $this->accessToken = $this->getAccessToken();
    }

    protected function getAccessToken()
    {
        // Verwende statt Cache-Facade direktes Speichern in einer Datei
        $cacheFile = storage_path('app/samedi_token.json');
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if (isset($data['expires_at']) && $data['expires_at'] > time()) {
                return $data['token'];
            }
        }

        // Token anfordern mit OAuth2 Client Credentials Grant
        try {
            $response = Http::post('https://api.samedi.de/oauth2/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;
                
                if ($token) {
                    $expiresIn = $data['expires_in'] ?? 3600; // Standard: 1 Stunde

                    // Token in Datei speichern
                    $cacheData = [
                        'token' => $token,
                        'expires_at' => time() + $expiresIn - 60, // 60 Sekunden Puffer
                    ];
                    
                    file_put_contents($cacheFile, json_encode($cacheData));
                    
                    return $token;
                }
            }
            
            Log::error('Samedi Token-Antwort: ' . json_encode($response->json()));
        } catch (\Exception $e) {
            Log::error('Fehler beim Abrufen des Samedi Access Tokens: ' . $e->getMessage());
        }

        return null;
    }

    public function test()
    {
        // Prüfe, ob die Zugangsdaten konfiguriert sind
        if (empty($this->clientId) || empty($this->clientSecret)) {
            return response()->json([
                'status' => 'warning',
                'message' => 'Samedi API-Zugangsdaten sind nicht vollständig konfiguriert.',
                'config' => [
                    'base_url' => $this->baseUrl,
                    'client_id_configured' => !empty($this->clientId),
                    'client_secret_configured' => !empty($this->clientSecret)
                ]
            ]);
        }

        // Prüfe, ob ein Token generiert werden konnte
        if (empty($this->accessToken)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Konnte keinen gültigen Access Token von Samedi erhalten.',
                'hint' => 'Bitte überprüfe die Client ID und das Client Secret.'
            ], 400);
        }

        // Token ist vorhanden, API ist erfolgreich konfiguriert
        return response()->json([
            'status' => 'ok',
            'message' => 'Samedi API ist konfiguriert und Authentifizierung erfolgreich.',
            'config' => [
                'base_url' => $this->baseUrl,
                'authenticated' => true
            ]
        ]);
    }

    public function listBookableTimes(Request $request)
    {
        try {
            if (empty($this->accessToken)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Konnte keinen gültigen Access Token von Samedi erhalten.'
                ], 400);
            }

            $response = Http::withToken($this->accessToken)
                ->get($this->baseUrl . '/bookable-times', [
                    'from_date' => $request->input('from_date', date('Y-m-d')),
                    'to_date' => $request->input('to_date', date('Y-m-d', strtotime('+7 days')))
                ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fehler bei der Verbindung zu Samedi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLocations()
    {
        try {
            if (empty($this->accessToken)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Konnte keinen gültigen Access Token von Samedi erhalten.'
                ], 400);
            }

            $response = Http::withToken($this->accessToken)
                ->get($this->baseUrl . '/locations');

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fehler bei der Verbindung zu Samedi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getServices()
    {
        try {
            if (empty($this->accessToken)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Konnte keinen gültigen Access Token von Samedi erhalten.'
                ], 400);
            }

            $response = Http::withToken($this->accessToken)
                ->get($this->baseUrl . '/services');

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fehler bei der Verbindung zu Samedi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createAppointment(Request $request)
    {
        try {
            if (empty($this->accessToken)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Konnte keinen gültigen Access Token von Samedi erhalten.'
                ], 400);
            }

            // Validiere die Anfrage
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'service_id' => 'required|string',
                'location_id' => 'required|string',
                'start_time' => 'required|date_format:Y-m-d H:i:s',
                'end_time' => 'required|date_format:Y-m-d H:i:s',
                'patient' => 'required|array',
                'patient.first_name' => 'required|string',
                'patient.last_name' => 'required|string',
                'patient.email' => 'required|email',
                'patient.phone' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validierungsfehler',
                    'errors' => $validator->errors()
                ], 422);
            }

            $response = Http::withToken($this->accessToken)
                ->post($this->baseUrl . '/appointments', $request->all());

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fehler bei der Verbindung zu Samedi: ' . $e->getMessage()
            ], 500);
        }
    }
}
