<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalcomWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Webhook Secret aus der Umgebung holen
        $secret = config('services.calcom.webhook_secret');
        
        // Wenn es ein GET-Request ist (Ping-Test von Cal.com)
        if ($request->isMethod('get')) {
            if (!$secret) {
                return response()->json([
                    'ok' => false,
                    'status' => 500,
                    'message' => 'Cal.com secret missing'
                ], 500);
            }
            
            return response()->json([
                'ok' => true,
                'status' => 200,
                'message' => 'Webhook is ready'
            ]);
        }
        
        // POST-Request verarbeiten
        Log::info('Cal.com webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);
        
        // Hier können Sie später die Webhook-Events verarbeiten
        
        return response()->json(['status' => 'success']);
    }
}
