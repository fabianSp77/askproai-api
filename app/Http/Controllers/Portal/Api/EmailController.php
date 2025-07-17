<?php

namespace App\Http\Controllers\Portal\Api;

use App\Models\Call;
use App\Mail\CallSummaryEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailController extends BaseApiController
{
    /**
     * Send email directly without queue
     */
    public function sendDirect(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|email',
            'subject' => 'required|string|max:255',
            'html_content' => 'nullable|string',
            'include_options' => 'required|array',
            'include_options.summary' => 'boolean',
            'include_options.transcript' => 'boolean',
            'include_options.customerInfo' => 'boolean',
            'include_options.appointmentInfo' => 'boolean',
            'include_options.actionItems' => 'boolean',
            'include_options.attachCSV' => 'boolean',
            'include_options.attachRecording' => 'boolean',
        ]);

        try {
            $company = $this->getCompany();
            $user = $this->getCurrentUser();
            
            if (!$company || !$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $call = Call::where('company_id', $company->id)
                ->where('id', $request->call_id)
                ->firstOrFail();
            
            // Set company context
            app()->instance('current_company_id', $call->company_id);

            $successCount = 0;
            $errors = [];

            // Send emails directly (not queued)
            foreach ($request->recipients as $recipient) {
                try {
                    // Create custom email with provided HTML content
                    Mail::to($recipient)->send(new \App\Mail\CustomCallSummaryEmail(
                        $call,
                        $request->subject,
                        $request->html_content ?? '',
                        $request->include_options
                    ));
                    
                    $successCount++;
                    
                    // Log activity
                    \App\Models\CallActivity::log($call, \App\Models\CallActivity::TYPE_EMAIL_SENT, 'E-Mail versendet', [
                        'user_id' => $user->id,
                        'is_system' => false,
                        'description' => "E-Mail direkt an $recipient versendet",
                        'metadata' => [
                            'recipient' => $recipient,
                            'subject' => $request->subject,
                            'sent_by' => $user->name,
                            'sent_at' => now()->toIso8601String(),
                            'include_options' => $request->include_options
                        ]
                    ]);
                } catch (\Exception $e) {
                    Log::error('Direct email send failed', [
                        'recipient' => $recipient,
                        'error' => $e->getMessage()
                    ]);
                    $errors[] = [
                        'recipient' => $recipient,
                        'error' => $e->getMessage()
                    ];
                }
            }

            if ($successCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alle E-Mails sind fehlgeschlagen',
                    'errors' => $errors
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => "$successCount von " . count($request->recipients) . " E-Mails erfolgreich versendet",
                'sent_count' => $successCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Email send error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Versenden der E-Mail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download CSV for a call
     */
    public function downloadCsv(Request $request, $callId)
    {
        try {
            $company = $this->getCompany();
            $user = $this->getCurrentUser();
            
            if (!$company || !$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $call = Call::where('company_id', $company->id)
                ->where('id', $callId)
                ->firstOrFail();
            
            // Set company context
            app()->instance('current_company_id', $call->company_id);
            
            // Check permissions
            if (!session('is_admin_viewing') && !$this->userCanAccessCall($call, $user)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $exportService = app(\App\Services\CallExportService::class);
            $csvContent = $exportService->exportSingleCall($call);
            
            $filename = 'anruf-' . $call->id . '-' . date('Y-m-d') . '.csv';
            
            return response($csvContent, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);

        } catch (\Exception $e) {
            Log::error('CSV download error', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Herunterladen der CSV-Datei',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate email preview
     */
    public function preview(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
            'subject' => 'required|string|max:255',
            'html_content' => 'nullable|string',
            'include_options' => 'required|array',
        ]);

        try {
            $user = Auth::guard('portal')->user();
            $call = Call::findOrFail($request->call_id);
            
            // Set company context
            app()->instance('current_company_id', $call->company_id);
            
            // Check permissions
            if (!session('is_admin_viewing') && !$this->userCanAccessCall($call, $user)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Create the email instance to get the rendered HTML
            $email = new \App\Mail\CustomCallSummaryEmail(
                $call,
                $request->subject,
                $request->html_content ?? '',
                $request->include_options
            );
            
            // Render the email view
            $content = $email->content();
            $html = view($content->view, $content->with)->render();
            
            return response()->json([
                'success' => true,
                'html' => $html
            ]);

        } catch (\Exception $e) {
            Log::error('Email preview error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Generieren der Vorschau',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user can access the call
     */
    private function userCanAccessCall($call, $user)
    {
        if (!$user) {
            return false;
        }

        if ($user->hasPermission('calls.view_all')) {
            return true;
        }

        if ($call->callPortalData && $call->callPortalData->assigned_to === $user->id) {
            return true;
        }

        if ($user->hasPermission('calls.view_team') && !$call->callPortalData->assigned_to) {
            return true;
        }

        return false;
    }
}