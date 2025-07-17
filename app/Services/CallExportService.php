<?php

namespace App\Services;

use App\Models\Call;
use Illuminate\Support\Collection;
use League\Csv\Writer;
use SplTempFileObject;

class CallExportService
{
    /**
     * Export a single call to CSV format
     */
    public function exportSingleCall(Call $call): string
    {
        // Ensure company context is set for tenant scope
        if ($call->company_id) {
            app()->instance('current_company_id', $call->company_id);
        }
        
        $csv = Writer::createFromString();
        
        // Don't add BOM - it causes issues with some email clients
        // Modern systems handle UTF-8 without BOM better
        // $csv->setOutputBOM(Writer::BOM_UTF8);
        
        // Define headers
        $headers = $this->getHeaders();
        $csv->insertOne($headers);
        
        // Add call data
        $csv->insertOne($this->formatCallData($call));
        
        return $csv->toString();
    }
    
    /**
     * Export multiple calls to CSV format
     */
    public function exportMultipleCalls(Collection $calls, array $columns = []): string
    {
        $csv = Writer::createFromString();
        
        // Don't add BOM - it causes issues with some email clients
        // Modern systems handle UTF-8 without BOM better
        // $csv->setOutputBOM(Writer::BOM_UTF8);
        
        // Use custom columns if provided, otherwise use default
        $headers = empty($columns) ? $this->getHeaders() : $columns;
        $csv->insertOne($headers);
        
        // Add call data
        foreach ($calls as $call) {
            $csv->insertOne($this->formatCallData($call, $columns));
        }
        
        return $csv->toString();
    }
    
