<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    public function exportCalls()
    {
        $calls = DB::table('calls')
            ->select('call_id', 'call_time', 'call_duration', 'type', 
                    'call_status', 'user_sentiment', 'successful', 'cost')
            ->orderBy('call_time', 'desc')
            ->get();
            
        $csv = $this->arrayToCsv($calls->toArray());
        
        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="anrufe.csv"',
        ]);
    }
    
    private function arrayToCsv($data)
    {
        if (empty($data)) return '';
        
        $csv = fopen('php://temp', 'r+');
        
        // Header
        fputcsv($csv, array_keys((array)$data[0]));
        
        // Rows
        foreach ($data as $row) {
            fputcsv($csv, (array)$row);
        }
        
        rewind($csv);
        $output = stream_get_contents($csv);
        fclose($csv);
        
        return $output;
    }
}
