<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AppointmentResource\Pages;
use App\Filament\Admin\Resources\Concerns\MultiTenantResource;
use App\Filament\Admin\Traits\ConsistentNavigation;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class AppointmentResourceUpdated extends Resource
{
    use MultiTenantResource, ConsistentNavigation;
    
    protected static ?string $model = Appointment::class;
    
    // Navigation properties are now handled by ConsistentNavigation trait
    // No need to define navigationIcon, navigationLabel, navigationGroup, navigationSort
    
    public static function form(Form $form): Form
    {
        // Form definition remains the same
        return $form->schema([
            // ... existing form schema
        ]);
    }
    
    public static function table(Table $table): Table
    {
        // Table definition remains the same
        return $table->columns([
            // ... existing table columns
        ]);
    }
    
    /**
     * Get count for navigation badge
     */
    public static function getAppointmentCount(): int
    {
        return static::getModel()::query()
            ->whereDate('start_time', today())
            ->where('status', 'scheduled')
            ->count();
    }
    
    /**
     * Get pages with breadcrumbs
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}