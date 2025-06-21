<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CalcomMigrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Example controller showing how to use CalcomMigrationService
 * for gradual V1 to V2 migration
 */
class CalcomMigrationExampleController extends Controller
{
    protected CalcomMigrationService $calcom;
    
    public function __construct(CalcomMigrationService $calcom)
    {
        $this->calcom = $calcom;
    }
    
    /**
     * Get event types - automatically uses V2 if enabled
     */
    public function getEventTypes(Request $request): JsonResponse
    {
        try {
            $teamSlug = $request->get('team_slug', config('services.calcom.team_slug'));
            
            // This will use V2 if enabled, otherwise V1
            $eventTypes = $this->calcom->getEventTypes($teamSlug);
            
            return response()->json([
                'success' => true,
                'data' => $eventTypes,
                'api_version' => $this->getApiVersion('getEventTypes')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get available slots - with V2 migration support
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'event_type_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'timezone' => 'string'
        ]);
        
        try {
            $slots = $this->calcom->getAvailableSlots(
                $request->event_type_id,
                $request->start_date,
                $request->end_date,
                $request->timezone ?? 'Europe/Berlin'
            );
            
            return response()->json([
                'success' => true,
                'data' => $slots,
                'api_version' => $this->getApiVersion('getAvailableSlots')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create booking - seamlessly migrates to V2
     */
    public function createBooking(Request $request): JsonResponse
    {
        $request->validate([
            'event_type_id' => 'required|integer',
            'start_time' => 'required|date',
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'string',
            'notes' => 'string|nullable'
        ]);
        
        try {
            $customerData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'timezone' => $request->timezone ?? 'Europe/Berlin'
            ];
            
            $booking = $this->calcom->bookAppointment(
                $request->event_type_id,
                $request->start_time,
                null, // V2 doesn't need end_time
                $customerData,
                $request->notes,
                ['source' => 'migration_example']
            );
            
            return response()->json([
                'success' => true,
                'data' => $booking,
                'api_version' => $this->getApiVersion('bookAppointment')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get migration status for monitoring
     */
    public function getMigrationStatus(): JsonResponse
    {
        $status = $this->calcom->getMigrationStatus();
        
        return response()->json([
            'success' => true,
            'migration_status' => $status,
            'recommendations' => $this->getRecommendations($status)
        ]);
    }
    
    /**
     * Enable V2 for specific method (admin only)
     */
    public function enableV2Method(Request $request): JsonResponse
    {
        $request->validate([
            'method' => 'required|string|in:getEventTypes,getAvailableSlots,bookAppointment,cancelBooking,getBooking',
            'duration' => 'integer|min:60|max:86400'
        ]);
        
        $this->calcom->enableV2ForMethod(
            $request->method,
            $request->duration ?? 3600
        );
        
        return response()->json([
            'success' => true,
            'message' => "V2 enabled for {$request->method}",
            'duration' => $request->duration ?? 3600
        ]);
    }
    
    /**
     * Helper to check which API version is being used
     */
    private function getApiVersion(string $method): string
    {
        $status = $this->calcom->getMigrationStatus();
        return $status['methods'][$method]['v2_enabled'] ?? false ? 'v2' : 'v1';
    }
    
    /**
     * Get recommendations based on migration status
     */
    private function getRecommendations(array $status): array
    {
        $recommendations = [];
        
        // Check if any methods are still on V1
        $v1Methods = array_filter($status['methods'], function($method) {
            return !$method['v2_enabled'];
        });
        
        if (count($v1Methods) > 0) {
            $recommendations[] = 'Consider migrating remaining V1 methods to V2 for better performance';
        }
        
        // Check if global V2 is disabled
        if (!$status['global_v2_enabled']) {
            $recommendations[] = 'Enable global V2 in production after successful testing';
        }
        
        // Check for temporary cache overrides
        $cacheOverrides = array_filter($status['methods'], function($method) {
            return $method['cache_override'];
        });
        
        if (count($cacheOverrides) > 0) {
            $recommendations[] = 'Make V2 migrations permanent by updating configuration';
        }
        
        return $recommendations;
    }
}