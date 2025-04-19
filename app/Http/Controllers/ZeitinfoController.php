<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ZeitinfoController extends Controller
{
    public function jetzt(Request $request)
    {
        $locale = strtolower($request->query('locale', 'de'));
        $supportedLocales = ['de', 'en'];

        if (!in_array($locale, $supportedLocales)) {
            Log::warning('Ungültiges Locale übergeben, fallback auf "de"', [
                'übergebenes_locale' => $locale,
                'client_ip' => $request->ip()
            ]);
            $locale = 'de';
        }

        try {
            $now = Carbon::now('Europe/Berlin')->locale($locale);
        } catch (\Exception $e) {
            Log::error('Fehler beim Setzen des Locale: ' . $e->getMessage(), [
                'client_ip' => $request->ip(),
                'gewünschtes_locale' => $locale
            ]);
            $now = Carbon::now('Europe/Berlin')->locale('de');
        }

        Log::info('Zeitinfo abgerufen', [
            'locale' => $locale,
            'client_ip' => $request->ip()
        ]);

        return response()->json([
            'date'    => $now->format('d.m.Y'),
            'time'    => $now->format('H:i'),
            'weekday' => $now->isoFormat('dddd')
        ]);
    }
}
