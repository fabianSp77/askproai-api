<?php

$filePath = '/var/www/api-gateway/app/Services/Billing/MonthlyBillingAggregator.php';
$content = file_get_contents($filePath);

$oldCode = <<<'OLD'
    /**
     * Get monthly service fees data.
     */
    private function getMonthlyServicesData(
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        // Get active monthly service pricing for this company
        $pricings = CompanyServicePricing::where('company_id', $company->id)
            ->where('is_active', true)
            ->where('effective_from', '<=', $periodEnd)
            ->where(function ($q) use ($periodStart) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $periodStart);
            })
            ->whereHas('template', function ($q) {
                $q->where('pricing_type', 'monthly');
            })
            ->with('template')
            ->get();

        $fees = [];
        foreach ($pricings as $pricing) {
            $fees[] = [
                'pricing_id' => $pricing->id,
                'name' => $pricing->template->name ?? $pricing->custom_name ?? 'Monatlicher Service',
                'amount_cents' => (int) ($pricing->final_price * 100),
            ];
        }

        return $fees;
    }
OLD;

$newCode = <<<'NEW'
    /**
     * Get monthly service fees data.
     *
     * OPTIMIZED: Uses preloaded batch data when available (O(1) lookup).
     */
    private function getMonthlyServicesData(
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        // OPTIMIZATION: Use preloaded data if available
        if ($this->batchDataLoaded) {
            $pricings = $this->getBatchServicePricings($company->id);
        } else {
            // Fallback to direct query (for getChargesSummary preview)
            $pricings = CompanyServicePricing::where('company_id', $company->id)
                ->where('is_active', true)
                ->where('effective_from', '<=', $periodEnd)
                ->where(function ($q) use ($periodStart) {
                    $q->whereNull('effective_until')
                        ->orWhere('effective_until', '>=', $periodStart);
                })
                ->whereHas('template', function ($q) {
                    $q->where('pricing_type', 'monthly');
                })
                ->with('template')
                ->get();
        }

        $fees = [];
        foreach ($pricings as $pricing) {
            $fees[] = [
                'pricing_id' => $pricing->id,
                'name' => $pricing->template->name ?? $pricing->custom_name ?? 'Monatlicher Service',
                'amount_cents' => (int) ($pricing->final_price * 100),
            ];
        }

        return $fees;
    }
NEW;

$newContent = str_replace($oldCode, $newCode, $content);

if ($newContent === $content) {
    echo "ERROR: No replacement made!\n";
    exit(1);
}

$result = file_put_contents($filePath, $newContent);
if ($result === false) {
    echo "ERROR: Could not write file!\n";
    exit(1);
}

echo "SUCCESS: File updated!\n";
exit(0);
