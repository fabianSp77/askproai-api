<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\PrepaidBalance;
use App\Models\PrepaidTransaction;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;

class VerifyBusinessStructure extends Command
{
    protected $signature = 'business:verify';
    protected $description = 'Verify complete business structure and show overview';

    public function handle()
    {
        $this->info('=== Business Structure Verification ===');
        $this->info('');

        // Find the reseller
        $reseller = Company::where('company_type', 'reseller')
                          ->where('name', 'LIKE', '%Premium Telecom%')
                          ->first();

        if (!$reseller) {
            $this->error('Reseller company not found!');
            return;
        }

        $this->info('🏢 RESELLER COMPANY');
        $this->line('├─ ID: ' . $reseller->id);
        $this->line('├─ Name: ' . $reseller->name);
        $this->line('├─ Type: ' . $reseller->company_type);
        $this->line('├─ Industry: ' . $reseller->industry);
        $this->line('├─ Email: ' . $reseller->email);
        $this->line('├─ Phone: ' . $reseller->phone);
        $this->line('├─ Address: ' . $reseller->address . ', ' . $reseller->city . ' ' . $reseller->postal_code);
        $this->line('└─ Commission Rate: ' . ($reseller->commission_rate * 100) . '%');
        $this->info('');

        // Reseller's prepaid balance
        $resellerBalance = PrepaidBalance::where('company_id', $reseller->id)->first();
        if ($resellerBalance) {
            $this->info('💰 RESELLER PREPAID BALANCE');
            $this->line('├─ Balance: €' . number_format($resellerBalance->balance, 2));
            $this->line('├─ Bonus Balance: €' . number_format($resellerBalance->bonus_balance, 2));
            $this->line('├─ Reserved Balance: €' . number_format($resellerBalance->reserved_balance, 2));
            $this->line('├─ Effective Balance: €' . number_format($resellerBalance->effective_balance, 2));
            $this->line('├─ Low Balance Threshold: €' . number_format($resellerBalance->low_balance_threshold, 2));
            $this->line('├─ Auto Topup: ' . ($resellerBalance->auto_topup_enabled ? 'Enabled' : 'Disabled'));
            $this->line('├─ Auto Topup Threshold: €' . number_format($resellerBalance->auto_topup_threshold, 2));
            $this->line('└─ Auto Topup Amount: €' . number_format($resellerBalance->auto_topup_amount, 2));
            $this->info('');
        }

        // Find client companies
        $clients = Company::where('parent_company_id', $reseller->id)->get();
        
        $this->info('👥 CLIENT COMPANIES (' . $clients->count() . ')');
        
        foreach ($clients as $index => $client) {
            $isLast = ($index === $clients->count() - 1);
            $prefix = $isLast ? '└─' : '├─';
            
            $this->line($prefix . ' ' . $client->name . ' (ID: ' . $client->id . ')');
            $this->line(($isLast ? '   ' : '│  ') . '├─ Industry: ' . $client->industry);
            $this->line(($isLast ? '   ' : '│  ') . '├─ Email: ' . $client->email);
            $this->line(($isLast ? '   ' : '│  ') . '├─ Phone: ' . $client->phone);
            $this->line(($isLast ? '   ' : '│  ') . '└─ Address: ' . $client->address . ', ' . $client->city . ' ' . $client->postal_code);
            
            // Client's prepaid balance
            $clientBalance = PrepaidBalance::where('company_id', $client->id)->first();
            if ($clientBalance) {
                $this->line(($isLast ? '   ' : '│  ') . '💰 Balance: €' . number_format($clientBalance->effective_balance, 2));
            }
            
            // Client's branches
            $branches = Branch::where('company_id', $client->id)->get();
            if ($branches->count() > 0) {
                $this->line(($isLast ? '   ' : '│  ') . '🏪 Branches (' . $branches->count() . '):');
                foreach ($branches as $branchIndex => $branch) {
                    $isBranchLast = ($branchIndex === $branches->count() - 1);
                    $branchPrefix = $isBranchLast ? '└─' : '├─';
                    $this->line(($isLast ? '   ' : '│  ') . '   ' . $branchPrefix . ' ' . $branch->name);
                    $this->line(($isLast ? '   ' : '│  ') . '     ' . ($isBranchLast ? ' ' : '│') . ' ├─ Phone: ' . $branch->phone_number);
                    $this->line(($isLast ? '   ' : '│  ') . '     ' . ($isBranchLast ? ' ' : '│') . ' └─ Email: ' . $branch->notification_email);
                }
            }
            
            // Client's staff
            $staff = Staff::where('company_id', $client->id)->get();
            if ($staff->count() > 0) {
                $this->line(($isLast ? '   ' : '│  ') . '👨‍💼 Staff (' . $staff->count() . '):');
                foreach ($staff as $staffIndex => $member) {
                    $isStaffLast = ($staffIndex === $staff->count() - 1);
                    $staffPrefix = $isStaffLast ? '└─' : '├─';
                    $ownerIndicator = (strpos($member->notes, 'Owner') !== false) ? ' 👑' : '';
                    $this->line(($isLast ? '   ' : '│  ') . '   ' . $staffPrefix . ' ' . $member->name . $ownerIndicator);
                    $this->line(($isLast ? '   ' : '│  ') . '     ' . ($isStaffLast ? ' ' : '│') . ' ├─ Email: ' . $member->email);
                    $this->line(($isLast ? '   ' : '│  ') . '     ' . ($isStaffLast ? ' ' : '│') . ' ├─ Phone: ' . $member->phone);
                    $this->line(($isLast ? '   ' : '│  ') . '     ' . ($isStaffLast ? ' ' : '│') . ' └─ Status: ' . ($member->is_active ? 'Active' : 'Inactive') . ', ' . ($member->is_bookable ? 'Bookable' : 'Not Bookable'));
                }
            }
            
            // Client's services
            $services = Service::where('company_id', $client->id)->get();
            if ($services->count() > 0) {
                $this->line(($isLast ? '   ' : '│  ') . '💇‍♀️ Services (' . $services->count() . '):');
                foreach ($services as $serviceIndex => $service) {
                    $isServiceLast = ($serviceIndex === $services->count() - 1);
                    $servicePrefix = $isServiceLast ? '└─' : '├─';
                    $this->line(($isLast ? '   ' : '│  ') . '   ' . $servicePrefix . ' ' . $service->name);
                    $this->line(($isLast ? '   ' : '│  ') . '     ' . ($isServiceLast ? ' ' : '│') . ' ├─ Price: €' . number_format($service->price, 2));
                    $this->line(($isLast ? '   ' : '│  ') . '     ' . ($isServiceLast ? ' ' : '│') . ' ├─ Duration: ' . $service->duration . ' minutes');
                    $this->line(($isLast ? '   ' : '│  ') . '     ' . ($isServiceLast ? ' ' : '│') . ' └─ Category: ' . $service->category);
                }
            }
            
            if (!$isLast) {
                $this->line('│');
            }
        }
        
        $this->info('');
        
        // Pricing structure
        $this->info('💵 PRICING STRUCTURE');
        $this->line('├─ System charges reseller: €0.30/minute (€0.005/second)');
        $this->line('├─ Reseller charges client: €0.40/minute (€0.0067/second)');
        $this->line('├─ Reseller profit per minute: €0.10');
        $this->line('├─ Billing accuracy: Per-second');
        $this->line('└─ Payment method: Prepaid (Guthabenbasis)');
        $this->info('');
        
        // Recent transactions
        $this->info('📊 RECENT TRANSACTIONS');
        $recentTransactions = PrepaidTransaction::with('company')
                                              ->whereIn('company_id', $clients->pluck('id')->push($reseller->id))
                                              ->orderBy('created_at', 'desc')
                                              ->limit(10)
                                              ->get();
        
        if ($recentTransactions->count() > 0) {
            foreach ($recentTransactions as $index => $transaction) {
                $isLast = ($index === $recentTransactions->count() - 1);
                $prefix = $isLast ? '└─' : '├─';
                $sign = $transaction->isCredit() ? '+' : '-';
                $this->line($prefix . ' ' . $transaction->created_at->format('Y-m-d H:i:s') . ' | ' . $transaction->company->name);
                $this->line(($isLast ? '   ' : '│  ') . '├─ Type: ' . $transaction->type_label);
                $this->line(($isLast ? '   ' : '│  ') . '├─ Amount: ' . $sign . '€' . number_format(abs($transaction->amount), 4));
                $this->line(($isLast ? '   ' : '│  ') . '└─ Description: ' . $transaction->description);
            }
        } else {
            $this->line('└─ No transactions found');
        }
        
        $this->info('');
        
        // Per-second billing demonstration
        $this->info('🧮 PER-SECOND BILLING DEMONSTRATION');
        $durations = [30, 75, 180, 225, 360]; // seconds
        
        foreach ($durations as $index => $seconds) {
            $isLast = ($index === count($durations) - 1);
            $prefix = $isLast ? '└─' : '├─';
            
            $resellerCost = $seconds * (0.30 / 60);
            $clientCost = $seconds * (0.40 / 60);
            $profit = $clientCost - $resellerCost;
            
            $minutes = intval($seconds / 60);
            $remainingSeconds = $seconds % 60;
            $timeDisplay = sprintf('%d:%02d', $minutes, $remainingSeconds);
            
            $this->line($prefix . ' Call duration: ' . $seconds . 's (' . $timeDisplay . ')');
            $this->line(($isLast ? '   ' : '│  ') . '├─ Reseller pays: €' . number_format($resellerCost, 4));
            $this->line(($isLast ? '   ' : '│  ') . '├─ Client pays: €' . number_format($clientCost, 4));
            $this->line(($isLast ? '   ' : '│  ') . '└─ Reseller profit: €' . number_format($profit, 4));
        }
        
        $this->info('');
        $this->info('✅ Business structure verification complete!');
    }
}