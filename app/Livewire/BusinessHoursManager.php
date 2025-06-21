<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Schema;

class BusinessHoursManager extends Component
{
    public $businessHours = [];
    public $selectedTemplate = '';
    public $templates = [];
    public $filialName = '';

    public function mount($businessHours = null, $filialName = '')
    {
        $this->filialName = $filialName;
        
        // Check if BusinessHoursTemplate model/table exists
        if (class_exists('App\Models\BusinessHoursTemplate') && Schema::hasTable('business_hours_templates')) {
            $businessHoursTemplateClass = 'App\Models\BusinessHoursTemplate';
            $this->templates = $businessHoursTemplateClass::all();
        } else {
            // Provide default templates if table doesn't exist
            $this->templates = collect($this->getDefaultTemplates());
        }
        
        if ($businessHours) {
            $this->businessHours = $businessHours;
        } else {
            $this->initializeEmptyHours();
        }
    }

    public function initializeEmptyHours()
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            $this->businessHours[$day] = [
                'open' => '',
                'close' => '',
                'closed' => false
            ];
        }
    }

    public function applyTemplate()
    {
        if ($this->selectedTemplate) {
            // Check if using database templates or default templates
            if (class_exists('App\Models\BusinessHoursTemplate') && Schema::hasTable('business_hours_templates')) {
                $businessHoursTemplateClass = 'App\Models\BusinessHoursTemplate';
                $template = $businessHoursTemplateClass::find($this->selectedTemplate);
                if ($template) {
                    $this->businessHours = $template->hours;
                    $this->dispatch('business-hours-updated', $this->businessHours);
                }
            } else {
                // Use default templates
                $defaultTemplates = $this->getDefaultTemplates();
                if (isset($defaultTemplates[$this->selectedTemplate])) {
                    $this->businessHours = $defaultTemplates[$this->selectedTemplate]['hours'];
                    $this->dispatch('business-hours-updated', $this->businessHours);
                }
            }
        }
    }

    public function copyFromPreviousDay($targetDay)
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $targetIndex = array_search($targetDay, $days);
        
        if ($targetIndex > 0) {
            $previousDay = $days[$targetIndex - 1];
            $this->businessHours[$targetDay] = $this->businessHours[$previousDay];
            $this->dispatch('business-hours-updated', $this->businessHours);
        }
    }

    public function copyToAllWeekdays($sourceDay)
    {
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        foreach ($weekdays as $day) {
            if ($day !== $sourceDay) {
                $this->businessHours[$day] = $this->businessHours[$sourceDay];
            }
        }
        $this->dispatch('business-hours-updated', $this->businessHours);
    }

    public function updateHours()
    {
        $this->dispatch('business-hours-updated', $this->businessHours);
    }

    public function render()
    {
        return view('livewire.business-hours-manager');
    }
    
    /**
     * Get default business hours templates
     */
    protected function getDefaultTemplates()
    {
        return [
            'standard' => [
                'id' => 'standard',
                'name' => 'Standard (Mo-Fr 9-18)',
                'hours' => [
                    'monday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'thursday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'saturday' => ['open' => '', 'close' => '', 'closed' => true],
                    'sunday' => ['open' => '', 'close' => '', 'closed' => true],
                ]
            ],
            'extended' => [
                'id' => 'extended',
                'name' => 'Erweitert (Mo-Sa)',
                'hours' => [
                    'monday' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
                    'tuesday' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
                    'wednesday' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
                    'thursday' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
                    'friday' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
                    'saturday' => ['open' => '09:00', 'close' => '16:00', 'closed' => false],
                    'sunday' => ['open' => '', 'close' => '', 'closed' => true],
                ]
            ],
            'salon' => [
                'id' => 'salon',
                'name' => 'Salon (Di-Sa)',
                'hours' => [
                    'monday' => ['open' => '', 'close' => '', 'closed' => true],
                    'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'thursday' => ['open' => '09:00', 'close' => '20:00', 'closed' => false],
                    'friday' => ['open' => '09:00', 'close' => '20:00', 'closed' => false],
                    'saturday' => ['open' => '08:00', 'close' => '14:00', 'closed' => false],
                    'sunday' => ['open' => '', 'close' => '', 'closed' => true],
                ]
            ],
        ];
    }
}
