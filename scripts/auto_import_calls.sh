#!/bin/bash
#
# Automatischer Import von Retell-Anrufen
# Läuft alle 5 Minuten per Cron
#

LOG_FILE="/var/www/api-gateway/storage/logs/auto_import.log"

echo "$(date '+%Y-%m-%d %H:%M:%S') - Starting auto import" >> $LOG_FILE

# PHP Script für Import
php -r '
require_once "/var/www/api-gateway/vendor/autoload.php";
$app = require_once "/var/www/api-gateway/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\RetellApiClient;
use Carbon\Carbon;

$client = new RetellApiClient();

// Get calls from last 10 minutes
$params = [
    "start_timestamp" => (Carbon::now()->subMinutes(10)->timestamp - 7200) * 1000,
    "end_timestamp" => Carbon::now()->timestamp * 1000,
    "limit" => 20
];

$calls = $client->getAllCalls($params);
$imported = 0;

if (is_array($calls)) {
    foreach ($calls as $call) {
        $exists = \App\Models\Call::where("retell_call_id", $call["call_id"])->exists();

        if (!$exists) {
            try {
                $callData = $client->getCallDetail($call["call_id"]);
                if ($callData) {
                    $client->syncCallToDatabase($callData);
                    $imported++;

                    $time = Carbon::createFromTimestampMs($call["start_timestamp"])->setTimezone("Europe/Berlin");
                    echo date("Y-m-d H:i:s") . " - Imported call: " . $call["call_id"] . " from " . $time->format("H:i:s") . "\n";
                }
            } catch (Exception $e) {
                echo date("Y-m-d H:i:s") . " - Error importing " . $call["call_id"] . ": " . $e->getMessage() . "\n";
            }
        }
    }
}

echo date("Y-m-d H:i:s") . " - Checked " . count($calls) . " calls, imported " . $imported . " new calls\n";
' >> $LOG_FILE 2>&1

echo "$(date '+%Y-%m-%d %H:%M:%S') - Auto import completed" >> $LOG_FILE
echo "---" >> $LOG_FILE