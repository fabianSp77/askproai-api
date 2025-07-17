<?php

namespace App\Filament\Admin\Resources\CallResource\Actions;

use App\Models\Call;
use App\Models\CallCharge;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;

class MarkAsNonBillableAction extends BulkAction
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'markAsNonBillable')
            ->label('Als nicht abrechenbar markieren')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Anrufe als nicht abrechenbar markieren')
            ->modalDescription('Diese Anrufe werden markiert und nicht berechnet. Bereits abgerechnete Anrufe erhalten eine Gutschrift.')
            ->form([
                \Filament\Forms\Components\Select::make('reason')
                    ->label('Grund')
                    ->options([
                        'technical_issue' => 'Technisches Problem',
                        'quality_issue' => 'Qualitätsproblem', 
                        'wrong_number' => 'Falsche Nummer / Verwählt',
                        'customer_complaint' => 'Kundenbeschwerde',
                        'test_call' => 'Testanruf',
                        'demo_call' => 'Demo-Anruf',
                        'other' => 'Sonstiges',
                    ])
                    ->required()
                    ->default('wrong_number'),
                \Filament\Forms\Components\Textarea::make('notes')
                    ->label('Anmerkungen')
                    ->rows(2)
                    ->maxLength(500),
            ])
            ->action(function (array $data, Collection $records) {
                $markedCount = 0;
                $refundedCount = 0;
                $totalRefunded = 0;
                
                foreach ($records as $call) {
                    // Add metadata to mark as non-billable
                    $metadata = $call->metadata ?? [];
                    $metadata['non_billable'] = true;
                    $metadata['non_billable_reason'] = $data['reason'];
                    $metadata['non_billable_notes'] = $data['notes'] ?? null;
                    $metadata['marked_non_billable_at'] = now()->toIso8601String();
                    $metadata['marked_non_billable_by'] = auth()->user()->name;
                    
                    $call->update(['metadata' => $metadata]);
                    $markedCount++;
                    
                    // If already charged, create refund
                    $charge = CallCharge::where('call_id', $call->id)->first();
                    if ($charge && $charge->refund_status === 'none') {
                        $refundService = app(\App\Services\CallRefundService::class);
                        $reason = match($data['reason']) {
                            'technical_issue' => 'Technisches Problem',
                            'quality_issue' => 'Qualitätsproblem',
                            'wrong_number' => 'Falsche Nummer',
                            'customer_complaint' => 'Kundenbeschwerde',
                            'test_call' => 'Testanruf',
                            'demo_call' => 'Demo-Anruf',
                            'other' => 'Sonstiges: ' . ($data['notes'] ?? ''),
                        };
                        
                        $result = $refundService->refundCall($call, $reason);
                        if ($result) {
                            $refundedCount++;
                            $totalRefunded += $charge->amount_charged;
                        }
                    }
                }
                
                $message = sprintf(
                    '%d Anrufe als nicht abrechenbar markiert.',
                    $markedCount
                );
                
                if ($refundedCount > 0) {
                    $message .= sprintf(
                        ' %d bereits abgerechnete Anrufe wurden erstattet (%.2f €).',
                        $refundedCount,
                        $totalRefunded
                    );
                }
                
                Notification::make()
                    ->title('Erfolgreich markiert')
                    ->body($message)
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}