<?php

namespace App\Services\Examples;

use App\Services\Traits\LogsStructured;
use App\Facades\StructuredLog;
use App\Services\CalcomV2Service;
use App\Services\AppointmentBookingService;

/**
 * Example implementations showing how to use the StructuredLogger
 */
class StructuredLoggerExample
{
    use LogsStructured;

    /**
     * Example 1: Simple API call logging
     */
    public function exampleApiCall()
    {
        // Using the trait's helper method
        $apiLogger = $this->logApiCall('/v2/slots/available', 'GET', [
            'startTime' => '2025-01-01T10:00:00Z',
            'endTime' => '2025-01-01T18:00:00Z',
            'eventTypeId' => 123
        ]);

        try {
            // Make the actual API call
            $response = Http::get('https://api.cal.com/v2/slots/available');
            
            if ($response->successful()) {
                $apiLogger->success($response);
            } else {
                $apiLogger->failure($response, 'API returned error status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $apiLogger->exception($e);
        }
    }

    /**
     * Example 2: Booking flow logging
     */
    public function exampleBookingFlow($customerId, $serviceId, $staffId, $requestedTime)
    {
        $correlationId = StructuredLog::getCorrelationId();
        
        // Step 1: Start booking process
        StructuredLog::logBookingFlow('booking_initiated', [
            'customer_id' => $customerId,
            'service_id' => $serviceId,
            'staff_id' => $staffId,
            'requested_time' => $requestedTime,
        ]);

        try {
            // Step 2: Check availability
            StructuredLog::logBookingFlow('checking_availability', [
                'customer_id' => $customerId,
                'staff_id' => $staffId,
                'requested_time' => $requestedTime,
            ]);

            $isAvailable = $this->checkAvailability($staffId, $requestedTime);

            if (!$isAvailable) {
                StructuredLog::logBookingFlow('booking_failed_unavailable', [
                    'customer_id' => $customerId,
                    'reason' => 'Time slot not available',
                ]);
                return false;
            }

            // Step 3: Create appointment
            StructuredLog::logBookingFlow('creating_appointment', [
                'customer_id' => $customerId,
                'service_id' => $serviceId,
                'staff_id' => $staffId,
            ]);

            $appointment = $this->createAppointment($customerId, $serviceId, $staffId, $requestedTime);

            // Step 4: Success
            StructuredLog::logBookingFlow('booking_completed', [
                'customer_id' => $customerId,
                'appointment_id' => $appointment->id,
                'confirmation_number' => $appointment->confirmation_number,
            ]);

            return $appointment;

        } catch (\Exception $e) {
            StructuredLog::logBookingFlow('booking_error', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
            
            StructuredLog::logError($e, [
                'booking_context' => [
                    'customer_id' => $customerId,
                    'service_id' => $serviceId,
                    'staff_id' => $staffId,
                ]
            ]);
            
            throw $e;
        }
    }

    /**
     * Example 3: Using the facade directly
     */
    public function exampleDirectUsage()
    {
        // Log with additional context for this operation
        $logger = StructuredLog::withAdditionalContext([
            'operation' => 'user_import',
            'batch_id' => 'batch_123',
        ]);

        $logger->info('Starting user import batch');

        try {
            // Do some work...
            $logger->success('User import completed', [
                'users_imported' => 150,
                'duration_seconds' => 45.2,
            ]);
        } catch (\Exception $e) {
            $logger->failure('User import failed', [
                'error' => $e->getMessage(),
                'users_processed' => 75,
            ]);
        }
    }

    /**
     * Example 4: Performance logging
     */
    public function examplePerformanceLogging()
    {
        $startTime = microtime(true);
        
        // Do some intensive operation
        $this->processLargeDataset();
        
        $duration = microtime(true) - $startTime;
        
        StructuredLog::logPerformance('large_dataset_processing', $duration, [
            'records_processed' => 10000,
            'records_per_second' => 10000 / $duration,
            'peak_memory_mb' => memory_get_peak_usage(true) / 1024 / 1024,
        ]);
    }

    /**
     * Example 5: Security event logging
     */
    public function exampleSecurityLogging($userId, $action)
    {
        StructuredLog::logSecurity('unauthorized_access_attempt', 'warning', [
            'user_id' => $userId,
            'attempted_action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
        ]);
    }

    /**
     * Example 6: Webhook logging
     */
    public function exampleWebhookLogging($payload)
    {
        StructuredLog::logWebhook('retell', 'call_ended', $payload, [
            'signature_valid' => true,
            'processing_time_ms' => 125,
        ]);
    }

    // Dummy methods for the examples
    private function checkAvailability($staffId, $time) { return true; }
    private function createAppointment($customerId, $serviceId, $staffId, $time) { 
        return (object)['id' => 'apt_123', 'confirmation_number' => 'CONF123'];
    }
    private function processLargeDataset() { sleep(1); }
}

/**
 * Example: Updating CalcomV2Service to use StructuredLogger
 */
class CalcomV2ServiceWithLogging extends CalcomV2Service
{
    use LogsStructured;

    public function getAvailableSlots($startTime, $endTime, $eventTypeId)
    {
        $apiLogger = $this->logApiCall('/v2/slots/available', 'GET', [
            'startTime' => $startTime,
            'endTime' => $endTime,
            'eventTypeId' => $eventTypeId,
        ]);

        try {
            $response = $this->circuitBreaker->call('calcom', function() use ($startTime, $endTime, $eventTypeId) {
                return $this->httpWithRetry()
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->get($this->baseUrlV2 . '/slots/available', [
                        'apiKey' => $this->apiKey,
                        'startTime' => $startTime,
                        'endTime' => $endTime,
                        'eventTypeId' => $eventTypeId,
                    ]);
            });

            if ($response->successful()) {
                $apiLogger->success($response);
                return $response->json();
            }

            $apiLogger->failure($response, 'Cal.com API returned error status: ' . $response->status());
            throw new \Exception("Cal.com getAvailableSlots failed");
            
        } catch (\Exception $e) {
            $apiLogger->exception($e);
            throw $e;
        }
    }
}

/**
 * Example: Updating AppointmentBookingService to use StructuredLogger
 */
class AppointmentBookingServiceWithLogging extends AppointmentBookingService
{
    use LogsStructured;

    public function bookAppointment(array $data)
    {
        $this->logBookingStep('appointment_booking_started', [
            'customer_phone' => $data['customer_phone'] ?? null,
            'service_name' => $data['service_name'] ?? null,
            'requested_date' => $data['date'] ?? null,
        ]);

        try {
            // Customer lookup/creation
            $this->logBookingStep('customer_lookup', [
                'phone' => $data['customer_phone'],
            ]);
            
            $customer = $this->findOrCreateCustomer($data);
            
            $this->logBookingStep('customer_resolved', [
                'customer_id' => $customer->id,
                'is_new' => $customer->wasRecentlyCreated,
            ]);

            // Service resolution
            $this->logBookingStep('service_resolution', [
                'service_name' => $data['service_name'],
            ]);
            
            $service = $this->resolveService($data);
            
            // Availability check
            $this->logBookingStep('availability_check', [
                'date' => $data['date'],
                'time' => $data['time'],
                'service_id' => $service->id,
            ]);

            // Create appointment
            $appointment = $this->createAppointment($customer, $service, $data);
            
            $this->logBookingStep('appointment_created', [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'start_time' => $appointment->start_time,
            ]);

            // Sync to Cal.com
            $this->logBookingStep('calcom_sync_started', [
                'appointment_id' => $appointment->id,
            ]);
            
            $this->syncToCalcom($appointment);
            
            $this->logBookingStep('calcom_sync_completed', [
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $appointment->calcom_booking_id,
            ]);

            $this->logSuccess('Appointment booked successfully', [
                'appointment_id' => $appointment->id,
                'confirmation_number' => $appointment->confirmation_number,
            ]);

            return $appointment;

        } catch (\Exception $e) {
            $this->logBookingStep('appointment_booking_failed', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
            
            $this->logError($e, [
                'booking_data' => $data,
            ]);
            
            throw $e;
        }
    }
}