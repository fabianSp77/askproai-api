<x-filament-panels::page>
    <div class="space-y-6">
        @if($company)
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Configuring integrations for <strong>{{ $company->name }}</strong>
                    </p>
                </div>
            </div>
        @endif
        
        <form wire:submit.prevent="completeSetup">
            {{ $this->form }}
            
            <div class="mt-6 flex items-center justify-between">
                <div>
                    @if($currentStep > 1)
                        <x-filament::button 
                            wire:click="previousStep" 
                            color="gray"
                            type="button"
                        >
                            Previous
                        </x-filament::button>
                    @endif
                </div>
                
                <div class="flex items-center gap-3">
                    @if($currentStep === 4)
                        <x-filament::button 
                            wire:click="simulateTestCall" 
                            color="gray"
                            icon="heroicon-m-phone"
                            type="button"
                            :loading="$isSimulatingCall"
                        >
                            Simulate Test Call
                        </x-filament::button>
                        
                        <x-filament::button 
                            type="submit"
                            icon="heroicon-m-check"
                        >
                            Complete Setup
                        </x-filament::button>
                    @else
                        <x-filament::button 
                            wire:click="nextStep" 
                            type="button"
                        >
                            Next
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </form>
    </div>
    
    <style>
        .fi-fo-wizard-header {
            background: transparent !important;
        }
    </style>
</x-filament-panels::page>