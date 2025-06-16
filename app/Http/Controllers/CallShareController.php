<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Mail\CallRecordingMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;

class CallShareController extends Controller
{
    /**
     * Send call recording email
     *
     * @param Request $request
     * @param Call $call
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendCallEmail(Request $request, Call $call)
    {
        // Check if user has permission to access this call
        if (!Gate::allows('view', $call)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Validate the request
        $validated = $request->validate([
            'recipient_email' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000'
        ]);

        try {
            // Load call with all necessary relations
            $call->load([
                'customer',
                'company',
                'branch',
                'staff',
                'service',
                'appointment'
            ]);

            // Prepare email data
            $emailData = [
                'call' => $call,
                'subject' => $validated['subject'] ?? 'Call Recording - ' . $call->created_at->format('d.m.Y H:i'),
                'custom_message' => $validated['message'] ?? null,
                'sender_name' => auth()->user()->name,
                'sender_email' => auth()->user()->email
            ];

            // Send the email
            Mail::to($validated['recipient_email'])
                ->send(new CallRecordingMail($emailData));

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully to ' . $validated['recipient_email']
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Failed to send call recording email', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email. Please try again later.'
            ], 500);
        }
    }
}