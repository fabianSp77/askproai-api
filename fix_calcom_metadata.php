<?php
// Öffne die CalcomService.php und ändere die createBooking Methode
// Suche nach der Zeile mit metadata und stelle sicher, dass alle Werte strings sind

$file = file_get_contents('app/Services/CalcomService.php');

// Ersetze die createBooking Methode
$file = preg_replace(
    '/public function createBooking\(.*?\{.*?\}/s',
    'public function createBooking($eventTypeId, $startTime, $customerData, $metadata = [])
    {
        try {
            // Füge teamSlug zu metadata hinzu
            if (!isset($metadata[\'teamSlug\'])) {
                $metadata[\'teamSlug\'] = \'askproai\';
            }
            
            // Konvertiere alle metadata Werte zu strings
            foreach ($metadata as $key => $value) {
                $metadata[$key] = (string)$value;
            }

            $data = [
                \'eventTypeId\' => (int)$eventTypeId,
                \'start\' => $startTime,
                \'responses\' => [
                    \'name\' => $customerData[\'name\'] ?? \'Unbekannt\',
                    \'email\' => $customerData[\'email\'] ?? \'kunde@example.com\',
                    \'phone\' => $customerData[\'phone\'] ?? null
                ],
                \'timeZone\' => \'Europe/Berlin\',
                \'language\' => \'de\',
                \'metadata\' => $metadata
            ];

            Log::info(\'Cal.com Booking Request\', [\'data\' => $data]);

            $response = $this->makeRequest(\'POST\', \'/bookings\', $data);

            if (isset($response[\'id\'])) {
                Log::info(\'Cal.com Booking erfolgreich\', [
                    \'booking_id\' => $response[\'id\'],
                    \'uid\' => $response[\'uid\'] ?? null
                ]);
                return $response;
            }

            Log::error(\'Cal.com Booking Response ohne ID\', [\'response\' => $response]);
            return null;

        } catch (\Exception $e) {
            Log::error(\'Cal.com Booking Fehler\', [
                \'error\' => $e->getMessage(),
                \'trace\' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }',
    $file
);

file_put_contents('app/Services/CalcomService.php', $file);
echo "✅ CalcomService aktualisiert - metadata wird jetzt zu strings konvertiert\n";
