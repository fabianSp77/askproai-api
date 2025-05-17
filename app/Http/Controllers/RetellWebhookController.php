<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PhoneNumber;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\Staff;
use App\Models\CalcomEventType;
use App\Models\Customer;

class RetellWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // ... Hier ggf. Signaturprüfung wie gehabt

        $data = $request->json()->all();
        $intent = $data['payload']['intent'] ?? null;
        $slotsData = $data['payload']['slots'] ?? [];

        // 1. Telefonnummer aus Webhook-Payload extrahieren (je nach Struktur!)
        $incomingNumber = $slotsData['to_number'] ?? $slotsData['callee'] ?? null; // Passe ggf. an, je nach Payload!

        if ($intent === 'booking_create') {
            // a) PhoneNumber suchen
            $phoneNumber = PhoneNumber::where('number', $incomingNumber)->first();
            if (!$phoneNumber) {
                return response()->json(['message' => 'Telefonnummer ist keinem Branch zugeordnet!'], 400);
            }

            // b) Branch holen
            $branch = Branch::where('id', $phoneNumber->branch_id)->first();
            if (!$branch) {
                return response()->json(['message' => 'Branch/Filiale nicht gefunden!'], 400);
            }

            // c) Customer und Tenant holen (jetzt per Name-Matching)
            $customer = Customer::where('id', $branch->customer_id)->first();
            if (!$customer) {
                return response()->json(['message' => 'Customer/Kunde nicht gefunden!'], 400);
            }
            $tenant = Tenant::where('name', $customer->name)->first();
            if (!$tenant) {
                return response()->json(['message' => 'Mandant/Firma nicht gefunden!'], 400);
            }

            // d) Staff im Branch finden (optional: alle, oder nur 1)
            $staff = Staff::where('branch_id', $branch->id)->first();
            if (!$staff) {
                return response()->json(['message' => 'Kein Mitarbeiter für diese Filiale gefunden!'], 400);
            }

            // e) EventType zum Staff holen
            $eventType = CalcomEventType::where('staff_id', $staff->id)
                ->where('is_active', true)
                ->first();

            if (!$eventType) {
                return response()->json(['message' => 'Keine Dienstleistung für diesen Mitarbeiter gefunden!'], 400);
            }

            // f) Buchungsdaten zusammenbauen
            $bookingDetails = [
                'eventTypeId' => $eventType->calcom_numeric_event_type_id,
                'name'        => $slotsData['name'] ?? 'Unbekannt',
                'email'       => $slotsData['email'] ?? 'termin@askproai.de',
                'startTime'   => $slotsData['start'] ?? null,
                'endTime'     => $slotsData['end'] ?? null,
                'timeZone'    => $slotsData['timeZone'] ?? 'Europe/Berlin',
                'language'    => $slotsData['language'] ?? 'de',
            ];

            if (empty($bookingDetails['startTime']) || empty($bookingDetails['endTime'])) {
                return response()->json(['message' => 'Start- oder Endzeit fehlt.'], 400);
            }
            if (empty($bookingDetails['name']) || empty($bookingDetails['email'])) {
                return response()->json(['message' => 'Name oder E-Mail fehlt.'], 400);
            }

            // g) Buchung bei Cal.com auslösen
            $calcomService = app(\App\Services\CalcomService::class);
            $bookingResponse = $calcomService->createBooking($bookingDetails, $tenant->calcom_team_slug);

            if ($bookingResponse->successful()) {
                return response()->json(['booking' => $bookingResponse->json()], 200);
            } else {
                return response()->json([
                    'message' => 'Fehler bei der Buchungserstellung über Cal.com.',
                    'calcom_status' => $bookingResponse->status(),
                    'calcom_error' => $bookingResponse->json() ?? $bookingResponse->body()
                ], 503);
            }
        }

        // ... alle anderen Intents, Logging etc.
        return response()->json(['message' => 'Intent nicht erkannt oder nicht unterstützt.'], 501);
    }
}
