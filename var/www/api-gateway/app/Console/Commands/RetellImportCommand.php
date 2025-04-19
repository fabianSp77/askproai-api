<?php
namespace App\Console\Commands;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetellImportCommand extends Command
{
    protected $signature = 'retell:import {file? : Pfad zur CSV-Datei}';
    protected $description = 'Importiert Anrufdaten aus einer retell.ai CSV-Datei';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!$filePath) {
            $filePath = $this->ask('Bitte geben Sie den Pfad zur CSV-Datei an');
        }
        
        if (!file_exists($filePath)) {
            $this->error("Die Datei $filePath existiert nicht!");
            return 1;
        }
        
        $this->info("Importiere Daten aus $filePath...");
        
        // CSV-Datei lesen
        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file); // Header-Zeile lesen
        
        // Header-Indizes für einfacheren Zugriff
        $headerIndices = array_flip($headers);
        
        $importedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        // Datensätze durchlaufen
        while (($row = fgetcsv($file)) !== false) {
            try {
                // Prüfen, ob bereits importiert
                $callId = $row[$headerIndices['Call ID']] ?? null;
                
                if (!$callId) {
                    $this->warn("Zeile ohne Call ID übersprungen");
                    $skippedCount++;
                    continue;
                }
                
                if (Call::where('call_id', $callId)->exists()) {
                    $this->line("Call ID $callId bereits vorhanden, übersprungen");
                    $skippedCount++;
                    continue;
                }
                
                // Anrufdatum und -zeit parsen
                $timeString = $row[$headerIndices['Time']] ?? null;
                $callTime = $timeString ? Carbon::createFromFormat('m/d/Y H:i', $timeString) : null;
                
                if (!$callTime) {
                    $this->warn("Ungültiges Zeitformat für Call ID $callId: $timeString");
                    $skippedCount++;
                    continue;
                }
                
                // Anrufdauer in Sekunden umrechnen
                $durationString = $row[$headerIndices['Call Duration']] ?? null;
                $durationSeconds = 0;
                
                if ($durationString) {
                    $parts = explode(':', $durationString);
                    if (count($parts) == 2) {
                        // Format MM:SS
                        $durationSeconds = (int)$parts[0] * 60 + (int)$parts[1];
                    } elseif (count($parts) == 3) {
                        // Format HH:MM:SS
                        $durationSeconds = (int)$parts[0] * 3600 + (int)$parts[1] * 60 + (int)$parts[2];
                    }
                }
                
                // Kosten extrahieren (vom Format "$0.023")
                $costString = $row[$headerIndices['Cost']] ?? null;
                $cost = $costString ? (float)str_replace('$', '', $costString) : null;
                
                // Kundeninformationen
                $customerName = $row[$headerIndices['name']] ?? null;
                $customerPhone = $row[$headerIndices['telefonnummer']] ?? 
                               $row[$headerIndices['telefonnummer_anrufer']] ?? 
                               $row[$headerIndices['telefonnummer_anrufers']] ?? null;
                $customerEmail = $row[$headerIndices['email']] ?? null;
                
                // Kunden finden oder erstellen
                $customer = null;
                if ($customerPhone) {
                    $customer = Customer::firstOrCreate(
                        ['phone_number' => $customerPhone],
                        [
                            'name' => $customerName ?: 'Unbekannt',
                            'email' => $customerEmail,
                        ]
                    );
                } elseif ($customerName) {
                    $customer = Customer::firstOrCreate(
                        ['name' => $customerName],
                        ['email' => $customerEmail]
                    );
                }
                
                // Anruf speichern
                $call = new Call([
                    'call_id' => $callId,
                    'customer_id' => $customer ? $customer->id : null,
                    'call_time' => $callTime,
                    'call_duration' => $durationString,
                    'duration_seconds' => $durationSeconds,
                    'type' => $row[$headerIndices['Type']] ?? 'web_call',
                    'cost' => $cost,
                    'disconnection_reason' => $row[$headerIndices['Disconnection Reason']] ?? null,
                    'call_status' => $row[$headerIndices['Call Status']] ?? 'unknown',
                    'user_sentiment' => $row[$headerIndices['User Sentiment']] ?? null,
                    'from_number' => $row[$headerIndices['From']] ?? null,
                    'to_number' => $row[$headerIndices['To']] ?? null,
                    'successful' => $row[$headerIndices['Call Successful']] === 'Successful',
                    'latency' => $row[$headerIndices['End to End Latency']] ?? null,
                    'call_summary' => $row[$headerIndices['zusammenfassung']] ?? 
                                    $row[$headerIndices['zusammenfassung_telefonat']] ?? 
                                    $row[$headerIndices['zusammenfassung_anruf']] ?? null,
                ]);
                
                $call->save();
                
                $importedCount++;
                
                if ($importedCount % 10 === 0) {
                    $this->info("$importedCount Anrufe importiert...");
                }
                
            } catch (\Exception $e) {
                $this->error("Fehler beim Import von Call ID $callId: " . $e->getMessage());
                Log::error("Retell Import Error: " . $e->getMessage(), ['call_id' => $callId ?? 'unknown']);
                $errorCount++;
            }
        }
        
        fclose($file);
        
        $this->info("Import abgeschlossen.");
        $this->info("$importedCount Anrufe importiert");
        $this->info("$skippedCount Anrufe übersprungen");
        $this->info("$errorCount Fehler aufgetreten");
        
        return 0;
    }
}
