<?php

namespace App\Filament\Admin\Resources\CompanyResource\Widgets;

use App\Models\PhoneNumber;
use App\Models\Call;
use App\Models\PrepaidTransaction;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CompanyDetailsWidget extends Widget
{
    public ?Model $record = null;

    protected static string $view = 'filament.admin.resources.company-resource.widgets.company-details-widget';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        if (!$this->record) {
            return [
                'phoneNumbers' => collect(),
                'recentCalls' => collect(),
                'recentTransactions' => collect(),
                'integrationStatus' => [],
                'monthlyStats' => [],
            ];
        }

        // Telefonnummern
        $phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('company_id', $this->record->id)
            ->with('branch')
            ->get();

        // Letzte Anrufe
        $recentCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('company_id', $this->record->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Letzte Transaktionen
        $recentTransactions = PrepaidTransaction::where('company_id', $this->record->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Integration Status
        $integrationStatus = [
            'retell' => !empty($this->record->retell_api_key),
            'calcom' => !empty($this->record->calcom_api_key),
            'stripe' => !empty($this->record->stripe_customer_id),
        ];

        // Monatsstatistiken
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlyStats = [
            'total_calls' => Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $this->record->id)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count(),
            
            'total_minutes' => round(Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $this->record->id)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('duration_sec') / 60, 2),
            
            'unique_callers' => Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $this->record->id)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->distinct('from_number')
                ->count('from_number'),
        ];

        return [
            'phoneNumbers' => $phoneNumbers,
            'recentCalls' => $recentCalls,
            'recentTransactions' => $recentTransactions,
            'integrationStatus' => $integrationStatus,
            'monthlyStats' => $monthlyStats,
        ];
    }
}