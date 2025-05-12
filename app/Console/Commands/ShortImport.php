<?php
namespace App\Console\Commands;

use App\Models\Call;
use App\Models\Customer;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ShortImport extends Command
{
    protected $signature = 'import:calls {file}';
    protected $description = 'Importiert Anrufdaten';

    public function handle()
    {
        $file = fopen($this->argument('file'), 'r');
        $headers = array_flip(fgetcsv($file));
        $count = 0;

        while (($row = fgetcsv($file)) !== false) {
            try {
                $call = new Call([
                    'call_id' => $row[$headers['Call ID']],
                    'call_time' => Carbon::createFromFormat('m/d/Y H:i', $row[$headers['Time']]),
                    'call_duration' => $row[$headers['Call Duration']],
                    'type' => $row[$headers['Type']],
                    'cost' => (float)str_replace('$', '', $row[$headers['Cost']]),
                    'call_status' => $row[$headers['Call Status']],
                    'user_sentiment' => $row[$headers['User Sentiment']],
                    'successful' => $row[$headers['Call Successful']] === 'Successful'
                ]);
                $call->save();
                $count++;
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->info("$count Anrufe importiert");
    }
}
