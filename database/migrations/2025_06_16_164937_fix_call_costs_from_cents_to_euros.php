<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\CurrencyConverter;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Korrigiere alle bestehenden Kosten, die fälschlicherweise als Euro statt Cents gespeichert wurden
        DB::table('calls')
            ->whereNotNull('cost')
            ->where('cost', '>', 1) // Werte über 1 Euro sind wahrscheinlich in Cents
            ->chunkById(100, function ($calls) {
                foreach ($calls as $call) {
                    // Wenn der cost-Wert über 1 liegt, ist es wahrscheinlich in Cents
                    if ($call->cost > 1) {
                        $euroValue = CurrencyConverter::centsToEuros($call->cost);
                        
                        DB::table('calls')
                            ->where('id', $call->id)
                            ->update([
                                'cost' => $euroValue,
                                'retell_cost' => $call->cost / 100 // Dollar-Wert speichern
                            ]);
                    }
                    
                    // Update cost_breakdown wenn vorhanden
                    if ($call->cost_breakdown) {
                        $breakdown = json_decode($call->cost_breakdown, true);
                        if ($breakdown && is_array($breakdown)) {
                            $formattedBreakdown = CurrencyConverter::formatCostBreakdown($breakdown);
                            
                            DB::table('calls')
                                ->where('id', $call->id)
                                ->update([
                                    'cost_breakdown' => json_encode($formattedBreakdown)
                                ]);
                        }
                    }
                }
            });
            
        // Log die Korrektur
        \Log::info('Call costs corrected from cents to euros', [
            'total_calls_checked' => DB::table('calls')->whereNotNull('cost')->count(),
            'calls_corrected' => DB::table('calls')->whereNotNull('cost')->where('cost', '>', 1)->count()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Diese Migration kann nicht rückgängig gemacht werden,
        // da wir nicht unterscheiden können zwischen korrekten und unkorrekten Werten
        \Log::warning('Cannot reverse cost correction migration - manual intervention required');
    }
};