<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use App\Models\EmailTemplate;
use App\Models\EmailTemplatePreset;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_from_preset')
                ->label('Aus Vorlage erstellen')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->form([
                    Forms\Components\ViewField::make('preset_selector')
                        ->label('Wählen Sie eine Vorlage')
                        ->view('filament.forms.components.preset-selector')
                        ->viewData([
                            'presets' => EmailTemplatePreset::orderBy('name')->get(),
                        ]),
                ])
                ->action(function (array $data) {
                    // Get the selected preset ID from the data
                    $presetId = $data['selected_preset_id'] ?? null;

                    if (! $presetId) {
                        Notification::make()
                            ->title('Keine Vorlage ausgewählt')
                            ->danger()
                            ->send();

                        return;
                    }

                    $preset = EmailTemplatePreset::find($presetId);

                    if (! $preset) {
                        Notification::make()
                            ->title('Vorlage nicht gefunden')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Create a new EmailTemplate from the preset
                    $template = EmailTemplate::create([
                        'company_id' => auth()->user()->company_id,
                        'name' => $preset->name,
                        'subject' => $preset->subject,
                        'body_html' => $preset->body_html,
                        'template_type' => 'both', // Default to both
                        'is_active' => false, // Draft status
                    ]);

                    Notification::make()
                        ->title('Template erfolgreich erstellt')
                        ->success()
                        ->send();

                    // Redirect to edit page
                    return redirect()->route('filament.admin.resources.email-templates.edit', ['record' => $template->id]);
                })
                ->modalHeading('Vorlage auswählen')
                ->modalWidth('5xl')
                ->modalSubmitActionLabel('Template erstellen'),

            Actions\CreateAction::make(),
        ];
    }
}
