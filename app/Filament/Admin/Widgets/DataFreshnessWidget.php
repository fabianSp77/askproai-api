<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Company;
use Filament\Widgets\Widget;
use Carbon\Carbon;

class DataFreshnessWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.data-freshness';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    public function getDataFreshness(): array
    {
        $now = Carbon::now();
        $freshness = [];
        
        // Check calls
        $lastCall = Call::latest()->first();
        if ($lastCall) {
            $daysSince = $lastCall->created_at->diffInDays($now);
            $freshness['calls'] = [
                'entity' => 'Calls',
                'last_activity' => $lastCall->created_at->format('Y-m-d H:i'),
                'days_ago' => $daysSince,
                'status' => $this->getFreshnessStatus($daysSince),
                'message' => $this->getFreshnessMessage($daysSince, 'call'),
            ];
        }
        
        // Check customers
        $lastCustomer = Customer::latest()->first();
        if ($lastCustomer) {
            $daysSince = $lastCustomer->created_at->diffInDays($now);
            $freshness['customers'] = [
                'entity' => 'Customers',
                'last_activity' => $lastCustomer->created_at->format('Y-m-d H:i'),
                'days_ago' => $daysSince,
                'status' => $this->getFreshnessStatus($daysSince),
                'message' => $this->getFreshnessMessage($daysSince, 'customer'),
            ];
        }
        
        // Check appointments
        $lastAppointment = Appointment::latest()->first();
        if ($lastAppointment) {
            $daysSince = $lastAppointment->created_at->diffInDays($now);
            $freshness['appointments'] = [
                'entity' => 'Appointments',
                'last_activity' => $lastAppointment->created_at->format('Y-m-d H:i'),
                'days_ago' => $daysSince,
                'status' => $this->getFreshnessStatus($daysSince),
                'message' => $this->getFreshnessMessage($daysSince, 'appointment'),
            ];
        }
        
        // Check companies
        $lastCompany = Company::latest()->first();
        if ($lastCompany) {
            $daysSince = $lastCompany->created_at->diffInDays($now);
            $freshness['companies'] = [
                'entity' => 'Companies',
                'last_activity' => $lastCompany->created_at->format('Y-m-d H:i'),
                'days_ago' => $daysSince,
                'status' => $this->getFreshnessStatus($daysSince),
                'message' => $this->getFreshnessMessage($daysSince, 'company record'),
            ];
        }
        
        return $freshness;
    }
    
    protected function getFreshnessStatus(int $days): string
    {
        if ($days < 1) {
            return 'fresh';
        } elseif ($days < 7) {
            return 'recent';
        } elseif ($days < 30) {
            return 'stale';
        } else {
            return 'critical';
        }
    }
    
    protected function getFreshnessMessage(int $days, string $entityType): string
    {
        if ($days < 1) {
            return "Active today";
        } elseif ($days === 1) {
            return "Last {$entityType} yesterday";
        } elseif ($days < 7) {
            return "Last {$entityType} {$days} days ago";
        } elseif ($days < 30) {
            $weeks = floor($days / 7);
            return "Last {$entityType} {$weeks} week" . ($weeks > 1 ? 's' : '') . " ago";
        } else {
            $months = floor($days / 30);
            return "⚠️ Last {$entityType} {$months} month" . ($months > 1 ? 's' : '') . " ago - check integrations!";
        }
    }
    
    public static function canView(): bool
    {
        // Temporarily disabled due to Livewire root tag issue
        return false;
        // return auth()->user()?->hasRole('Admin') ?? false;
    }
}