<?php

namespace App\Services;

use App\Models\Call;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Filament\Admin\Resources\CallResource;

class CallNotificationService
{
    /**
     * Notify users about a new incoming call
     */
    public static function notifyNewCall(Call $call): void
    {
        // Get all users from the same company
        $users = User::where('company_id', $call->company_id)
            ->where('is_active', true)
            ->get();
            
        foreach ($users as $user) {
            // Check if user has notification preferences (can be added later)
            if (self::shouldNotifyUser($user, 'new_call')) {
                Notification::make()
                    ->title('Neuer Anruf eingegangen!')
                    ->icon('heroicon-o-phone-arrow-down-left')
                    ->iconColor('success')
                    ->body(self::getCallNotificationBody($call))
                    ->actions([
                        Action::make('view')
                            ->label('Anzeigen')
                            ->url(CallResource::getUrl('view', ['record' => $call]))
                            ->button(),
                    ])
                    ->sendToDatabase($user);
            }
        }
    }
    
    /**
     * Notify about a call that resulted in an appointment
     */
    public static function notifyCallConverted(Call $call): void
    {
        if (!$call->appointment_id) {
            return;
        }
        
        $users = User::where('company_id', $call->company_id)
            ->where('is_active', true)
            ->get();
            
        foreach ($users as $user) {
            if (self::shouldNotifyUser($user, 'call_converted')) {
                Notification::make()
                    ->title('Termin aus Anruf gebucht!')
                    ->icon('heroicon-o-calendar-days')
                    ->iconColor('success')
                    ->body(self::getConvertedNotificationBody($call))
                    ->actions([
                        Action::make('viewCall')
                            ->label('Anruf anzeigen')
                            ->url(CallResource::getUrl('view', ['record' => $call]))
                            ->button(),
                        Action::make('viewAppointment')
                            ->label('Termin anzeigen')
                            ->url('/admin/appointments/' . $call->appointment_id)
                            ->button()
                            ->color('success'),
                    ])
                    ->sendToDatabase($user);
            }
        }
    }
    
    /**
     * Notify about a failed call
     */
    public static function notifyFailedCall(Call $call): void
    {
        if ($call->call_status !== 'failed') {
            return;
        }
        
        $users = User::where('company_id', $call->company_id)
            ->where('is_active', true)
            ->role(['admin', 'manager']) // Only notify admins and managers
            ->get();
            
        foreach ($users as $user) {
            if (self::shouldNotifyUser($user, 'failed_call')) {
                Notification::make()
                    ->title('Anruf fehlgeschlagen')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('danger')
                    ->body(self::getFailedNotificationBody($call))
                    ->actions([
                        Action::make('view')
                            ->label('Details anzeigen')
                            ->url(CallResource::getUrl('view', ['record' => $call]))
                            ->button(),
                    ])
                    ->sendToDatabase($user);
            }
        }
    }
    
    /**
     * Check if user should receive notification
     */
    private static function shouldNotifyUser(User $user, string $type): bool
    {
        // For now, always return true. Later we can add user preferences
        // Example: return $user->notification_preferences[$type] ?? true;
        return true;
    }
    
    /**
     * Get notification body for new call
     */
    private static function getCallNotificationBody(Call $call): string
    {
        $parts = [];
        
        if ($call->customer) {
            $parts[] = "Anrufer: {$call->customer->name}";
        }
        
        $parts[] = "Nummer: {$call->from_number}";
        
        if ($call->branch) {
            $parts[] = "Filiale: {$call->branch->name}";
        }
        
        if ($call->duration_sec) {
            $duration = gmdate('i:s', $call->duration_sec);
            $parts[] = "Dauer: {$duration}";
        }
        
        return implode(' • ', $parts);
    }
    
    /**
     * Get notification body for converted call
     */
    private static function getConvertedNotificationBody(Call $call): string
    {
        $parts = [];
        
        if ($call->customer) {
            $parts[] = "Kunde: {$call->customer->name}";
        }
        
        if ($call->appointment && $call->appointment->starts_at) {
            $date = $call->appointment->starts_at->format('d.m.Y H:i');
            $parts[] = "Termin: {$date}";
        }
        
        if ($call->appointment && $call->appointment->service) {
            $parts[] = "Service: {$call->appointment->service->name}";
        }
        
        return implode(' • ', $parts);
    }
    
    /**
     * Get notification body for failed call
     */
    private static function getFailedNotificationBody(Call $call): string
    {
        $parts = [];
        
        $parts[] = "Nummer: {$call->from_number}";
        
        if ($call->disconnection_reason) {
            $parts[] = "Grund: {$call->disconnection_reason}";
        }
        
        if ($call->duration_sec) {
            $duration = gmdate('i:s', $call->duration_sec);
            $parts[] = "Dauer: {$duration}";
        }
        
        return implode(' • ', $parts);
    }
}