<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CalcomWebhookController;
use App\Http\Requests\CalcomWebhookRequest;
use Illuminate\Http\Request;

class ProcessCalcomWebhookFromFile extends Command
{
    protected $signature = 'calcom:process-webhook {payload}';
    protected $description = 'Process a Cal.com webhook from JSON payload';

    public function handle()
    {
        $payload = $this->argument('payload');
        $data = json_decode($payload, true);

        if (!$data) {
            $this->error('Invalid JSON payload');
            return 1;
        }

        $this->info('Processing webhook: ' . ($data['triggerEvent'] ?? 'unknown'));

        // Create a mock request
        $request = Request::create('/api/calcom/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');

        // Get controller
        $controller = app(CalcomWebhookController::class);

        try {
            // Create CalcomWebhookRequest from the mock request
            $calcomRequest = CalcomWebhookRequest::createFrom($request);

            // Set the request data manually to bypass validation
            $calcomRequest->merge($data);

            // Process through controller
            $response = $controller->handle($calcomRequest);

            $this->info('Response: ' . $response->getContent());
            return 0;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
