<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProgressiveEnhancementService;
use App\Services\AppointmentMCPServer;
use App\Services\CallMCPServer;
use App\Services\DashboardMCPServer;

class ProgressiveController extends Controller
{
    protected ProgressiveEnhancementService $enhancementService;
    
    public function __construct(ProgressiveEnhancementService $enhancementService)
    {
        $this->enhancementService = $enhancementService;
    }
    
    /**
     * Show dashboard based on enhancement level
     */
    public function dashboard(Request $request)
    {
        $level = $request->attributes->get('enhancement_level', 2);
        
        // Get dashboard data
        $dashboardMCP = app(DashboardMCPServer::class);
        $dashboardData = $dashboardMCP->executeTool('getDashboardStats', [
            'company_id' => auth()->user()->company_id,
            'branch_id' => $request->get('branch_id'),
            'date_range' => '30days'
        ]);
        
        $data = [
            'stats' => $dashboardData['stats'] ?? [],
            'activities' => $dashboardData['recent_activities'] ?? [],
            'todayAppointments' => $dashboardData['today_appointments'] ?? 0,
        ];
        
        // Return view based on enhancement level
        switch ($level) {
            case 0: // No JS
                return view('portal.dashboard-no-js', $data);
                
            case 1: // Basic Alpine
                return view('portal.dashboard-basic', $data);
                
            case 2: // Full Alpine
                return view('portal.dashboard-alpine', $data);
                
            case 3: // Hybrid
                return view('portal.dashboard-hybrid', $data);
                
            case 4: // React SPA
                // For SPA, we return minimal HTML with initial state
                return view('portal.spa-app', [
                    'initialState' => [
                        'dashboard' => $data,
                        'user' => auth()->user(),
                        'company' => auth()->user()->company,
                    ]
                ]);
                
            default:
                return view('portal.dashboard-alpine', $data);
        }
    }
    
    /**
     * Show appointments based on enhancement level
     */
    public function appointments(Request $request)
    {
        $level = $request->attributes->get('enhancement_level', 2);
        
        // Get appointments data
        $appointmentMCP = app(AppointmentMCPServer::class);
        $appointments = $appointmentMCP->executeTool('listAppointments', [
            'company_id' => auth()->user()->company_id,
            'branch_id' => $request->get('branch_id'),
            'date' => $request->get('date', now()->format('Y-m-d')),
            'status' => $request->get('status'),
            'page' => $request->get('page', 1),
            'per_page' => 20
        ]);
        
        $data = [
            'appointments' => $appointments['appointments'] ?? [],
            'pagination' => $appointments['pagination'] ?? null,
            'filters' => [
                'date' => $request->get('date'),
                'status' => $request->get('status'),
                'branch_id' => $request->get('branch_id'),
            ]
        ];
        
        // Return appropriate view
        switch ($level) {
            case 0: // No JS - server-side filtering and pagination
                return view('portal.appointments.no-js', $data);
                
            case 1: // Basic Alpine - simple interactivity
                return view('portal.appointments.basic', $data);
                
            case 2: // Full Alpine - rich interactions
                return view('portal.appointments.alpine', $data);
                
            case 3: // Hybrid - Alpine filters, React table
                return view('portal.appointments.hybrid', $data);
                
            case 4: // React SPA
                return response()->json($data);
                
            default:
                return view('portal.appointments.alpine', $data);
        }
    }
    
    /**
     * Show calls based on enhancement level
     */
    public function calls(Request $request)
    {
        $level = $request->attributes->get('enhancement_level', 2);
        
        // Get calls data
        $callMCP = app(CallMCPServer::class);
        $calls = $callMCP->executeTool('listCalls', [
            'company_id' => auth()->user()->company_id,
            'branch_id' => $request->get('branch_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status'),
            'page' => $request->get('page', 1),
            'per_page' => 20
        ]);
        
        $data = [
            'calls' => $calls['calls'] ?? [],
            'pagination' => $calls['pagination'] ?? null,
            'statistics' => $calls['statistics'] ?? [],
            'filters' => [
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'status' => $request->get('status'),
                'branch_id' => $request->get('branch_id'),
            ]
        ];
        
        // Return appropriate view
        switch ($level) {
            case 0: // No JS
                return view('portal.calls.no-js', $data);
                
            case 1: // Basic Alpine
                return view('portal.calls.basic', $data);
                
            case 2: // Full Alpine
                return view('portal.calls.alpine', $data);
                
            case 3: // Hybrid
                return view('portal.calls.hybrid', $data);
                
            case 4: // React SPA
                return response()->json($data);
                
            default:
                return view('portal.calls.alpine', $data);
        }
    }
    
    /**
     * Handle form submission based on enhancement level
     */
    public function submitForm(Request $request)
    {
        $level = $request->attributes->get('enhancement_level', 2);
        
        // Validate form
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);
        
        // Process form...
        // (Implementation depends on form type)
        
        // Return response based on level
        if ($level === 0) {
            // No JS - full page redirect
            return redirect()->back()->with('success', 'Formular erfolgreich gesendet!');
        } elseif (in_array($level, [1, 2])) {
            // Alpine - can handle both redirect and JSON
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Formular erfolgreich gesendet!']);
            }
            return redirect()->back()->with('success', 'Formular erfolgreich gesendet!');
        } else {
            // React/Hybrid - always JSON
            return response()->json(['success' => true, 'message' => 'Formular erfolgreich gesendet!']);
        }
    }
    
    /**
     * Get component based on enhancement level
     */
    public function getComponent(Request $request, string $component)
    {
        $level = $request->attributes->get('enhancement_level', 2);
        $strategy = $this->enhancementService->getComponentStrategy($level, $component);
        
        // Map strategy to actual component view/data
        $componentMap = [
            'server-form' => 'components.forms.server',
            'alpine-form-basic' => 'components.forms.alpine-basic',
            'alpine-form' => 'components.forms.alpine',
            'react-form' => ['type' => 'react', 'component' => 'Form'],
            
            'server-table' => 'components.tables.server',
            'alpine-table-basic' => 'components.tables.alpine-basic',
            'alpine-table' => 'components.tables.alpine',
            'react-table' => ['type' => 'react', 'component' => 'Table'],
        ];
        
        $mapped = $componentMap[$strategy] ?? $componentMap['alpine-form'];
        
        if (is_array($mapped) && $mapped['type'] === 'react') {
            // Return React component info
            return response()->json([
                'component' => $mapped['component'],
                'props' => $request->all(),
            ]);
        }
        
        // Return Blade view
        return view($mapped, $request->all());
    }
    
    /**
     * Performance test endpoint
     */
    public function performanceTest(Request $request)
    {
        $levels = [0, 1, 2, 3, 4];
        $results = [];
        
        foreach ($levels as $level) {
            $start = microtime(true);
            
            // Simulate rendering for each level
            $assets = $this->enhancementService->getAssets($level);
            $cacheStrategy = $this->enhancementService->getCacheStrategy($level);
            $hints = $this->enhancementService->getPerformanceHints($level);
            
            $results[$level] = [
                'level' => $level,
                'assets' => $assets,
                'cache_ttl' => $cacheStrategy['ttl'],
                'performance_hints' => $hints,
                'render_time' => round((microtime(true) - $start) * 1000, 2) . 'ms',
            ];
        }
        
        return response()->json([
            'current_level' => $request->attributes->get('enhancement_level', 2),
            'user_agent' => $request->header('User-Agent'),
            'connection_type' => $request->header('ECT'),
            'save_data' => $request->header('Save-Data'),
            'results' => $results,
        ]);
    }
}