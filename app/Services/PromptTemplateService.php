<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class PromptTemplateService
{
    /**
     * Available industry templates
     */
    private array $industries = [
        'salon' => 'Beauty & Wellness',
        'medical' => 'Medizin & Gesundheit',
        'fitness' => 'Fitness & Sport',
        'generic' => 'Allgemein'
    ];
    
    /**
     * Get available industry templates
     */
    public function getAvailableTemplates(): array
    {
        return $this->industries;
    }
    
    /**
     * Render a prompt template for a branch
     */
    public function renderPrompt(Branch $branch, string $industry = 'generic', array $additionalData = []): string
    {
        $company = $branch->company;
        
        // Prepare template data
        $data = array_merge([
            // Company data
            'company_name' => $company->name,
            'company_type' => $industry,
            'company_phone' => $company->phone ?? $branch->phone,
            
            // Branch data
            'branch_name' => $branch->name,
            'branch_city' => $branch->city ?? 'Unknown',
            'branch_address' => $this->formatAddress($branch),
            'branch_features' => $branch->features ?? [],
            
            // Services
            'services_list' => $this->formatServices($branch),
            'appointment_duration' => $branch->default_appointment_duration ?? 30,
            'buffer_time' => $branch->buffer_time ?? 15,
            
            // Working hours
            'working_hours' => $this->formatWorkingHours($branch),
            
            // Staff info
            'staff_count' => $branch->staff()->active()->count(),
            'languages_supported' => $this->getLanguages($branch),
            
            // Policies
            'cancellation_policy' => $company->cancellation_policy ?? 'Termine können bis 24 Stunden vorher kostenfrei storniert werden.',
            'special_instructions' => $company->special_instructions ?? '',
            
            // Agent settings
            'agent_name' => 'Sarah', // Default, can be customized
            
        ], $additionalData);
        
        // Ensure template exists
        $template = $this->getTemplatePath($industry);
        if (!View::exists($template)) {
            $template = 'prompts.industries.generic';
        }
        
        // Render the template
        return View::make($template, $data)->render();
    }
    
    /**
     * Get template path for industry
     */
    private function getTemplatePath(string $industry): string
    {
        return 'prompts.industries.' . $industry;
    }
    
    /**
     * Format branch address
     */
    private function formatAddress(Branch $branch): string
    {
        $parts = array_filter([
            $branch->address,
            $branch->postal_code,
            $branch->city
        ]);
        
        return implode(', ', $parts) ?: 'Keine Adresse hinterlegt';
    }
    
    /**
     * Format services list
     */
    private function formatServices(Branch $branch): string
    {
        $services = $branch->services()->pluck('name')->toArray();
        
        if (empty($services)) {
            return 'Verschiedene Dienstleistungen';
        }
        
        return implode(', ', $services);
    }
    
    /**
     * Format working hours
     */
    private function formatWorkingHours(Branch $branch): string
    {
        // If branch has business_hours JSON
        if ($branch->business_hours) {
            return $this->formatBusinessHours($branch->business_hours);
        }
        
        // Fallback to working hours relation
        $workingHours = $branch->workingHours;
        if ($workingHours->isEmpty()) {
            return 'Mo-Fr 9:00-18:00 Uhr';
        }
        
        // Group by days
        $grouped = [];
        foreach ($workingHours as $wh) {
            $day = $this->getDayName($wh->day_of_week);
            $grouped[$day][] = $wh->start_time . '-' . $wh->end_time;
        }
        
        $formatted = [];
        foreach ($grouped as $day => $times) {
            $formatted[] = $day . ': ' . implode(', ', $times);
        }
        
        return implode(' | ', $formatted);
    }
    
    /**
     * Format business hours from JSON
     */
    private function formatBusinessHours(array $hours): string
    {
        $formatted = [];
        $dayNames = [
            'monday' => 'Mo',
            'tuesday' => 'Di', 
            'wednesday' => 'Mi',
            'thursday' => 'Do',
            'friday' => 'Fr',
            'saturday' => 'Sa',
            'sunday' => 'So'
        ];
        
        foreach ($hours as $day => $times) {
            if (isset($times['isOpen']) && $times['isOpen']) {
                $dayName = $dayNames[$day] ?? ucfirst($day);
                $formatted[] = $dayName . ': ' . $times['start'] . '-' . $times['end'];
            }
        }
        
        return implode(' | ', $formatted) ?: 'Nach Vereinbarung';
    }
    
    /**
     * Get day name in German
     */
    private function getDayName(int $dayOfWeek): string
    {
        return match($dayOfWeek) {
            1 => 'Mo',
            2 => 'Di',
            3 => 'Mi',
            4 => 'Do',
            5 => 'Fr',
            6 => 'Sa',
            7 => 'So',
            default => 'Tag ' . $dayOfWeek
        };
    }
    
    /**
     * Get supported languages
     */
    private function getLanguages(Branch $branch): string
    {
        $languages = $branch->staff()
            ->active()
            ->get()
            ->pluck('languages')
            ->flatten()
            ->unique()
            ->filter()
            ->toArray();
            
        if (empty($languages)) {
            return 'Deutsch';
        }
        
        $languageNames = [
            'de' => 'Deutsch',
            'en' => 'Englisch',
            'tr' => 'Türkisch',
            'ar' => 'Arabisch',
            'ru' => 'Russisch',
            'pl' => 'Polnisch',
            'es' => 'Spanisch',
            'fr' => 'Französisch',
            'it' => 'Italienisch'
        ];
        
        $mapped = array_map(function($lang) use ($languageNames) {
            return $languageNames[$lang] ?? $lang;
        }, $languages);
        
        return implode(', ', $mapped);
    }
    
    /**
     * Generate prompt for Retell agent
     */
    public function generateRetellPrompt(Branch $branch, array $config = []): array
    {
        $industry = $config['industry'] ?? 'generic';
        $voice = $config['voice'] ?? 'sarah';
        
        $prompt = $this->renderPrompt($branch, $industry, $config);
        
        return [
            'prompt' => $prompt,
            'voice_id' => $voice,
            'language' => 'de',
            'responsiveness' => 0.8,
            'interruption_sensitivity' => 0.7,
            'enable_backchannel' => true,
            'backchannel_frequency' => 0.6,
            'backchannel_words' => ['Ja', 'Verstehe', 'Okay', 'Natürlich'],
            'boosted_keywords' => $this->getBookedKeywords($branch),
            'reminder_trigger_ms' => 10000,
            'reminder_message' => 'Sind Sie noch da? Kann ich Ihnen bei der Terminbuchung helfen?'
        ];
    }
    
    /**
     * Get boosted keywords for better recognition
     */
    private function getBookedKeywords(Branch $branch): array
    {
        $keywords = [
            $branch->company->name,
            $branch->name,
            $branch->city ?? ''
        ];
        
        // Add service names
        $services = $branch->services()->pluck('name')->toArray();
        $keywords = array_merge($keywords, $services);
        
        // Add staff names
        $staffNames = $branch->staff()->active()->pluck('name')->toArray();
        $keywords = array_merge($keywords, $staffNames);
        
        // Filter and clean
        return array_values(array_filter(array_unique($keywords)));
    }
}