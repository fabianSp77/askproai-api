<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class DataFreshnessStatsWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        $now = Carbon::now();
        $stats = [];
        
        // Check calls freshness
        $lastCall = Call::latest()->first();
        if ($lastCall) {
            $daysSince = $lastCall->created_at->diffInDays($now);
            $stats[] = $this->createFreshnessStat(
                'Last Call Activity',
                $lastCall->created_at,
                $daysSince,
                'heroicon-m-phone'
            );
        }
        
        // Check customers freshness
        $lastCustomer = Customer::latest()->first();
        if ($lastCustomer) {
            $daysSince = $lastCustomer->created_at->diffInDays($now);
            $stats[] = $this->createFreshnessStat(
                'Last Customer Added',
                $lastCustomer->created_at,
                $daysSince,
                'heroicon-m-user-group'
            );
        } else {
            $stats[] = Stat::make('Customer Data', 'No customers')
                ->description('Import customer data needed')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger');
        }
        
        // Check appointments freshness
        $lastAppointment = Appointment::latest()->first();
        if ($lastAppointment) {
            $daysSince = $lastAppointment->created_at->diffInDays($now);
            $stats[] = $this->createFreshnessStat(
                'Last Appointment',
                $lastAppointment->created_at,
                $daysSince,
                'heroicon-m-calendar'
            );
        } else {
            $stats[] = Stat::make('Appointment Data', 'No appointments')
                ->description('Sync with CalCom needed')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger');
        }
        
        // Check companies freshness
        $companyCount = Company::count();
        $lastCompany = Company::latest()->first();
        if ($lastCompany) {
            $daysSince = $lastCompany->created_at->diffInDays($now);
            $stats[] = Stat::make('Companies', $companyCount . ' total')
                ->description($this->getTimeAgoText($daysSince))
                ->descriptionIcon('heroicon-m-building-office')
                ->color($this->getFreshnessColor($daysSince));
        }
        
        return $stats;
    }
    
    protected function createFreshnessStat(string $label, Carbon $date, int $daysSince, string $icon): Stat
    {
        $timeAgo = $this->getTimeAgoText($daysSince);
        $color = $this->getFreshnessColor($daysSince);
        
        return Stat::make($label, $date->format('d.m.Y'))
            ->description($timeAgo)
            ->descriptionIcon($icon)
            ->color($color)
            ->chart($this->generateTrendChart($daysSince));
    }
    
    protected function getTimeAgoText(int $days): string
    {
        if ($days === 0) {
            return 'Today - Active';
        } elseif ($days === 1) {
            return 'Yesterday';
        } elseif ($days < 7) {
            return $days . ' days ago';
        } elseif ($days < 30) {
            $weeks = floor($days / 7);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } elseif ($days < 60) {
            return '1 month ago - Check integration';
        } else {
            $months = floor($days / 30);
            return '⚠️ ' . $months . ' months ago - Integration broken?';
        }
    }
    
    protected function getFreshnessColor(int $days): string
    {
        if ($days < 1) {
            return 'success';
        } elseif ($days < 7) {
            return 'info';
        } elseif ($days < 30) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
    
    protected function generateTrendChart(int $daysSince): array
    {
        // Generate a declining trend chart based on staleness
        $chart = [];
        for ($i = 0; $i < 7; $i++) {
            $value = max(0, 7 - $i - ($daysSince / 10));
            $chart[] = $value;
        }
        return $chart;
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }
}