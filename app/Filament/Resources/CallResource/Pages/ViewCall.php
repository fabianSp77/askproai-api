<?php

namespace App\Filament\Resources\CallResource\Pages;

use App\Filament\Resources\CallResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewCall extends ViewRecord
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Eager load all relationships to prevent N+1 queries
     */
    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        return parent::resolveRecord($key)->load([
            'customer',
            'company',
            'staff',
            'phoneNumber',
            'branch',
            'latestAppointment.staff',
            'latestAppointment.service',
            'latestAppointment.customer',
        ]);
    }

    public function getTitle(): string
    {
        // Build intelligent title with customer name, date and status
        // Priority: customer_name field > linked customer > fallback
        if ($this->record->customer_name) {
            $customerName = $this->record->customer_name;
        } elseif ($this->record->customer?->name) {
            $customerName = $this->record->customer->name;
        } else {
            $customerName = $this->record->from_number === 'anonymous' ? 'Anonymer Anrufer' : 'Unbekannter Kunde';
        }

        $date = $this->record->created_at?->format('d.m.Y H:i') ?? 'Datum unbekannt';

        $statusEmoji = match($this->record->status) {
            'completed' => '✅',
            'missed' => '📵',
            'failed' => '❌',
            'busy' => '🔴',
            'no_answer' => '🔇',
            default => '📞',
        };

        return $statusEmoji . ' Anruf mit ' . $customerName . ' - ' . $date;
    }

    public function getSubheading(): ?string
    {
        // Show technical IDs as subtle subheading
        $ids = [];

        if ($this->record->external_id) {
            $ids[] = 'Externe ID: ' . $this->record->external_id;
        }

        if ($this->record->retell_call_id) {
            $ids[] = 'Retell ID: ' . substr($this->record->retell_call_id, 0, 8) . '...';
        }

        $ids[] = 'System ID: #' . $this->record->id;

        return implode(' • ', $ids);
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}