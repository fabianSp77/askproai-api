<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Models\CalcomEventType;
use App\Models\Staff;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EventTypeManagement extends Page
{
    use HasConsistentNavigation;
    
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Event-Type Verwaltung';
    protected static ?string $navigationGroup = 'Personal & Services';
    protected static ?int $navigationSort = 210;
    protected static string $view = 'filament.admin.pages.event-type-management';
    protected static ?string $title = 'Event-Type Verwaltung';
    
    public function getViewData(): array
    {
        $company = auth()->user()->company;
        
        if (!$company) {
            return [
                'stats' => [],
                'recentActivities' => [],
                'warnings' => [],
                'companyName' => 'Kein Unternehmen',
            ];
        }
        
        // Statistiken
        $totalEventTypes = CalcomEventType::where('company_id', $company->id)->count();
        $activeEventTypes = CalcomEventType::where('company_id', $company->id)
            ->where('is_active', true)
            ->count();
        $totalStaff = Staff::where('company_id', $company->id)
            ->where('active', true)
            ->count();
        $assignedStaff = Staff::where('company_id', $company->id)
            ->where('active', true)
            ->whereHas('eventTypes')
            ->count();
        
        // Unzugeordnete Event-Types
        $unassignedEventTypes = CalcomEventType::where('company_id', $company->id)
            ->where('is_active', true)
            ->whereDoesntHave('assignedStaff')
            ->count();
            
        // Event-Types ohne Cal.com Verknüpfung
        $eventTypesWithoutCalcom = CalcomEventType::where('company_id', $company->id)
            ->where('is_active', true)
            ->whereNull('calcom_event_type_id')
            ->count();
            
        // Letzte Aktivitäten
        $recentActivities = DB::table('staff_event_types')
            ->join('staff', 'staff.id', '=', 'staff_event_types.staff_id')
            ->join('calcom_event_types', 'calcom_event_types.id', '=', 'staff_event_types.event_type_id')
            ->where('staff.company_id', $company->id)
            ->orderBy('staff_event_types.created_at', 'desc')
            ->limit(5)
            ->select(
                'staff.name as staff_name',
                'calcom_event_types.name as event_type_name',
                'staff_event_types.created_at'
            )
            ->get();
            
        // Warnungen
        $warnings = [];
        
        if ($unassignedEventTypes > 0) {
            $warnings[] = [
                'type' => 'warning',
                'message' => "$unassignedEventTypes Event-Types haben keine zugeordneten Mitarbeiter",
                'action' => '/admin/staff-event-assignment-modern',
                'actionLabel' => 'Jetzt zuordnen'
            ];
        }
        
        if ($eventTypesWithoutCalcom > 0) {
            $warnings[] = [
                'type' => 'danger',
                'message' => "$eventTypesWithoutCalcom Event-Types sind nicht mit Cal.com verknüpft",
                'action' => '/admin/event-type-import-wizard',
                'actionLabel' => 'Cal.com Import'
            ];
        }
        
        $staffWithoutEventTypes = $totalStaff - $assignedStaff;
        if ($staffWithoutEventTypes > 0) {
            $warnings[] = [
                'type' => 'info',
                'message' => "$staffWithoutEventTypes Mitarbeiter haben keine Event-Types zugeordnet",
                'action' => '/admin/staff',
                'actionLabel' => 'Mitarbeiter verwalten'
            ];
        }
        
        return [
            'stats' => [
                [
                    'label' => 'Event-Types',
                    'value' => $totalEventTypes,
                    'description' => "$activeEventTypes aktiv",
                    'icon' => 'heroicon-o-calendar-days',
                    'color' => 'primary',
                ],
                [
                    'label' => 'Mitarbeiter',
                    'value' => $totalStaff,
                    'description' => "$assignedStaff mit Event-Types",
                    'icon' => 'heroicon-o-users',
                    'color' => 'success',
                ],
                [
                    'label' => 'Zuordnungen',
                    'value' => DB::table('staff_event_types')
                        ->join('staff', 'staff.id', '=', 'staff_event_types.staff_id')
                        ->where('staff.company_id', $company->id)
                        ->count(),
                    'description' => 'Aktive Verknüpfungen',
                    'icon' => 'heroicon-o-link',
                    'color' => 'info',
                ],
                [
                    'label' => 'Cal.com Status',
                    'value' => CalcomEventType::where('company_id', $company->id)
                        ->whereNotNull('calcom_event_type_id')
                        ->count() . '/' . $totalEventTypes,
                    'description' => 'Verknüpft',
                    'icon' => 'heroicon-o-check-circle',
                    'color' => $eventTypesWithoutCalcom > 0 ? 'warning' : 'success',
                ],
            ],
            'recentActivities' => $recentActivities,
            'warnings' => $warnings,
            'companyName' => $company->name,
        ];
    }
    
    public function getQuickActions(): array
    {
        return [
            [
                'label' => 'Event-Types importieren',
                'description' => 'Importieren Sie Event-Types von Cal.com',
                'icon' => 'heroicon-o-arrow-down-tray',
                'url' => '/admin/event-type-import-wizard',
                'color' => 'primary',
            ],
            [
                'label' => 'Mitarbeiter zuordnen',
                'description' => 'Ordnen Sie Mitarbeiter zu Event-Types zu',
                'icon' => 'heroicon-o-user-group',
                'url' => '/admin/staff-event-assignment-modern',
                'color' => 'success',
            ],
            [
                'label' => 'Event-Type konfigurieren',
                'description' => 'Detaillierte Einstellungen für Event-Types',
                'icon' => 'heroicon-o-cog-6-tooth',
                'url' => '/admin/event-type-setup-wizard',
                'color' => 'info',
            ],
            [
                'label' => 'Cal.com Sync Status',
                'description' => 'Überprüfen Sie die Synchronisation',
                'icon' => 'heroicon-o-arrow-path',
                'url' => '/admin/calcom-sync-status',
                'color' => 'warning',
            ],
        ];
    }
}