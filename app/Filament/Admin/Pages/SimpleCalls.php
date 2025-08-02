<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Call;
use Illuminate\Support\Facades\Auth;

class SimpleCalls extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationLabel = 'Simple Calls';
    protected static ?int $navigationSort = 99;
    protected static ?string $slug = 'simple-calls';
    
    protected static string $view = 'filament.admin.pages.simple-calls';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'developer']) || $user->email === 'dev@askproai.de');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    public $calls = [];
    public $error = null;
    public $debugInfo = [];
    
    public function mount(): void
    {
        try {
            // Debug info
            $this->debugInfo = [
                'auth_check' => Auth::check(),
                'user_id' => Auth::id(),
                'user_email' => Auth::user() ? Auth::user()->email : null,
                'company_id' => Auth::user() ? Auth::user()->company_id : null,
                'app_has_company' => app()->has('current_company_id'),
                'current_company_id' => app()->has('current_company_id') ? app('current_company_id') : null,
                'company_context_source' => app()->has('company_context_source') ? app('company_context_source') : null,
            ];
            
            // Force set company context
            if (Auth::check() && Auth::user()->company_id) {
                app()->instance('current_company_id', Auth::user()->company_id);
                app()->instance('company_context_source', 'web_auth');
            }
            
            // Get calls with eager loading
            $query = Call::query()->with(['customer']);
            $this->debugInfo['query_sql'] = $query->toSql();
            $this->debugInfo['query_bindings'] = $query->getBindings();
            
            $this->calls = $query->latest()->limit(10)->get()->map(function($call) {
                return [
                    'id' => $call->id,
                    'call_id' => $call->call_id,
                    'phone_number' => $call->phone_number,
                    'duration' => $call->duration_sec,
                    'created_at' => $call->created_at->format('Y-m-d H:i:s'),
                    'customer_name' => $call->customer ? $call->customer->name : 'N/A',
                ];
            })->toArray();
            
            $this->debugInfo['call_count'] = count($this->calls);
            
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->debugInfo['error'] = $e->getMessage();
            $this->debugInfo['trace'] = $e->getTraceAsString();
        }
    }
}