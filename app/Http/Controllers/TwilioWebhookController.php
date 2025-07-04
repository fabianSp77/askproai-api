<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TwilioWebhookController extends Controller
{
    /**
     * Handle Twilio status callback
     */
    public function statusCallback(Request $request)
    {
        try {
            // Log the webhook
            Log::info('Twilio status callback received', $request->all());
            
            $messageSid = $request->input('MessageSid');
            $messageStatus = $request->input('MessageStatus');
            $errorCode = $request->input('ErrorCode');
            $errorMessage = $request->input('ErrorMessage');
            $price = $request->input('Price');
            $priceUnit = $request->input('PriceUnit');
            
            // Update the message log
            $updated = DB::table('sms_message_logs')
                ->where('twilio_sid', $messageSid)
                ->update([
                    'status' => $messageStatus,
                    'price' => $price,
                    'price_unit' => $priceUnit,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'delivered_at' => in_array($messageStatus, ['delivered', 'sent']) ? now() : null,
                    'failed_at' => in_array($messageStatus, ['failed', 'undelivered']) ? now() : null,
                    'updated_at' => now()
                ]);
            
            if (!$updated) {
                Log::warning('Twilio status callback: Message not found', [
                    'message_sid' => $messageSid,
                    'status' => $messageStatus
                ]);
            }
            
            // Track metrics
            if ($messageStatus === 'failed' || $messageStatus === 'undelivered') {
                Log::error('Twilio message delivery failed', [
                    'message_sid' => $messageSid,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage
                ]);
            }
            
            return response('OK', 200);
            
        } catch (\Exception $e) {
            Log::error('Twilio status callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response('Error', 500);
        }
    }
    
    /**
     * Handle incoming SMS/WhatsApp messages
     */
    public function incomingMessage(Request $request)
    {
        try {
            Log::info('Twilio incoming message received', $request->all());
            
            $from = $request->input('From');
            $to = $request->input('To');
            $body = $request->input('Body');
            $messageSid = $request->input('MessageSid');
            
            // Determine channel
            $channel = 'sms';
            if (str_starts_with($from, 'whatsapp:')) {
                $channel = 'whatsapp';
                $from = str_replace('whatsapp:', '', $from);
            }
            if (str_starts_with($to, 'whatsapp:')) {
                $to = str_replace('whatsapp:', '', $to);
            }
            
            // Find customer by phone number
            $customer = DB::table('customers')
                ->where('phone', 'LIKE', '%' . substr($from, -10))
                ->first();
            
            // Log the incoming message
            DB::table('sms_message_logs')->insert([
                'company_id' => $customer->company_id ?? null,
                'customer_id' => $customer->id ?? null,
                'channel' => $channel,
                'to' => $to,
                'from' => $from,
                'message' => $body,
                'twilio_sid' => $messageSid,
                'status' => 'received',
                'metadata' => json_encode([
                    'direction' => 'inbound',
                    'raw_request' => $request->all()
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Process message content
            $this->processIncomingMessage($from, $body, $channel, $customer);
            
            // Send empty response (no auto-reply)
            return response('', 200)
                ->header('Content-Type', 'text/xml');
            
        } catch (\Exception $e) {
            Log::error('Twilio incoming message error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response('Error', 500);
        }
    }
    
    /**
     * Process incoming message content
     */
    protected function processIncomingMessage(string $from, string $body, string $channel, $customer)
    {
        $bodyLower = strtolower(trim($body));
        
        // Check for appointment cancellation keywords
        if (in_array($bodyLower, ['absage', 'absagen', 'cancel', 'stop', 'stornieren'])) {
            $this->handleAppointmentCancellation($from, $customer);
            return;
        }
        
        // Check for confirmation keywords
        if (in_array($bodyLower, ['ja', 'yes', 'ok', 'bestätigen', 'confirm'])) {
            $this->handleAppointmentConfirmation($from, $customer);
            return;
        }
        
        // Log unhandled messages for manual review
        Log::info('Unhandled incoming message', [
            'from' => $from,
            'body' => $body,
            'channel' => $channel,
            'customer_id' => $customer->id ?? null
        ]);
    }
    
    /**
     * Handle appointment cancellation via SMS/WhatsApp
     */
    protected function handleAppointmentCancellation(string $from, $customer)
    {
        if (!$customer) {
            Log::warning('Cancellation attempt from unknown number', ['from' => $from]);
            return;
        }
        
        // Find next upcoming appointment
        $appointment = DB::table('appointments')
            ->where('customer_id', $customer->id)
            ->where('starts_at', '>', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('starts_at')
            ->first();
        
        if ($appointment) {
            // Update appointment status
            DB::table('appointments')
                ->where('id', $appointment->id)
                ->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => 'Customer cancelled via SMS/WhatsApp',
                    'cancelled_at' => now(),
                    'updated_at' => now()
                ]);
            
            Log::info('Appointment cancelled via SMS/WhatsApp', [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'from' => $from
            ]);
            
            // TODO: Send cancellation confirmation
        }
    }
    
    /**
     * Handle appointment confirmation via SMS/WhatsApp
     */
    protected function handleAppointmentConfirmation(string $from, $customer)
    {
        if (!$customer) {
            Log::warning('Confirmation attempt from unknown number', ['from' => $from]);
            return;
        }
        
        // Find next upcoming appointment that needs confirmation
        $appointment = DB::table('appointments')
            ->where('customer_id', $customer->id)
            ->where('starts_at', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('starts_at')
            ->first();
        
        if ($appointment) {
            // Update appointment status
            DB::table('appointments')
                ->where('id', $appointment->id)
                ->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'updated_at' => now()
                ]);
            
            Log::info('Appointment confirmed via SMS/WhatsApp', [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'from' => $from
            ]);
            
            // TODO: Send confirmation acknowledgment
        }
    }
}