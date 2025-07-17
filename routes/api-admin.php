<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Api\AuthController;
use App\Http\Controllers\Admin\Api\DashboardController;
use App\Http\Controllers\Admin\Api\CompanyController;
use App\Http\Controllers\Admin\Api\UserController;
use App\Http\Controllers\Admin\Api\CallController;
use App\Http\Controllers\Admin\Api\AppointmentController;
use App\Http\Controllers\Admin\Api\CustomerController;
use App\Http\Controllers\Admin\Api\StaffController;
use App\Http\Controllers\Admin\Api\ServiceController;
use App\Http\Controllers\Admin\Api\BranchController;
use App\Http\Controllers\Admin\Api\SystemController;
use App\Http\Controllers\Admin\Api\IntegrationController;
use App\Http\Controllers\Admin\Api\TranslationController;
use App\Http\Controllers\Admin\Api\CustomerTimelineController;
use App\Http\Controllers\Admin\Api\BillingController;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| API routes for the React-based Admin Portal
| All routes are prefixed with /api/admin
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth:sanctum', \App\Http\Middleware\DisableTenantScopeForAdminApi::class])->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity']);
    Route::get('/dashboard/system-health', [DashboardController::class, 'systemHealth']);

    // Companies (Tenants)
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/companies/{company}', [CompanyController::class, 'show']);
    Route::put('/companies/{company}', [CompanyController::class, 'update']);
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy']);
    Route::post('/companies/{company}/activate', [CompanyController::class, 'activate']);
    Route::post('/companies/{company}/deactivate', [CompanyController::class, 'deactivate']);
    Route::post('/companies/{company}/sync-calcom', [CompanyController::class, 'syncCalcom']);
    Route::post('/companies/{company}/validate-api-keys', [CompanyController::class, 'validateApiKeys']);
    Route::get('/companies/{company}/event-types', [CompanyController::class, 'getEventTypes']);
    Route::get('/companies/{company}/working-hours', [CompanyController::class, 'getWorkingHours']);
    Route::post('/companies/{company}/working-hours', [CompanyController::class, 'updateWorkingHours']);
    Route::get('/companies/{company}/notification-settings', [CompanyController::class, 'getNotificationSettings']);
    Route::post('/companies/{company}/notification-settings', [CompanyController::class, 'updateNotificationSettings']);

    // Users (System Admins)
    Route::apiResource('users', UserController::class);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
    Route::post('/users/{user}/toggle-2fa', [UserController::class, 'toggle2FA']);

    // Calls
    Route::get('/calls/stats', [CallController::class, 'stats']); // Must be before resource route
    Route::apiResource('calls', CallController::class)->names('admin.calls');
    Route::get('/calls/{call}/transcript', [CallController::class, 'transcript']);
    Route::get('/calls/{call}/recording', [CallController::class, 'getRecording']);
    Route::post('/calls/{call}/share', [CallController::class, 'share']);
    Route::post('/calls/mark-non-billable', [CallController::class, 'markNonBillable']);
    Route::post('/calls/create-refund', [CallController::class, 'createRefund']);
    Route::post('/calls/bulk-delete', [CallController::class, 'bulkDelete']);

    // Appointments
    Route::get('/appointments/stats', [AppointmentController::class, 'stats']);
    Route::get('/appointments/quick-filters', [AppointmentController::class, 'quickFilters']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);
    Route::post('/appointments/bulk-action', [AppointmentController::class, 'bulkAction']);
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm']);
    Route::post('/appointments/{appointment}/complete', [AppointmentController::class, 'complete']);
    Route::post('/appointments/{appointment}/no-show', [AppointmentController::class, 'noShow']);
    Route::post('/appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn']);
    Route::post('/appointments/{appointment}/send-reminder', [AppointmentController::class, 'sendReminder']);
    Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule']);

    // Customers
    Route::get('/customers/stats', [CustomerController::class, 'stats']);
    Route::get('/customers/tags', [CustomerController::class, 'tags']);
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    Route::put('/customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
    Route::get('/customers/{customer}/history', [CustomerController::class, 'history']);
    Route::get('/customers/{customer}/timeline', [CustomerTimelineController::class, 'index']);
    Route::post('/customers/{customer}/notes', [CustomerTimelineController::class, 'addNote']);
    Route::get('/customers/{customer}/statistics', [CustomerTimelineController::class, 'statistics']);
    Route::get('/customers/{customer}/appointments', [CustomerTimelineController::class, 'appointments']);
    Route::get('/customers/{customer}/calls', [CustomerTimelineController::class, 'calls']);
    Route::get('/customers/{customer}/notes', [CustomerTimelineController::class, 'notes']);
    Route::delete('/notes/{note}', [CustomerTimelineController::class, 'deleteNote']);
    Route::get('/customers/{customer}/documents', [CustomerTimelineController::class, 'documents']);
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('/customers/{customer}/quick-booking', [CustomerController::class, 'quickBooking']);
    Route::post('/customers/{customer}/enable-portal', [CustomerController::class, 'enablePortal']);
    Route::post('/customers/{customer}/disable-portal', [CustomerController::class, 'disablePortal']);
    Route::post('/customers/{customer}/send-email', [CustomerController::class, 'sendEmail']);
    Route::post('/customers/{customer}/send-sms', [CustomerController::class, 'sendSms']);
    Route::post('/customers/{customer}/update-tags', [CustomerController::class, 'updateTags']);
    Route::post('/customers/{customer}/toggle-vip', [CustomerController::class, 'toggleVip']);
    Route::post('/customers/merge', [CustomerController::class, 'merge']);

    // Staff
    Route::apiResource('staff', StaffController::class)->names('admin.staff');
    Route::get('/staff/{staff}/availability', [StaffController::class, 'availability']);
    Route::post('/staff/{staff}/assign-services', [StaffController::class, 'assignServices']);

    // Services
    Route::apiResource('services', ServiceController::class)->names('admin.services');
    Route::post('/services/{service}/assign-staff', [ServiceController::class, 'assignStaff']);

    // Branches
    Route::apiResource('branches', BranchController::class)->names('admin.branches');
    Route::get('/branches/{branch}/working-hours', [BranchController::class, 'workingHours']);
    Route::post('/branches/{branch}/working-hours', [BranchController::class, 'updateWorkingHours']);

    // System
    Route::get('/system/health', [SystemController::class, 'health']);
    Route::get('/system/logs', [SystemController::class, 'logs']);
    Route::get('/system/queue-status', [SystemController::class, 'queueStatus']);
    Route::post('/system/clear-cache', [SystemController::class, 'clearCache']);

    // Integrations
    Route::get('/integrations/retell/status', [IntegrationController::class, 'retellStatus']);
    Route::post('/integrations/retell/sync', [IntegrationController::class, 'retellSync']);
    Route::get('/integrations/calcom/status', [IntegrationController::class, 'calcomStatus']);
    Route::post('/integrations/calcom/sync', [IntegrationController::class, 'calcomSync']);
    
    // Webhook monitoring
    Route::get('/webhooks/recent', [IntegrationController::class, 'recentWebhooks']);
    Route::get('/webhooks/stats', [IntegrationController::class, 'webhookStats']);
    
    // Translations
    Route::get('/translations/languages', [TranslationController::class, 'languages']);
    Route::get('/translations/{locale}', [TranslationController::class, 'getTranslations']);
    Route::post('/translations/translate', [TranslationController::class, 'translate']);
    Route::post('/translations/clear-cache', [TranslationController::class, 'clearCache']);
    
    // Billing
    Route::get('/billing/overview', [BillingController::class, 'overview']);
    Route::get('/billing/balances', [BillingController::class, 'balances']);
    Route::put('/billing/balances/{id}/settings', [BillingController::class, 'updateBalanceSettings']);
    Route::get('/billing/invoices', [BillingController::class, 'invoices']);
    Route::post('/billing/invoices/{id}/mark-paid', [BillingController::class, 'markInvoiceAsPaid']);
    Route::post('/billing/invoices/{id}/resend', [BillingController::class, 'resendInvoice']);
    Route::get('/billing/topups', [BillingController::class, 'topups']);
    Route::post('/billing/topups', [BillingController::class, 'createTopup']);
    Route::get('/billing/transactions', [BillingController::class, 'transactions']);
    Route::post('/billing/charges', [BillingController::class, 'createCharge']);
    Route::get('/billing/call-charges', [BillingController::class, 'callCharges']);
    Route::get('/billing/export-transactions', [BillingController::class, 'exportTransactions']);
});