<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BusinessHoursTemplate;

class BusinessHoursManager extends Component
{
    public $businessHours = [];
    public $selectedTemplate = '';
    public $templates = [];
    public $filialName = '';

    public function mount($businessHours = null, $filialName = '')
    {
        $this->filialName = $filialName;
        $this->templates = BusinessHoursTemplate::all();
        
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
            $template = BusinessHoursTemplate::find($this->selectedTemplate);
            if ($template) {
                $this->businessHours = $template->hours;
                $this->dispatch('business-hours-updated', $this->businessHours);
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
}
