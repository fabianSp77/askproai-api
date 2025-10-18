<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;

class TestCreatePage extends Command
{
    protected $signature = 'test:create-page';
    protected $description = 'Test the appointments create page';

    public function handle()
    {
        $this->info('Testing /admin/appointments/create page...');
        
        try {
            $user = \App\Models\User::find(6);
            
            if (!$user) {
                $this->error('User not found');
                return;
            }
            
            // Simulate Filament resource class loading
            $resource = new \App\Filament\Resources\AppointmentResource();
            $createPage = new \App\Filament\Resources\AppointmentResource\Pages\CreateAppointment();
            
            $this->info('✅ Classes loaded successfully');
            $this->info('  - AppointmentResource: ' . get_class($resource));
            $this->info('  - CreateAppointment Page: ' . get_class($createPage));
            
            // Check if form method exists
            if (method_exists($resource, 'form')) {
                $this->info('✅ form() method exists');
            } else {
                $this->error('❌ form() method NOT found');
            }
            
            // Try to instantiate the form
            $form = $resource->form(\Filament\Forms\Form::make());
            $this->info('✅ Form created');
            $this->line('Form schema: ' . count($form->getComponents()) . ' components');
            
        } catch (\Exception $e) {
            $this->error('❌ ERROR: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->error('');
            $this->error('Stack Trace:');
            foreach (explode("\n", $e->getTraceAsString()) as $line) {
                $this->line($line);
            }
        }
    }
}
