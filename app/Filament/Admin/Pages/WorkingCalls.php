<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Call;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkingCalls extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationLabel = 'Working Calls';
    protected static ?int $navigationSort = 98;
    protected static ?string $slug = 'working-calls';
    protected static string $view = 'filament.admin.pages.working-calls';
    
    public array $stats = [];
    public array $calls = [];
    public int $totalCalls = 0;
    public int $todaysCalls = 0;
    public int $callsWithAppointments = 0;
    public float $avgDuration = 0;
    
    public function mount(): void
    {
        // Force company context
        if (Auth::check() && Auth::user()->company_id) {
            app()->instance('current_company_id', Auth::user()->company_id);
            app()->instance('company_context_source', 'web_auth');
        }
        
        $this->loadStats();
        $this->loadCalls();
    }
    
    protected function loadStats(): void
    {
        $companyId = Auth::user()->company_id;
        
        // Total calls
        $this->totalCalls = Call::where('company_id', $companyId)->count();
        
        // Today's calls
        $this->todaysCalls = Call::where('company_id', $companyId)
            ->whereDate('created_at', Carbon::today())
            ->count();
        
        // Calls with appointments
        $this->callsWithAppointments = Call::where('company_id', $companyId)
            ->whereNotNull('appointment_id')
            ->count();
        
        // Average duration
        $this->avgDuration = Call::where('company_id', $companyId)
            ->whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0)
            ->avg('duration_sec') ?? 0;
    }
    
    protected function loadCalls(): void
    {
        $this->calls = Call::where('company_id', Auth::user()->company_id)
            ->with(['customer', 'appointment', 'branch'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(function($call) {
                return [
                    'id' => $call->id,
                    'call_id' => substr($call->call_id ?? '', 0, 8) . '...',
                    'phone_number' => $call->phone_number,
                    'customer_name' => $call->customer->name ?? 'Unbekannt',
                    'branch_name' => $call->branch->name ?? '-',
                    'duration' => $call->duration_sec ? gmdate('i:s', $call->duration_sec) : '-',
                    'has_appointment' => $call->appointment_id ? true : false,
                    'created_at' => $call->created_at->format('d.m.Y H:i'),
                    'sentiment' => $call->sentiment ?? 'neutral',
                ];
            })
            ->toArray();
    }
    
    public function refresh(): void
    {
        $this->loadStats();
        $this->loadCalls();
    }
}