    /**
     * Export calls with custom filters and format
     */
    public function exportWithFilters(array $filters = []): string
    {
        $query = Call::query();
        
        // Apply filters
        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }
        
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['has_appointment'])) {
            if ($filters['has_appointment']) {
                $query->whereNotNull('appointment_id');
            } else {
                $query->whereNull('appointment_id');
            }
        }
        
        // Load relationships
        $query->with(['customer', 'branch', 'appointment']);
        
        // Get calls
        $calls = $query->get();
        
        return $this->exportMultipleCalls($calls, $filters['columns'] ?? []);
    }
    
    /**
     * Get default CSV headers
     */
    protected function getHeaders(): array
    {
        return [
            'ID',
            'Datum',
            'Uhrzeit',
            'Dauer (Sekunden)',
            'Dauer (Formatiert)',
            'Telefonnummer',
            'Kundenname',
            'Kunden-Email',
            'Filiale',
            'Status',
            'Dringlichkeit',
            'Zusammenfassung',
            'Termin gebucht',
            'Termin-ID',
            'Transkript',
            'Erfasste Daten',
            'Agent Name',
            'Anrufkosten',
            'Erstellt am',
            'Aktualisiert am'
        ];
    }
    
    /**
     * Format call data for CSV export
     */
    protected function formatCallData(Call $call, array $columns = []): array
    {
        // Load charge relationship if not already loaded
        if (!$call->relationLoaded('charge')) {
            $call->load('charge');
        }
        
        $allData = [
            'ID' => $call->id,
            'Datum' => $call->created_at->format('d.m.Y'),
            'Uhrzeit' => $call->created_at->format('H:i:s'),
            'Dauer (Sekunden)' => $call->duration_sec ?? 0,
            'Dauer (Formatiert)' => $this->formatDuration($call->duration_sec),
            'Telefonnummer' => $call->phone_number ?? '',
            'Kundenname' => $call->customer?->name ?? '',
            'Kunden-Email' => $call->customer?->email ?? '',
            'Filiale' => $this->getBranchName($call),
            'Status' => $this->translateStatus($call->status),
            'Dringlichkeit' => $this->translateUrgency($call->urgency_level),
            'Zusammenfassung' => $call->summary ?? '',
            'Termin gebucht' => $call->appointment_id ? 'Ja' : 'Nein',
            'Termin-ID' => $call->appointment_id ?? '',
            'Transkript' => $call->transcript ?? '',
            'Erfasste Daten' => $this->formatDynamicVariables($call->dynamic_variables),
            'Agent Name' => $call->agent_name ?? '',
            'Anrufkosten' => $this->formatCustomerCost($call),
            'Erstellt am' => $call->created_at->format('d.m.Y H:i:s'),
            'Aktualisiert am' => $call->updated_at->format('d.m.Y H:i:s')
        ];
        
        // If specific columns are requested, filter the data
        if (!empty($columns)) {
            $filteredData = [];
            foreach ($columns as $column) {
                $filteredData[] = $allData[$column] ?? '';
            }
            return $filteredData;
        }
        
        return array_values($allData);
    }
    
    /**
     * Format duration from seconds to MM:SS
     */
    protected function formatDuration(?int $seconds): string
    {
        if (!$seconds) {
            return '00:00';
        }
        
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $secs);
    }
    
    /**
     * Translate status to German
     */
    protected function translateStatus(?string $status): string
    {
        $translations = [
            'ended' => 'Beendet',
            'active' => 'Aktiv',
            'processing' => 'Verarbeitung',
            'error' => 'Fehler',
            'pending' => 'Ausstehend'
        ];
        
        return $translations[$status] ?? $status ?? 'Unbekannt';
    }
    
    /**
     * Translate urgency level to German
     */
    protected function translateUrgency(?string $urgency): string
    {
        $translations = [
            'urgent' => 'Dringend',
            'high' => 'Hoch',
            'normal' => 'Normal',
            'low' => 'Niedrig'
        ];
        
        return $translations[$urgency] ?? $urgency ?? 'Normal';
    }
    
    /**
     * Format dynamic variables for CSV
     */
    protected function formatDynamicVariables($variables): string
    {
        if (empty($variables)) {
            return '';
        }
        
        $formatted = [];
        foreach ($variables as $key => $value) {
            // Skip technical fields
            if (in_array($key, ['caller_id', 'to_number', 'from_number', 'direction', 'twilio_call_sid'])) {
                continue;
            }
            
            $formatted[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . (is_array($value) ? json_encode($value) : $value);
        }
        
        return implode('; ', $formatted);
    }
    
    /**
     * Format cost with currency
     */
    protected function formatCost($cost): string
    {
        if (!$cost) {
            return '0,00 €';
        }
        
        return number_format($cost, 2, ',', '.') . ' €';
    }
    
    /**
     * Format customer cost (what we charge the customer)
     */
    protected function formatCustomerCost(Call $call): string
    {
        // If there's a charge record, use the customer cost
        if ($call->charge && $call->charge->amount_charged) {
            return number_format($call->charge->amount_charged, 2, ',', '.') . ' €';
        }
        
        // Otherwise return zero
        return '0,00 €';
    }
    
    /**
     * Get available export columns
     */
    public function getAvailableColumns(): array
    {
        return [
            'ID' => 'Eindeutige Anruf-ID',
            'Datum' => 'Datum des Anrufs',
            'Uhrzeit' => 'Uhrzeit des Anrufs',
            'Dauer (Sekunden)' => 'Anrufdauer in Sekunden',
            'Dauer (Formatiert)' => 'Anrufdauer im Format MM:SS',
            'Telefonnummer' => 'Telefonnummer des Anrufers',
            'Kundenname' => 'Name des Kunden',
            'Kunden-Email' => 'E-Mail-Adresse des Kunden',
            'Filiale' => 'Name der Filiale',
            'Status' => 'Status des Anrufs',
            'Dringlichkeit' => 'Dringlichkeitsstufe',
            'Zusammenfassung' => 'KI-generierte Zusammenfassung',
            'Termin gebucht' => 'Wurde ein Termin gebucht?',
            'Termin-ID' => 'ID des gebuchten Termins',
            'Transkript' => 'Vollständiges Gesprächstranskript',
            'Erfasste Daten' => 'Vom AI-Agent erfasste Daten',
            'Agent Name' => 'Name des AI-Agents',
            'Anrufkosten' => 'Kosten des Anrufs',
            'Erstellt am' => 'Erstellungszeitpunkt',
            'Aktualisiert am' => 'Letztes Update'
        ];
    }
    
    /**
     * Generate a filename for export
     */
    public function generateFilename(string $prefix = 'anrufe_export'): string
    {
        return $prefix . '_' . now()->format('Y-m-d_His') . '.csv';
    }
    
    /**
     * Get branch name safely without triggering tenant scope
     */
    protected function getBranchName(Call $call): string
    {
        if (!$call->branch_id) {
            return '';
        }
        
        try {
            // If branch is already loaded, use it
            if ($call->relationLoaded('branch') && $call->branch) {
                return $call->branch->name;
            }
            
            // Otherwise, query without tenant scope
            $branch = \App\Models\Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->find($call->branch_id);
                
            return $branch ? $branch->name : '';
        } catch (\Exception $e) {
            return '';
        }
    }
}