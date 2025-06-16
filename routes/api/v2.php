<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V2\AppointmentController;
use App\Http\Controllers\API\V2\CustomerController;
use App\Http\Controllers\API\V2\CallController;
use App\Http\Controllers\API\V2\StaffController;
use App\Http\Controllers\API\V2\BranchController;
use App\Http\Controllers\API\V2\ServiceController;

/*
|--------------------------------------------------------------------------
| API V2 Routes
|--------------------------------------------------------------------------
|
| Modern RESTful API routes with improved structure and functionality
|
*/

Route::prefix('v2')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    
    // Appointments
    Route::prefix('appointments')->group(function () {
        Route::get('/', [AppointmentController::class, 'index']);
        Route::post('/', [AppointmentController::class, 'store']);
        Route::get('/search', [AppointmentController::class, 'search']);
        Route::get('/statistics', [AppointmentController::class, 'statistics']);
        Route::get('/available-slots', [AppointmentController::class, 'availableSlots']);
        Route::get('/{appointment}', [AppointmentController::class, 'show']);
        Route::put('/{appointment}', [AppointmentController::class, 'update']);
        Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel']);
        Route::post('/{appointment}/complete', [AppointmentController::class, 'complete']);
        Route::post('/{appointment}/no-show', [AppointmentController::class, 'noShow']);
    });

    // Customers
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/search', [CustomerController::class, 'search']);
        Route::get('/{customer}', [CustomerController::class, 'show']);
        Route::put('/{customer}', [CustomerController::class, 'update']);
        Route::delete('/{customer}', [CustomerController::class, 'destroy']);
        Route::get('/{customer}/appointments', [CustomerController::class, 'appointments']);
        Route::post('/{customer}/tags', [CustomerController::class, 'addTag']);
        Route::delete('/{customer}/tags/{tag}', [CustomerController::class, 'removeTag']);
    });

    // Calls
    Route::prefix('calls')->group(function () {
        Route::get('/', [CallController::class, 'index']);
        Route::get('/statistics', [CallController::class, 'statistics']);
        Route::get('/{call}', [CallController::class, 'show']);
        Route::get('/{call}/transcript', [CallController::class, 'transcript']);
        Route::post('/{call}/refresh', [CallController::class, 'refresh']);
    });

    // Staff
    Route::prefix('staff')->group(function () {
        Route::get('/', [StaffController::class, 'index']);
        Route::get('/{staff}', [StaffController::class, 'show']);
        Route::get('/{staff}/schedule', [StaffController::class, 'schedule']);
        Route::get('/{staff}/availability', [StaffController::class, 'availability']);
        Route::get('/{staff}/services', [StaffController::class, 'services']);
    });

    // Branches
    Route::prefix('branches')->group(function () {
        Route::get('/', [BranchController::class, 'index']);
        Route::get('/{branch}', [BranchController::class, 'show']);
        Route::get('/{branch}/staff', [BranchController::class, 'staff']);
        Route::get('/{branch}/services', [BranchController::class, 'services']);
    });

    // Services
    Route::prefix('services')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::get('/{service}', [ServiceController::class, 'show']);
        Route::get('/{service}/staff', [ServiceController::class, 'staff']);
    });
});

// Public endpoints (no auth required)
Route::prefix('v2/public')->middleware(['throttle:public'])->group(function () {
    // Public availability check
    Route::get('/availability', [AppointmentController::class, 'publicAvailability']);
    
    // Webhook endpoints with signature verification
    Route::post('/webhooks/retell', [App\Http\Controllers\API\V2\WebhookController::class, 'retell'])
        ->middleware('verify.retell.signature');
    
    Route::post('/webhooks/calcom', [App\Http\Controllers\API\V2\WebhookController::class, 'calcom'])
        ->middleware('calcom.signature');
});