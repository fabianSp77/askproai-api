<?php

namespace App\Filament\Admin\Resources\Components;

use Filament\Infolists\Components\Entry;
use App\Helpers\AutoTranslateHelper;

class ToggleableTextEntry extends Entry
{
    protected string $view = 'filament.infolists.toggleable-text-entry';
    
    protected ?string $sourceLanguage = null;
    
    public function sourceLanguage(?string $language): static
    {
        $this->sourceLanguage = $language;
        return $this;
    }
    
    public function getState(): mixed
    {
        $state = parent::getState();
        $record = $this->getRecord();
        
        if (empty($state) || $state === 'Nicht erfasst') {
            return [
                'content' => ['original' => $state ?: '', 'translated' => $state ?: ''],
                'showToggle' => false
            ];
        }
        
        // Get source language from record if not explicitly set
        $sourceLanguage = $this->sourceLanguage ?? $record->detected_language ?? null;
        
        // Get toggleable content
        $toggleableData = AutoTranslateHelper::getToggleableContent(
            $state,
            $sourceLanguage
        );
        
        return [
            'content' => $toggleableData,
            'showToggle' => $toggleableData['should_translate']
        ];
    }
}