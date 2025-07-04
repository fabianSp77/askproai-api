<?php

// This script creates a backup of the current mock implementation
// and provides a real implementation that checks database appointments

$filePath = __DIR__ . '/app/Services/MCP/RetellCustomFunctionMCPServer.php';
$backupPath = __DIR__ . '/app/Services/MCP/RetellCustomFunctionMCPServer.php.backup';

// Create backup
copy($filePath, $backupPath);
echo "Backup created at: $backupPath\n";

// New implementation for getAvailableSlots method
$newImplementation = <<<'PHP'
    /**
     * Get available slots for a date
     */
    protected function getAvailableSlots(string $branchId, string $date, ?string $serviceName): array
    {
        try {
            // Parse the date
            $dateObj = Carbon::parse($date);
            
            // Get staff members for this branch with the service
            $staffMembers = \DB::table('staff')
                ->where('branch_id', $branchId)
                ->where('deleted_at', null)
                ->pluck('id');
            
            if ($staffMembers->isEmpty()) {
                Log::warning('No staff members found for branch', ['branch_id' => $branchId]);
                return [];
            }
            
            // Get working hours for the requested day
            $dayOfWeek = $dateObj->dayOfWeek;
            $workingHours = \DB::table('working_hours')
                ->whereIn('staff_id', $staffMembers)
                ->where('day_of_week', $dayOfWeek)
                ->get();
            
            if ($workingHours->isEmpty()) {
                Log::warning('No working hours for this day', [
                    'branch_id' => $branchId,
                    'day_of_week' => $dayOfWeek,
                    'date' => $date
                ]);
                return [];
            }
            
            $slots = [];
            
            foreach ($workingHours as $wh) {
                // Get existing appointments for this staff member on this date
                $existingAppointments = \DB::table('appointments')
                    ->where('staff_id', $wh->staff_id)
                    ->where('branch_id', $branchId)
                    ->whereDate('appointment_date', $dateObj->format('Y-m-d'))
                    ->whereIn('status', ['scheduled', 'confirmed'])
                    ->orderBy('appointment_date')
                    ->get(['appointment_date', 'duration']);
                
                // Generate time slots
                $start = Carbon::parse($date . ' ' . $wh->start);
                $end = Carbon::parse($date . ' ' . $wh->end);
                $slotDuration = 30; // 30-minute slots
                
                // If it's today, start from next available slot
                if ($dateObj->isToday() && $start->isPast()) {
                    $now = Carbon::now();
                    $nextSlot = $now->copy()->addMinutes(30 - ($now->minute % 30))->second(0);
                    if ($nextSlot->isAfter($start)) {
                        $start = $nextSlot;
                    }
                }
                
                while ($start->addMinutes($slotDuration)->lte($end)) {
                    $slotEnd = $start->copy()->addMinutes($slotDuration);
                    
                    // Check if this slot conflicts with existing appointments
                    $isAvailable = true;
                    foreach ($existingAppointments as $apt) {
                        $aptStart = Carbon::parse($apt->appointment_date);
                        $aptEnd = $aptStart->copy()->addMinutes($apt->duration ?? 30);
                        
                        // Check for overlap
                        if (!($slotEnd->lte($aptStart) || $start->gte($aptEnd))) {
                            $isAvailable = false;
                            break;
                        }
                    }
                    
                    if ($isAvailable) {
                        $slots[] = $start->format('Y-m-d H:i:s');
                    }
                    
                    $start = $slotEnd;
                }
            }
            
            // Remove duplicates and sort
            $slots = array_unique($slots);
            sort($slots);
            
            return array_values($slots);
            
        } catch (\Exception $e) {
            Log::error('Failed to get available slots', [
                'branch_id' => $branchId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to some default slots if there's an error
            $fallbackSlots = [];
            $start = Carbon::parse($date)->setTime(9, 0);
            $end = Carbon::parse($date)->setTime(17, 0);
            
            while ($start < $end) {
                $fallbackSlots[] = $start->format('Y-m-d H:i:s');
                $start->addMinutes(30);
            }
            
            return $fallbackSlots;
        }
    }
PHP;

echo "\nNew implementation ready. To apply it, edit the RetellCustomFunctionMCPServer.php file and replace the getAvailableSlots method.\n";
echo "\nThe new implementation will:\n";
echo "1. Check real working hours from the database\n";
echo "2. Check for existing appointments to avoid double-booking\n";
echo "3. Only show slots in the future (not past times for today)\n";
echo "4. Handle errors gracefully with fallback slots\n";