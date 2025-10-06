<?php

use Illuminate\Support\Facades\Route;
use App\Http\Requests\CollectAppointmentRequest;
use Illuminate\Http\JsonResponse;

/**
 * TEST ROUTES - NUR FÜR DEVELOPMENT
 *
 * Diese Routes sind NUR für lokale Tests und sollten in Production deaktiviert sein!
 */

if (config('app.env') !== 'production') {

    Route::post('/test/email-sanitization', function (CollectAppointmentRequest $request) {
        // Dieser Endpoint testet NUR die Request-Validation
        // prepareForValidation() wird automatisch VOR der Validation ausgeführt

        $args = $request->input('args', []);

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Validation passed - prepareForValidation() worked!',
            'original_email' => $request->input('_original_email', 'not provided'),
            'sanitized_email' => $args['email'] ?? null,
            'all_args' => $args
        ], 200);
    });
}
