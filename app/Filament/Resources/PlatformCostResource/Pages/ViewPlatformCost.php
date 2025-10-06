<?php

namespace App\Filament\Resources\PlatformCostResource\Pages;

use App\Filament\Resources\PlatformCostResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPlatformCost extends ViewRecord
{
    protected static string $resource = PlatformCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        $platform = match($this->record->platform) {
            'retell' => 'Retell.ai',
            'twilio' => 'Twilio',
            'calcom' => 'Cal.com',
            'openai' => 'OpenAI',
            default => ucfirst($this->record->platform)
        };

        return "Plattform-Kosten: {$platform}";
    }
}