<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index()
    {
        $dailyStats = DB::table('calls')
            ->selectRaw('DATE(call_time) as date, COUNT(*) as total, 
                         SUM(CASE WHEN successful = 1 THEN 1 ELSE 0 END) as successful')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();
            
        return view('reports.index', compact('dailyStats'));
    }
}
