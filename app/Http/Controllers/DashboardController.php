<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistiken
        $stats = [
            'total_calls' => Call::count(),
            'success_rate' => Call::where('successful', true)->count() > 0 
                ? round((Call::where('successful', true)->count() / Call::count()) * 100, 1)
                : 0,
            'unique_customers' => Customer::count(),
            'avg_cost' => Call::avg('cost') ? round(Call::avg('cost'), 2) : 0,
        ];

        // Sentiment-Analyse
        $sentiments = [
            'Positive' => Call::where('user_sentiment', 'Positive')->count(),
            'Neutral' => Call::where('user_sentiment', 'Neutral')->count(),
            'Negative' => Call::where('user_sentiment', 'Negative')->count(),
        ];

        // Anrufe nach Tagen
        $callsByDay = DB::table('calls')
            ->selectRaw('DATE(call_time) as date, COUNT(*) as count')
            ->where('call_time', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Neueste Anrufe
        $latestCalls = Call::orderBy('call_time', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'stats',
            'sentiments',
            'callsByDay',
            'latestCalls'
        ));
    }

    public function customers()
    {
        $customers = Customer::orderBy('name')->paginate(15);
        return view('dashboard.customers', compact('customers'));
    }

    public function appointments()
    {
        $appointments = Appointment::with(['customer', 'service', 'staff'])
            ->orderBy('datum', 'desc')
            ->orderBy('uhrzeit', 'desc')
            ->paginate(15);
        return view('dashboard.appointments', compact('appointments'));
    }

    public function calls()
    {
        $calls = Call::orderBy('call_time', 'desc')
            ->paginate(15);
        return view('dashboard.calls', compact('calls'));
    }

    public function services() 
    {
        $services = Service::orderBy('name')->paginate(15);
        return view('dashboard.services', compact('services'));
    }

    public function staff()
    {
        $staff = Staff::orderBy('name')->paginate(15);
        return view('dashboard.staff', compact('staff'));
    }
}
