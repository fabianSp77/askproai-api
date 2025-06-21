<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\GdprRequest;
use App\Services\GdprService;
use App\Services\CookieConsentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PrivacyController extends Controller
{
    protected GdprService $gdprService;
    protected CookieConsentService $cookieService;

    public function __construct(GdprService $gdprService, CookieConsentService $cookieService)
    {
        $this->gdprService = $gdprService;
        $this->cookieService = $cookieService;
    }

    /**
     * Show privacy settings page
     */
    public function index()
    {
        $customer = auth('customer')->user();
        $cookieConsent = $this->cookieService->getCurrentConsent();
        $cookieCategories = $this->cookieService->getCookieCategories();
        
        $gdprRequests = GdprRequest::where('customer_id', $customer->id)
            ->latest()
            ->paginate(10);

        return view('portal.privacy.index', compact(
            'customer',
            'cookieConsent',
            'cookieCategories',
            'gdprRequests'
        ));
    }

    /**
     * Update cookie consent preferences
     */
    public function updateCookieConsent(Request $request)
    {
        $validated = $request->validate([
            'functional_cookies' => 'boolean',
            'analytics_cookies' => 'boolean',
            'marketing_cookies' => 'boolean',
        ]);

        $this->cookieService->saveConsent($validated);

        return redirect()->route('portal.privacy')
            ->with('success', __('Ihre Cookie-Einstellungen wurden aktualisiert.'));
    }

    /**
     * Withdraw all cookie consent
     */
    public function withdrawCookieConsent()
    {
        $this->cookieService->withdrawConsent();

        return redirect()->route('portal.privacy')
            ->with('success', __('Ihre Cookie-Einwilligung wurde zurückgezogen.'));
    }

    /**
     * Request data export
     */
    public function requestDataExport(Request $request)
    {
        $customer = auth('customer')->user();

        // Check if there's already a pending request
        $pendingRequest = GdprRequest::where('customer_id', $customer->id)
            ->where('type', 'export')
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($pendingRequest) {
            return redirect()->route('portal.privacy')
                ->with('warning', __('Sie haben bereits eine ausstehende Datenanfrage.'));
        }

        $gdprRequest = GdprRequest::create([
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'type' => 'export',
            'reason' => $request->input('reason'),
        ]);

        Log::info('GDPR data export requested', [
            'customer_id' => $customer->id,
            'request_id' => $gdprRequest->id,
        ]);

        // Process immediately for small datasets, queue for larger ones
        if ($customer->appointments()->count() < 100) {
            $this->processDataExport($gdprRequest);
        } else {
            // Queue the job for processing
            // ProcessGdprExportJob::dispatch($gdprRequest);
        }

        return redirect()->route('portal.privacy')
            ->with('success', __('Ihre Datenanfrage wurde eingereicht. Sie erhalten eine E-Mail, sobald Ihre Daten bereit sind.'));
    }

    /**
     * Request data deletion
     */
    public function requestDataDeletion(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'confirm' => 'required|accepted',
        ]);

        $customer = auth('customer')->user();

        $gdprRequest = GdprRequest::create([
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'type' => 'deletion',
            'reason' => $validated['reason'],
        ]);

        Log::warning('GDPR data deletion requested', [
            'customer_id' => $customer->id,
            'request_id' => $gdprRequest->id,
        ]);

        // Send notification to admin for manual review
        // Notification::send($customer->company->admins, new GdprDeletionRequestNotification($gdprRequest));

        return redirect()->route('portal.privacy')
            ->with('success', __('Ihr Löschantrag wurde eingereicht. Ein Administrator wird Ihren Antrag prüfen.'));
    }

    /**
     * Download exported data
     */
    public function downloadExport(GdprRequest $gdprRequest)
    {
        $customer = auth('customer')->user();

        // Verify the request belongs to the authenticated customer
        if ($gdprRequest->customer_id !== $customer->id) {
            abort(403);
        }

        if ($gdprRequest->status !== 'completed' || !$gdprRequest->export_file_path) {
            abort(404);
        }

        $path = $gdprRequest->export_file_path;
        
        if (!Storage::exists($path)) {
            abort(404);
        }

        Log::info('GDPR data export downloaded', [
            'customer_id' => $customer->id,
            'request_id' => $gdprRequest->id,
        ]);

        return Storage::download($path, 'meine-daten-' . now()->format('Y-m-d') . '.zip');
    }

    /**
     * Process data export (immediate processing for small datasets)
     */
    protected function processDataExport(GdprRequest $gdprRequest): void
    {
        try {
            $gdprRequest->markAsProcessing();
            
            $customer = $gdprRequest->customer;
            $exportPath = $this->gdprService->createExportFile($customer);
            
            $gdprRequest->markAsCompleted([
                'export_file_path' => $exportPath,
            ]);

            // Send notification with download link
            // $customer->notify(new GdprExportReadyNotification($gdprRequest));
            
        } catch (\Exception $e) {
            Log::error('GDPR export failed', [
                'request_id' => $gdprRequest->id,
                'error' => $e->getMessage(),
            ]);
            
            $gdprRequest->markAsRejected('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Show cookie policy page
     */
    public function cookiePolicy()
    {
        $cookieCategories = $this->cookieService->getCookieCategories();
        return view('portal.privacy.cookie-policy', compact('cookieCategories'));
    }

    /**
     * Show privacy policy page
     */
    public function privacyPolicy()
    {
        return view('portal.privacy.privacy-policy');
    }
}