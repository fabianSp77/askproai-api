<?php

namespace App\Console\Commands;

use App\Models\GdprRequest;
use App\Services\GdprService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessGdprRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gdpr:process-requests {--type=} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending GDPR requests';

    protected GdprService $gdprService;

    public function __construct(GdprService $gdprService)
    {
        parent::__construct();
        $this->gdprService = $gdprService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $id = $this->option('id');

        if ($id) {
            $this->processSingleRequest($id);
            return;
        }

        $query = GdprRequest::where('status', 'pending');
        
        if ($type) {
            $query->where('type', $type);
        }

        $requests = $query->get();

        if ($requests->isEmpty()) {
            $this->info('No pending GDPR requests found.');
            return;
        }

        $this->info("Found {$requests->count()} pending GDPR requests.");

        foreach ($requests as $request) {
            $this->processRequest($request);
        }

        $this->info('GDPR request processing completed.');
    }

    protected function processSingleRequest($id)
    {
        $request = GdprRequest::find($id);
        
        if (!$request) {
            $this->error("GDPR request with ID {$id} not found.");
            return;
        }

        $this->processRequest($request);
    }

    protected function processRequest(GdprRequest $request)
    {
        $this->info("Processing {$request->type} request #{$request->id} for customer #{$request->customer_id}");

        try {
            $request->markAsProcessing();

            switch ($request->type) {
                case 'export':
                    $this->processExportRequest($request);
                    break;
                    
                case 'deletion':
                    $this->processDeletionRequest($request);
                    break;
                    
                case 'rectification':
                    $this->info("Rectification requests require manual processing.");
                    break;
                    
                case 'portability':
                    $this->processPortabilityRequest($request);
                    break;
            }
        } catch (\Exception $e) {
            $this->error("Error processing request: {$e->getMessage()}");
            Log::error('GDPR request processing failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function processExportRequest(GdprRequest $request)
    {
        $customer = $request->customer;
        
        if (!$customer) {
            $this->error("Customer not found for request #{$request->id}");
            return;
        }

        $exportPath = $this->gdprService->createExportFile($customer);
        
        $request->markAsCompleted([
            'export_file_path' => $exportPath,
        ]);

        $this->info("Export completed. File saved to: {$exportPath}");
        
        // TODO: Send notification email to customer
    }

    protected function processDeletionRequest(GdprRequest $request)
    {
        // Deletion requests should be reviewed manually
        $this->warn("Deletion request #{$request->id} requires manual review and approval.");
        $this->warn("Customer: {$request->customer->name} (#{$request->customer_id})");
        $this->warn("Reason: {$request->reason}");
        
        if ($this->confirm('Do you want to proceed with the deletion?')) {
            $customer = $request->customer;
            
            // Ask whether to anonymize or hard delete
            $anonymize = $this->choice(
                'How should the data be deleted?',
                ['anonymize' => 'Anonymize (recommended)', 'delete' => 'Hard delete'],
                'anonymize'
            ) === 'anonymize';

            $this->gdprService->deleteCustomerData($customer, $anonymize);
            
            $request->markAsCompleted([
                'admin_notes' => "Data " . ($anonymize ? 'anonymized' : 'deleted') . " by admin command",
            ]);

            $this->info("Customer data has been " . ($anonymize ? 'anonymized' : 'deleted') . ".");
        } else {
            $this->info("Deletion cancelled.");
        }
    }

    protected function processPortabilityRequest(GdprRequest $request)
    {
        // Similar to export but in a standardized format
        $this->processExportRequest($request);
    }
}