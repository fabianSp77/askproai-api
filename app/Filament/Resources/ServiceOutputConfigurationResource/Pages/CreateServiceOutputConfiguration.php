<?php

namespace App\Filament\Resources\ServiceOutputConfigurationResource\Pages;

use App\Filament\Resources\ServiceOutputConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceOutputConfiguration extends CreateRecord
{
    protected static string $resource = ServiceOutputConfigurationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values if not provided
        $data['is_active'] = $data['is_active'] ?? true;
        $data['retry_on_failure'] = $data['retry_on_failure'] ?? true;

        // Transform Repeater format to database format
        $entries = $data['recipient_entries'] ?? [];

        $emailRecipients = [];
        $mutedRecipients = [];

        foreach ($entries as $entry) {
            $email = trim($entry['email'] ?? '');
            if (empty($email)) {
                continue;
            }

            $emailRecipients[] = $email;

            if (!($entry['is_active'] ?? true)) {
                $mutedRecipients[] = $email;
            }
        }

        $data['email_recipients'] = array_values(array_unique($emailRecipients));
        $data['muted_recipients'] = array_values(array_unique($mutedRecipients));

        // Remove the virtual field
        unset($data['recipient_entries']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
