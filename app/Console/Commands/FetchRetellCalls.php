<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RetellV2Service;
use App\Models\Company;
use App\Jobs\ProcessRetellCallEndedJob;

class FetchRetellCalls extends Command
{
    protected $signature = 'retell:fetch-calls {--company=} {--limit=100}';
    protected $description = 'Fetch all calls from Retell.ai API';

    public function handle()
    {
        $companyId = $this->option('company');
        $limit = $this->option('limit');
        
        $company = $companyId ? Company::find($companyId) : Company::first();
        
        if (!$company) {
            $this->error('Keine Company gefunden!');
            return 1;
        }
        
        $this->info("Verwende Company: {$company->name}");
        
        try {
            $retellService = new RetellV2Service($company);
            
            $this->info('Rufe Anrufe von Retell.ai ab...');
            
            // Direkte API-Anfrage an Retell
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.retell.api_key'),
            ])->post('https://api.retellai.com/v2/list-calls', [
                'limit' => (int)$limit,
                'sort_order' => 'descending'
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('API Error: ' . $response->body());
            }
            
            $calls = $response->json();
            
            if (isset($calls['results']) && is_array($calls['results'])) {
                $this->info('Gefundene Anrufe: ' . count($calls['results']));
                
                $bar = $this->output->createProgressBar(count($calls['results']));
                $bar->start();
                
                foreach ($calls['results'] as $callData) {
                    ProcessRetellCallEndedJob::dispatch([
                        'event' => 'call_ended',
                        'call' => $callData
                    ], $company);
                    
                    $bar->advance();
                }
                
                $bar->finish();
                $this->newLine();
                
                $this->info('Alle Anrufe wurden zur Verarbeitung eingereicht.');
                
                // Zeige kurze Übersicht
                $this->table(
                    ['Call ID', 'From', 'Duration', 'Status'],
                    collect($calls['results'])->map(function ($call) {
                        return [
                            $call['call_id'] ?? 'N/A',
                            $call['from_number'] ?? 'Unknown',
                            isset($call['end_timestamp']) && isset($call['start_timestamp']) 
                                ? round(($call['end_timestamp'] - $call['start_timestamp']) / 1000) . 's'
                                : 'N/A',
                            $call['call_status'] ?? 'unknown'
                        ];
                    })->take(10)->toArray()
                );
                
                if (count($calls['results']) > 10) {
                    $this->info('... und ' . (count($calls['results']) - 10) . ' weitere Anrufe');
                }
            } else {
                $this->warn('Keine Anrufe gefunden.');
            }
        } catch (\Exception $e) {
            $this->error('Fehler: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}