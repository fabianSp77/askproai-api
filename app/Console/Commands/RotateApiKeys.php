<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\RetellConfiguration;
use App\Services\Security\ApiKeyEncryptionService;
use App\Services\CalcomV2Service;
use App\Services\RetellService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class RotateApiKeys extends Command
{
    protected $signature = 'security:rotate-keys 
                            {--company= : Specific company ID to rotate keys for}
                            {--service= : Specific service to rotate (calcom, retell, all)}
                            {--encrypt-only : Only encrypt existing keys without rotation}
                            {--force : Force rotation without confirmation}';

    protected $description = 'Rotate and encrypt API keys for all services';

    private ApiKeyEncryptionService $encryptionService;
    private array $rotationLog = [];

    public function __construct(ApiKeyEncryptionService $encryptionService)
    {
        parent::__construct();
        $this->encryptionService = $encryptionService;
    }

    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('This will rotate API keys. Services may be temporarily disrupted. Continue?')) {
            return Command::FAILURE;
        }

        $this->info('Starting API key rotation...');
        
        DB::beginTransaction();
        
        try {
            // Phase 1: Encrypt existing keys
            $this->encryptExistingKeys();
            
            // Phase 2: Rotate keys if not encrypt-only
            if (!$this->option('encrypt-only')) {
                $this->rotateKeys();
            }
            
            // Phase 3: Update environment file
            $this->updateEnvironmentFile();
            
            DB::commit();
            
            // Log rotation
            $this->logRotation();
            
            $this->info('API key rotation completed successfully!');
            $this->displaySummary();
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error('API key rotation failed: ' . $e->getMessage());
            Log::error('API key rotation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }

    private function encryptExistingKeys()
    {
        $this->info('Phase 1: Encrypting existing keys...');
        
        $companyQuery = Company::query();
        if ($companyId = $this->option('company')) {
            $companyQuery->where('id', $companyId);
        }
        
        $companies = $companyQuery->get();
        $encrypted = 0;
        
        foreach ($companies as $company) {
            $updated = false;
            
            // Encrypt Cal.com API key
            if ($company->calcom_api_key && !$this->encryptionService->isEncrypted($company->calcom_api_key)) {
                $company->calcom_api_key = $this->encryptionService->encrypt($company->calcom_api_key);
                $updated = true;
                $this->rotationLog[] = [
                    'company_id' => $company->id,
                    'service' => 'calcom',
                    'action' => 'encrypted',
                    'timestamp' => now()
                ];
            }
            
            // Encrypt Retell.ai API key
            if ($company->retell_api_key && !$this->encryptionService->isEncrypted($company->retell_api_key)) {
                $company->retell_api_key = $this->encryptionService->encrypt($company->retell_api_key);
                $updated = true;
                $this->rotationLog[] = [
                    'company_id' => $company->id,
                    'service' => 'retell',
                    'action' => 'encrypted',
                    'timestamp' => now()
                ];
            }
            
            if ($updated) {
                $company->save();
                $encrypted++;
            }
        }
        
        // Encrypt Retell webhook secrets
        $retellConfigs = RetellConfiguration::all();
        foreach ($retellConfigs as $config) {
            if ($config->webhook_secret && !$this->encryptionService->isEncrypted($config->webhook_secret)) {
                $config->webhook_secret = $this->encryptionService->encrypt($config->webhook_secret);
                $config->save();
                $encrypted++;
                
                $this->rotationLog[] = [
                    'config_id' => $config->id,
                    'service' => 'retell_webhook',
                    'action' => 'encrypted',
                    'timestamp' => now()
                ];
            }
        }
        
        $this->info("Encrypted {$encrypted} API keys.");
    }

    private function rotateKeys()
    {
        $this->info('Phase 2: Rotating API keys...');
        
        $service = $this->option('service') ?? 'all';
        
        if ($service === 'all' || $service === 'calcom') {
            $this->rotateCalcomKeys();
        }
        
        if ($service === 'all' || $service === 'retell') {
            $this->rotateRetellKeys();
        }
    }

    private function rotateCalcomKeys()
    {
        $this->warn('Cal.com key rotation requires manual steps:');
        $this->info('1. Log in to Cal.com dashboard');
        $this->info('2. Navigate to Settings > API Keys');
        $this->info('3. Generate new API key');
        $this->info('4. Update the key in this system');
        
        if ($this->confirm('Have you generated a new Cal.com API key?')) {
            $newKey = $this->secret('Enter the new Cal.com API key');
            
            if ($newKey) {
                // Update environment
                $this->updateEnvValue('DEFAULT_CALCOM_API_KEY', $newKey);
                $this->updateEnvValue('CALCOM_V2_API_KEY', $newKey);
                
                // Update companies if using default key
                $defaultKey = config('services.calcom.api_key');
                Company::whereNotNull('calcom_api_key')
                    ->get()
                    ->each(function ($company) use ($defaultKey, $newKey) {
                        $currentKey = $this->encryptionService->decrypt($company->calcom_api_key);
                        if ($currentKey === $defaultKey) {
                            $company->calcom_api_key = $this->encryptionService->encrypt($newKey);
                            $company->save();
                            
                            $this->rotationLog[] = [
                                'company_id' => $company->id,
                                'service' => 'calcom',
                                'action' => 'rotated',
                                'timestamp' => now()
                            ];
                        }
                    });
                
                $this->info('Cal.com API key rotated successfully.');
            }
        }
    }

    private function rotateRetellKeys()
    {
        $this->warn('Retell.ai key rotation requires manual steps:');
        $this->info('1. Log in to Retell.ai dashboard');
        $this->info('2. Navigate to Settings > API Keys');
        $this->info('3. Generate new API key');
        $this->info('4. Update the key in this system');
        
        if ($this->confirm('Have you generated a new Retell.ai API key?')) {
            $newKey = $this->secret('Enter the new Retell.ai API key');
            
            if ($newKey) {
                // Update environment
                $this->updateEnvValue('DEFAULT_RETELL_API_KEY', $newKey);
                $this->updateEnvValue('RETELL_TOKEN', $newKey);
                
                // Update companies if using default key
                $defaultKey = config('services.retell.api_key');
                Company::whereNotNull('retell_api_key')
                    ->get()
                    ->each(function ($company) use ($defaultKey, $newKey) {
                        $currentKey = $this->encryptionService->decrypt($company->retell_api_key);
                        if ($currentKey === $defaultKey) {
                            $company->retell_api_key = $this->encryptionService->encrypt($newKey);
                            $company->save();
                            
                            $this->rotationLog[] = [
                                'company_id' => $company->id,
                                'service' => 'retell',
                                'action' => 'rotated',
                                'timestamp' => now()
                            ];
                        }
                    });
                
                $this->info('Retell.ai API key rotated successfully.');
            }
        }
        
        // Rotate webhook secret
        if ($this->confirm('Rotate Retell.ai webhook secret?')) {
            $newSecret = $this->encryptionService->generateSecureKey(32);
            
            $this->updateEnvValue('RETELL_WEBHOOK_SECRET', $newSecret);
            
            RetellConfiguration::all()->each(function ($config) use ($newSecret) {
                $config->webhook_secret = $this->encryptionService->encrypt($newSecret);
                $config->save();
                
                $this->rotationLog[] = [
                    'config_id' => $config->id,
                    'service' => 'retell_webhook',
                    'action' => 'rotated',
                    'timestamp' => now()
                ];
            });
            
            $this->warn('Important: Update the webhook secret in Retell.ai dashboard!');
            $this->info('New webhook secret: ' . $newSecret);
        }
    }

    private function updateEnvironmentFile()
    {
        $this->info('Phase 3: Updating environment file...');
        
        // Generate new APP_KEY if requested
        if ($this->confirm('Generate new APP_KEY (requires application restart)?')) {
            $this->call('key:generate', ['--force' => true]);
            
            $this->rotationLog[] = [
                'service' => 'app_key',
                'action' => 'rotated',
                'timestamp' => now()
            ];
        }
    }

    private function updateEnvValue($key, $value)
    {
        $path = base_path('.env');
        
        if (File::exists($path)) {
            $content = File::get($path);
            
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
            
            File::put($path, $content);
        }
    }

    private function logRotation()
    {
        $logFile = storage_path('logs/api-key-rotation-' . now()->format('Y-m-d-H-i-s') . '.json');
        
        File::put($logFile, json_encode([
            'rotation_date' => now()->toIso8601String(),
            'operator' => auth()->user()->email ?? 'console',
            'summary' => [
                'total_actions' => count($this->rotationLog),
                'encrypted' => collect($this->rotationLog)->where('action', 'encrypted')->count(),
                'rotated' => collect($this->rotationLog)->where('action', 'rotated')->count(),
            ],
            'details' => $this->rotationLog
        ], JSON_PRETTY_PRINT));
        
        $this->info("Rotation log saved to: {$logFile}");
    }

    private function displaySummary()
    {
        $this->newLine();
        $this->info('=== Rotation Summary ===');
        
        $encrypted = collect($this->rotationLog)->where('action', 'encrypted')->count();
        $rotated = collect($this->rotationLog)->where('action', 'rotated')->count();
        
        $this->table(
            ['Action', 'Count'],
            [
                ['Keys Encrypted', $encrypted],
                ['Keys Rotated', $rotated],
                ['Total Actions', count($this->rotationLog)]
            ]
        );
        
        $this->newLine();
        $this->warn('Next Steps:');
        $this->info('1. Restart application services (php-fpm, horizon)');
        $this->info('2. Test all integrations');
        $this->info('3. Monitor error logs for failed API calls');
        $this->info('4. Update webhook secrets in external services');
    }
}