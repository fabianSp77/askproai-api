<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DemoController extends Controller
{
    /**
     * Initialize demo session with proper authentication.
     */
    public function index(Request $request)
    {
        // Debug log to verify controller is called
        Log::info('DemoController::index called');

        try {
            // Find demo user
            $demoUser = PortalUser::where('email', 'demo@askproai.de')->first();

            if (! $demoUser) {
                Log::error('Demo user not found: demo@askproai.de');

                return redirect('/business/login')->with('error', 'Demo account not available');
            }

            // Ensure demo user is active
            if (! $demoUser->is_active) {
                $demoUser->is_active = true;
                $demoUser->save();
            }

            // Clear any existing sessions
            Auth::guard('portal')->logout();
            session()->flush();
            session()->regenerate();

            // Login as demo user
            Auth::guard('portal')->login($demoUser);

            // Set demo mode flag
            session(['demo_mode' => true]);

            // Get CSRF token
            $csrfToken = csrf_token();

            // Prepare auth data for React
            $authData = [
                'user' => [
                    'id' => $demoUser->id,
                    'name' => $demoUser->name,
                    'email' => $demoUser->email,
                    'company_id' => $demoUser->company_id,
                    'branch_id' => $demoUser->branch_id,
                    'is_demo' => true,
                ],
                'company' => $demoUser->company ? [
                    'id' => $demoUser->company->id,
                    'name' => $demoUser->company->name,
                ] : null,
                'branch' => $demoUser->branch ? [
                    'id' => $demoUser->branch->id,
                    'name' => $demoUser->branch->name,
                ] : null,
            ];

            // Set additional demo flags in session
            session(['demo_auth_data' => $authData]);

            // Create a custom view with demo initialization
            $html = view('portal.react-dashboard')->render();

            // Inject demo initialization script before closing body tag
            $demoScript = '<script src="/js/demo-init.js"></script>';
            $html = str_replace('</body>', $demoScript . '</body>', $html);

            return response($html);
        } catch (\Exception $e) {
            Log::error('Demo initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect('/business/login')->with('error', 'Unable to initialize demo mode');
        }
    }

    /**
     * API endpoint to verify demo auth status.
     */
    public function status(Request $request)
    {
        $user = Auth::guard('portal')->user();

        if (! $user || $user->email !== 'demo@askproai.de') {
            return response()->json([
                'authenticated' => false,
                'is_demo' => false,
            ]);
        }

        return response()->json([
            'authenticated' => true,
            'is_demo' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'branch_id' => $user->branch_id,
            ],
        ]);
    }
}
