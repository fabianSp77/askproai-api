<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\Company;
use App\Services\MCP\RetellAIBridgeMCPServer;
use Illuminate\Console\Command;

class RetellMCPTestCallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retell:test-call
                            {phone : The phone number to call}
                            {--company= : Company ID (defaults to first company)}
                            {--agent= : Agent ID (defaults to company\'s default agent)}
                            {--scenario=greeting : Test scenario (greeting, appointment, custom)}
                            {--message= : Custom message for the call}
                            {--monitor : Monitor the call status in real-time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a test call using Retell AI MCP integration';

    /**
     * Execute the console command.
     */
    public function handle(RetellAIBridgeMCPServer $bridgeServer): int
    {
        $phoneNumber = $this->argument('phone');

        // Get company
        $companyId = $this->option('company');
        if ($companyId) {
            $company = Company::find($companyId);
        } else {
            $company = Company::first();
        }

        if (! $company) {
            $this->error('No company found. Please specify --company=ID');

            return self::FAILURE;
        }

        // Get agent ID
        $agentId = $this->option('agent') ?? $company->retell_agent_id;
        if (! $agentId) {
            $this->error('No agent ID found. Please specify --agent=ID or configure default agent for company.');

            return self::FAILURE;
        }

        $this->info('🚀 Initiating test call...');
        $this->newLine();

        $this->table(
            ['Parameter', 'Value'],
            [
                ['Company', $company->name . ' (ID: ' . $company->id . ')'],
                ['Phone Number', $phoneNumber],
                ['Agent ID', $agentId],
                ['Scenario', $this->option('scenario')],
            ]
        );

        try {
            // Build test parameters
            $params = [
                'company_id' => $company->id,
                'to_number' => $phoneNumber,
                'agent_id' => $agentId,
                'purpose' => 'test_call',
                'dynamic_variables' => $this->buildTestVariables(),
            ];

            // Make the call
            $result = $bridgeServer->createOutboundCall($params);

            $this->newLine();
            $this->info('✅ Call initiated successfully!');
            $this->line('Call ID: ' . $result['call_id']);
            $this->line('Retell Call ID: ' . $result['retell_call_id']);

            if ($this->option('monitor')) {
                $this->monitorCall($result['call_id']);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Failed to initiate call');
            $this->error('Error: ' . $e->getMessage());

            if ($this->output->isVerbose()) {
                $this->error('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Build test variables based on scenario.
     */
    protected function buildTestVariables(): array
    {
        $scenario = $this->option('scenario');
        $variables = [
            'test_mode' => true,
            'test_scenario' => $scenario,
            'test_timestamp' => now()->toISOString(),
        ];

        switch ($scenario) {
            case 'greeting':
                $variables['expected_response'] = 'greeting_with_company_name';

                break;
            case 'appointment':
                $variables['test_service'] = 'Haircut';
                $variables['test_date'] = 'tomorrow';
                $variables['test_time'] = '2 PM';
                $variables['expected_response'] = 'appointment_confirmation';

                break;
            case 'custom':
                if ($this->option('message')) {
                    $variables['custom_message'] = $this->option('message');
                }

                break;
        }

        return $variables;
    }

    /**
     * Monitor call status in real-time.
     */
    protected function monitorCall(string $callId): void
    {
        $this->newLine();
        $this->info('📞 Monitoring call status...');
        $this->line('Press Ctrl+C to stop monitoring');
        $this->newLine();

        $lastStatus = null;
        $startTime = now();

        while (true) {
            $call = Call::find($callId);

            if (! $call) {
                $this->error('Call record not found!');

                break;
            }

            // Update status display
            if ($call->status !== $lastStatus) {
                $lastStatus = $call->status;
                $duration = $startTime->diffInSeconds(now());

                $this->line(sprintf(
                    '[%s] Status: %s (Duration: %ds)',
                    now()->format('H:i:s'),
                    strtoupper($call->status),
                    $duration
                ));

                // Show additional info based on status
                switch ($call->status) {
                    case 'in-progress':
                        $this->info('   📞 Call is active...');

                        break;
                    case 'completed':
                        $this->info('   ✅ Call completed successfully!');
                        $this->line('   Duration: ' . $call->duration_sec . ' seconds');

                        if ($call->transcript) {
                            $this->newLine();
                            $this->info('📝 Transcript:');
                            $this->line(wordwrap($call->transcript, 80));
                        }

                        if ($call->recording_url) {
                            $this->newLine();
                            $this->info('🎙️ Recording URL:');
                            $this->line($call->recording_url);
                        }

                        return;
                    case 'failed':
                    case 'no-answer':
                        $this->error('   ❌ Call ended: ' . $call->status);

                        return;
                }
            }

            // Check for timeout
            if ($startTime->diffInMinutes(now()) > 10) {
                $this->warn('⏱️ Monitoring timeout after 10 minutes');

                break;
            }

            sleep(2); // Check every 2 seconds
        }
    }
}
