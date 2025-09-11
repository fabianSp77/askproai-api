<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingChainService
{
    /**
     * Process a billable event through the multi-tier billing chain
     * 
     * @param Tenant $customer The end customer being billed
     * @param string $serviceType Type of service (call, api, appointment)
     * @param int $quantity Quantity (minutes for calls, count for others)
     * @param array $metadata Additional context data
     * @return array Array of created transactions
     */
    public function processBillingChain(
        Tenant $customer, 
        string $serviceType, 
        int $quantity = 1, 
        array $metadata = []
    ): array {
        return DB::transaction(function () use ($customer, $serviceType, $quantity, $metadata) {
            $transactions = [];
            
            // Calculate the base costs
            $costs = $this->calculateCosts($customer, $serviceType, $quantity);
            
            // Determine if this goes through a reseller
            if ($customer->hasReseller() && $customer->parentTenant) {
                // Multi-tier billing: Customer → Reseller → Platform
                $transactions = $this->processResellerChain(
                    $customer, 
                    $customer->parentTenant, 
                    $serviceType, 
                    $quantity, 
                    $costs, 
                    $metadata
                );
            } else {
                // Direct billing: Customer → Platform
                $transactions[] = $this->processDirectBilling(
                    $customer, 
                    $serviceType, 
                    $quantity, 
                    $costs['customer_cost'], 
                    $metadata
                );
            }
            
            // Track commission if applicable
            if (!empty($costs['commission']) && $costs['commission'] > 0) {
                $this->recordCommission($transactions, $costs);
            }
            
            // Check for low balance alerts
            $this->checkBalanceAlerts($customer);
            
            return $transactions;
        });
    }
    
    /**
     * Calculate costs at each tier
     */
    protected function calculateCosts(Tenant $customer, string $serviceType, int $quantity): array
    {
        $costs = [
            'platform_cost' => 0,
            'reseller_cost' => 0,
            'customer_cost' => 0,
            'commission' => 0,
            'markup' => 0
        ];
        
        // Get base platform cost
        $platformPricing = $this->getPlatformPricing($serviceType);
        $costs['platform_cost'] = $platformPricing * $quantity;
        
        if ($customer->hasReseller() && $customer->parentTenant) {
            $reseller = $customer->parentTenant;
            
            // Platform charges reseller base rate
            $costs['reseller_cost'] = $costs['platform_cost'];
            
            // Calculate reseller markup
            if ($reseller->reseller_markup_cents) {
                // Fixed markup per unit
                $costs['markup'] = $reseller->reseller_markup_cents * $quantity;
            } else {
                // Percentage markup
                $markupPercent = $reseller->min_markup_percent ?? 10;
                $costs['markup'] = (int) round($costs['platform_cost'] * ($markupPercent / 100));
            }
            
            // Customer pays reseller's price
            $costs['customer_cost'] = $costs['reseller_cost'] + $costs['markup'];
            
            // Commission is the markup amount (reseller's profit)
            $costs['commission'] = $costs['markup'];
            
        } else {
            // Direct customer pays standard rate
            $costs['customer_cost'] = $customer->getEffectivePrice($serviceType) * $quantity;
        }
        
        return $costs;
    }
    
    /**
     * Process billing through reseller chain
     */
    protected function processResellerChain(
        Tenant $customer,
        Tenant $reseller,
        string $serviceType,
        int $quantity,
        array $costs,
        array $metadata
    ): array {
        $transactions = [];
        $description = $this->generateDescription($serviceType, $quantity, $metadata);
        
        // 1. Deduct from customer balance (pays reseller price)
        if ($customer->balance_cents < $costs['customer_cost']) {
            throw new \Exception('Kunde hat unzureichendes Guthaben');
        }
        
        $customerTransaction = Transaction::create([
            'tenant_id' => $customer->id,
            'type' => Transaction::TYPE_USAGE,
            'amount_cents' => -$costs['customer_cost'],
            'balance_before_cents' => $customer->balance_cents,
            'balance_after_cents' => $customer->balance_cents - $costs['customer_cost'],
            'description' => $description,
            'reseller_tenant_id' => $reseller->id,
            'commission_amount_cents' => $costs['commission'],
            'base_cost_cents' => $costs['platform_cost'],
            'reseller_revenue_cents' => $costs['commission'],
            'metadata' => array_merge($metadata, [
                'service_type' => $serviceType,
                'quantity' => $quantity,
                'billing_chain' => 'customer_to_reseller'
            ])
        ]);
        
        $customer->decrement('balance_cents', $costs['customer_cost']);
        $transactions[] = $customerTransaction;
        
        // 2. Credit reseller with their commission
        $resellerCreditTransaction = Transaction::create([
            'tenant_id' => $reseller->id,
            'type' => Transaction::TYPE_USAGE,
            'amount_cents' => $costs['commission'],
            'balance_before_cents' => $reseller->balance_cents,
            'balance_after_cents' => $reseller->balance_cents + $costs['commission'],
            'description' => "Provision: {$description} (Kunde: {$customer->name})",
            'parent_transaction_id' => $customerTransaction->id,
            'metadata' => array_merge($metadata, [
                'service_type' => $serviceType,
                'quantity' => $quantity,
                'billing_chain' => 'reseller_commission',
                'customer_tenant_id' => $customer->id
            ])
        ]);
        
        $reseller->increment('balance_cents', $costs['commission']);
        $transactions[] = $resellerCreditTransaction;
        
        // 3. Deduct platform cost from reseller
        $resellerDebitTransaction = Transaction::create([
            'tenant_id' => $reseller->id,
            'type' => Transaction::TYPE_USAGE,
            'amount_cents' => -$costs['platform_cost'],
            'balance_before_cents' => $reseller->balance_cents,
            'balance_after_cents' => $reseller->balance_cents - $costs['platform_cost'],
            'description' => "Plattformkosten: {$description}",
            'parent_transaction_id' => $customerTransaction->id,
            'metadata' => array_merge($metadata, [
                'service_type' => $serviceType,
                'quantity' => $quantity,
                'billing_chain' => 'reseller_to_platform'
            ])
        ]);
        
        $reseller->decrement('balance_cents', $costs['platform_cost']);
        $transactions[] = $resellerDebitTransaction;
        
        // 4. Create platform revenue record (virtual - platform doesn't have balance)
        if ($platform = Tenant::where('tenant_type', 'platform')->first()) {
            $platformTransaction = Transaction::create([
                'tenant_id' => $platform->id,
                'type' => Transaction::TYPE_USAGE,
                'amount_cents' => $costs['platform_cost'],
                'balance_before_cents' => 0,
                'balance_after_cents' => 0,
                'description' => "Plattformumsatz: {$description}",
                'parent_transaction_id' => $customerTransaction->id,
                'metadata' => array_merge($metadata, [
                    'service_type' => $serviceType,
                    'quantity' => $quantity,
                    'billing_chain' => 'platform_revenue',
                    'reseller_tenant_id' => $reseller->id,
                    'customer_tenant_id' => $customer->id
                ])
            ]);
            $transactions[] = $platformTransaction;
        }
        
        return $transactions;
    }
    
    /**
     * Process direct billing (no reseller)
     */
    protected function processDirectBilling(
        Tenant $customer,
        string $serviceType,
        int $quantity,
        int $costCents,
        array $metadata
    ): Transaction {
        if ($customer->balance_cents < $costCents) {
            throw new \Exception('Unzureichendes Guthaben');
        }
        
        $description = $this->generateDescription($serviceType, $quantity, $metadata);
        
        $transaction = Transaction::create([
            'tenant_id' => $customer->id,
            'type' => Transaction::TYPE_USAGE,
            'amount_cents' => -$costCents,
            'balance_before_cents' => $customer->balance_cents,
            'balance_after_cents' => $customer->balance_cents - $costCents,
            'description' => $description,
            'metadata' => array_merge($metadata, [
                'service_type' => $serviceType,
                'quantity' => $quantity,
                'billing_chain' => 'direct'
            ])
        ]);
        
        $customer->decrement('balance_cents', $costCents);
        
        return $transaction;
    }
    
    /**
     * Record commission in the ledger
     */
    protected function recordCommission(array $transactions, array $costs): void
    {
        if (empty($transactions)) return;
        
        $primaryTransaction = $transactions[0];
        
        if ($primaryTransaction->reseller_tenant_id) {
            DB::table('commission_ledger')->insert([
                'reseller_tenant_id' => $primaryTransaction->reseller_tenant_id,
                'customer_tenant_id' => $primaryTransaction->tenant_id,
                'transaction_id' => $primaryTransaction->id,
                'gross_amount_cents' => abs($primaryTransaction->amount_cents),
                'platform_cost_cents' => $costs['platform_cost'],
                'commission_cents' => $costs['commission'],
                'commission_rate' => $costs['commission'] / abs($primaryTransaction->amount_cents) * 100,
                'status' => 'pending',
                'description' => $primaryTransaction->description,
                'metadata' => json_encode($primaryTransaction->metadata),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
    
    /**
     * Check and create balance alerts
     */
    protected function checkBalanceAlerts(Tenant $tenant): void
    {
        // Check low balance threshold
        $threshold = $tenant->billingSettings->low_balance_threshold_cents ?? 1000; // Default 10€
        
        if ($tenant->balance_cents < $threshold) {
            DB::table('billing_alerts')->insert([
                'tenant_id' => $tenant->id,
                'type' => 'low_balance',
                'severity' => $tenant->balance_cents < ($threshold / 2) ? 'critical' : 'warning',
                'title' => 'Niedriger Kontostand',
                'message' => "Ihr Guthaben beträgt nur noch {$tenant->getFormattedBalance()}. Bitte laden Sie Ihr Konto auf.",
                'metadata' => json_encode([
                    'balance_cents' => $tenant->balance_cents,
                    'threshold_cents' => $threshold
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // TODO: Send email notification if enabled
        }
    }
    
    /**
     * Get platform base pricing
     */
    protected function getPlatformPricing(string $serviceType): int
    {
        // These are the base costs that the platform charges
        return match($serviceType) {
            'call', 'call_minutes' => 30,  // 30 cents per minute (platform cost)
            'api', 'api_call' => 10,       // 10 cents per API call
            'appointment' => 100,           // 100 cents (1€) per appointment
            default => 10                   // Default 10 cents
        };
    }
    
    /**
     * Generate description for transaction
     */
    protected function generateDescription(string $serviceType, int $quantity, array $metadata): string
    {
        return match($serviceType) {
            'call', 'call_minutes' => "Telefonanruf ({$quantity} Minuten)",
            'api', 'api_call' => "API-Nutzung ({$quantity} Aufrufe)",
            'appointment' => "Terminbuchung ({$quantity} Termine)",
            default => "Service-Nutzung ({$quantity} Einheiten)"
        };
    }
    
    /**
     * Process call billing with multi-tier support
     */
    public function billCall(Call $call): array
    {
        $tenant = Tenant::find($call->tenant_id);
        if (!$tenant) {
            throw new \Exception('Mandant für Anruf nicht gefunden');
        }
        
        $durationMinutes = ceil($call->duration_seconds / 60);
        
        return $this->processBillingChain(
            $tenant,
            'call',
            $durationMinutes,
            [
                'call_id' => $call->id,
                'phone_number' => $call->phone_number,
                'duration_seconds' => $call->duration_seconds
            ]
        );
    }
    
    /**
     * Process appointment billing with multi-tier support
     */
    public function billAppointment(Appointment $appointment): array
    {
        $customer = $appointment->customer;
        if (!$customer || !$customer->tenant) {
            throw new \Exception('Kunde oder Mandant für Termin nicht gefunden');
        }
        
        return $this->processBillingChain(
            $customer->tenant,
            'appointment',
            1,
            [
                'appointment_id' => $appointment->id,
                'service_id' => $appointment->service_id,
                'staff_id' => $appointment->staff_id
            ]
        );
    }
}