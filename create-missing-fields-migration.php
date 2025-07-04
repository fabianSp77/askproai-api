<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== CREATE MISSING FIELDS MIGRATION ===\n";
echo str_repeat("=", 50) . "\n\n";

// Check existing columns
$existingColumns = Schema::getColumnListing('calls');
echo "Existing columns: " . count($existingColumns) . "\n\n";

// Define required columns
$requiredColumns = [
    'agent_name' => ['type' => 'string', 'nullable' => true, 'comment' => 'Full name of the AI agent'],
    'agent_version' => ['type' => 'integer', 'nullable' => true, 'comment' => 'Version of the AI agent'],
    'urgency_level' => ['type' => 'string', 'nullable' => true, 'comment' => 'Call urgency: high/medium/low'],
    'no_show_count' => ['type' => 'integer', 'default' => 0, 'comment' => 'Previous no-shows'],
    'reschedule_count' => ['type' => 'integer', 'default' => 0, 'comment' => 'Number of reschedules'],
    'first_visit' => ['type' => 'boolean', 'nullable' => true, 'comment' => 'Is first visit'],
    'insurance_type' => ['type' => 'string', 'nullable' => true, 'comment' => 'Type of insurance'],
    'insurance_company' => ['type' => 'string', 'nullable' => true, 'comment' => 'Insurance provider'],
    'custom_analysis_data' => ['type' => 'json', 'nullable' => true, 'comment' => 'All custom analysis fields'],
    'call_summary' => ['type' => 'text', 'nullable' => true, 'comment' => 'AI-generated call summary'],
    'llm_token_usage' => ['type' => 'json', 'nullable' => true, 'comment' => 'Token usage statistics']
];

// Check which columns are missing
$missingColumns = [];
foreach ($requiredColumns as $column => $config) {
    if (!in_array($column, $existingColumns)) {
        $missingColumns[$column] = $config;
    }
}

if (empty($missingColumns)) {
    echo "✅ All required columns already exist!\n";
} else {
    echo "Missing columns:\n";
    foreach ($missingColumns as $column => $config) {
        echo "  - $column (" . $config['type'] . ")\n";
    }
    
    // Generate migration content
    $timestamp = date('Y_m_d_His');
    $className = 'AddRetellDataFieldsToCallsTable';
    $filename = $timestamp . '_add_retell_data_fields_to_calls_table.php';
    
    $migrationContent = "<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calls', function (Blueprint \$table) {\n";
    
    foreach ($missingColumns as $column => $config) {
        $migrationContent .= "            ";
        
        switch ($config['type']) {
            case 'string':
                $migrationContent .= "\$table->string('$column')";
                break;
            case 'text':
                $migrationContent .= "\$table->text('$column')";
                break;
            case 'integer':
                $migrationContent .= "\$table->integer('$column')";
                break;
            case 'boolean':
                $migrationContent .= "\$table->boolean('$column')";
                break;
            case 'json':
                $migrationContent .= "\$table->json('$column')";
                break;
        }
        
        if (isset($config['nullable']) && $config['nullable']) {
            $migrationContent .= "->nullable()";
        }
        
        if (isset($config['default'])) {
            $migrationContent .= "->default(" . var_export($config['default'], true) . ")";
        }
        
        if (isset($config['comment'])) {
            $migrationContent .= "->comment('" . addslashes($config['comment']) . "')";
        }
        
        $migrationContent .= ";\n";
    }
    
    $migrationContent .= "        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint \$table) {\n";
    
    foreach ($missingColumns as $column => $config) {
        $migrationContent .= "            \$table->dropColumn('$column');\n";
    }
    
    $migrationContent .= "        });
    }
};";
    
    // Save migration file
    $migrationPath = __DIR__ . '/database/migrations/' . $filename;
    file_put_contents($migrationPath, $migrationContent);
    
    echo "\n✅ Migration created: $filename\n";
    echo "\nRun the migration with:\n";
    echo "php artisan migrate\n";
}

// Also check if existing JSON columns need to be converted
echo "\n\n=== JSON COLUMN CONVERSION CHECK ===\n";
echo str_repeat("-", 50) . "\n";

$textColumnsToConvert = [
    'analysis' => 'Call analysis data',
    'latency_metrics' => 'Performance metrics',
    'cost_breakdown' => 'Cost details',
    'llm_usage' => 'LLM token usage',
    'transcript_object' => 'Structured transcript',
    'transcript_with_tools' => 'Transcript with tool calls',
    'retell_dynamic_variables' => 'Dynamic variables',
    'retell_llm_dynamic_variables' => 'LLM dynamic variables',
    'custom_sip_headers' => 'SIP headers',
    'metadata' => 'Call metadata',
    'webhook_data' => 'Webhook payload',
    'raw_data' => 'Complete raw data'
];

foreach ($textColumnsToConvert as $column => $description) {
    if (in_array($column, $existingColumns)) {
        $type = Schema::getColumnType('calls', $column);
        if ($type !== 'json') {
            echo "⚠️ $column is $type, should be JSON ($description)\n";
        } else {
            echo "✅ $column is already JSON\n";
        }
    }
}