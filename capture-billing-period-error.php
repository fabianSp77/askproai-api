<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Set detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a custom error handler to capture any issues
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "PHP Error [{$errno}]: {$errstr}\n";
    echo "File: {$errfile}:{$errline}\n\n";
    return true;
});

// Set exception handler
set_exception_handler(function($e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
});

// Set auth
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($user) {
    auth()->login($user);
    if ($user->company_id) {
        app()->instance('current_company_id', $user->company_id);
    }
}

try {
    echo "Testing BillingPeriod edit page components...\n\n";
    
    // Load the billing period
    $billingPeriod = \App\Models\BillingPeriod::find(2);
    if (!$billingPeriod) {
        throw new Exception("BillingPeriod not found");
    }
    
    // Test the edit page class
    $editPageClass = \App\Filament\Admin\Resources\BillingPeriodResource\Pages\EditBillingPeriod::class;
    
    // Create a mock request
    $request = new \Illuminate\Http\Request();
    $request->merge(['record' => 2]);
    app()->instance('request', $request);
    
    // Try to instantiate the page
    echo "1. Instantiating edit page...\n";
    try {
        $page = new $editPageClass();
        echo "   ✓ Page instantiated\n";
        
        // Set the record
        $page->record = $billingPeriod;
        echo "   ✓ Record set\n";
        
    } catch (\Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
        throw $e;
    }
    
    echo "\n2. Testing form generation...\n";
    
    // Test form schema static method directly
    try {
        $formClass = app(\Filament\Forms\Form::class);
        $schema = \App\Filament\Admin\Resources\BillingPeriodResource::form($formClass);
        echo "   ✓ Form schema created\n";
    } catch (\Exception $e) {
        echo "   ✗ Form error: " . $e->getMessage() . "\n";
        echo "   Stack: " . $e->getTraceAsString() . "\n";
    }
    
    echo "\n3. Checking for missing classes or methods...\n";
    
    // Check if all form components exist
    $components = [
        'Filament\Forms\Components\Tabs',
        'Filament\Forms\Components\Section',
        'Filament\Forms\Components\Grid',
        'Filament\Forms\Components\Select',
        'Filament\Forms\Components\DatePicker',
        'Filament\Forms\Components\TextInput',
        'Filament\Forms\Components\Toggle',
        'Filament\Forms\Components\DateTimePicker',
    ];
    
    foreach ($components as $component) {
        if (class_exists($component)) {
            echo "   ✓ {$component}\n";
        } else {
            echo "   ✗ {$component} NOT FOUND\n";
        }
    }
    
    echo "\nAll tests completed.\n";
    
} catch (\Throwable $e) {
    echo "\nCRITICAL ERROR:\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    $trace = $e->getTrace();
    foreach (array_slice($trace, 0, 10) as $i => $frame) {
        echo "#{$i} ";
        if (isset($frame['file'])) {
            echo $frame['file'] . ":" . $frame['line'];
        }
        if (isset($frame['function'])) {
            echo " " . $frame['function'] . "()";
        }
        echo "\n";
    }
}