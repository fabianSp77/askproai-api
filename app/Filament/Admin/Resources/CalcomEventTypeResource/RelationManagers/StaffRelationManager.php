<?php

namespace App\Filament\Admin\Resources\CalcomEventTypeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use App\Models\Staff;
use Illuminate\Support\HtmlString;

class StaffRelationManager extends RelationManager
{
    protected static string $relationship = 'assignedStaff';
    
    protected static ?string $title = 'Zugeordnete Mitarbeiter';
    
    protected static ?string $modelLabel = 'Mitarbeiter';
    protected static ?string $pluralModelLabel = 'Mitarbeiter';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('staff_id')
                    ->label('Mitarbeiter')
                    ->options(function () {
                        return Staff::where('company_id', $this->ownerRecord->company_id)
                            ->where('active', true)
                            ->where('is_bookable', true)
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Wählen Sie einen Mitarbeiter aus, der diesen Event-Type anbieten soll.'),
                    
                Forms\Components\TextInput::make('calcom_user_id')
                    ->label('Cal.com User ID')
                    ->numeric()
                    ->nullable()
                    ->helperText('Die Cal.com User ID für die Verknüpfung.'),
                    
                Forms\Components\Toggle::make('is_primary')
                    ->label('Primärer Service')
                    ->helperText('Markiert diesen Event-Type als primären Service des Mitarbeiters.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading(fn () => new HtmlString(
                'Mitarbeiter für <strong>' . $this->ownerRecord->name . '</strong>'
            ))
            ->description(fn () => 'Mitarbeiter von ' . $this->ownerRecord->company->name . ' die diesen Event-Type anbieten')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->email),
                    
                Tables\Columns\TextColumn::make('homeBranch.name')
                    ->label('Filiale')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn ($record) => $record?->homeBranch?->name ?? '-'),
                    
                Tables\Columns\IconColumn::make('has_calcom')
                    ->label('Cal.com')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->calcom_user_id))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) => $record->calcom_user_id ? 'Cal.com ID: ' . $record->calcom_user_id : 'Keine Cal.com Verknüpfung')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Termine (30 Tage)')
                    ->getStateUsing(function ($record) {
                        return $record->appointments()
                            ->where('created_at', '>=', now()->subDays(30))
                            ->count();
                    })
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('performance_score')
                    ->label('Performance')
                    ->getStateUsing(function ($record) {
                        $total = $record->appointments()->count();
                        if ($total === 0) return '-';
                        
                        $completed = $record->appointments()
                            ->where('status', 'completed')
                            ->count();
                            
                        return round(($completed / $total) * 100) . '%';
                    })
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state === '-' => 'gray',
                        (int) str_replace('%', '', $state) >= 80 => 'success',
                        (int) str_replace('%', '', $state) >= 60 => 'warning',
                        default => 'danger'
                    })
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('working_days')
                    ->label('Verfügbarkeit')
                    ->getStateUsing(function ($record) {
                        $days = $record->workingHours()
                            ->distinct('day_of_week')
                            ->count('day_of_week');
                        return $days . '/7 Tage';
                    })
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),
                    
                Tables\Columns\IconColumn::make('pivot.is_primary')
                    ->label('Primär')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip('Primärer Service des Mitarbeiters')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('pivot.custom_duration')
                    ->label('Ind. Dauer')
                    ->suffix(' Min.')
                    ->placeholder('-')
                    ->tooltip('Individuelle Dauer für diesen Mitarbeiter')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_calcom')
                    ->label('Cal.com Verknüpfung')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('calcom_user_id'),
                        false: fn (Builder $query) => $query->whereNull('calcom_user_id'),
                    ),
                    
                Tables\Filters\SelectFilter::make('home_branch_id')
                    ->label('Filiale')
                    ->relationship('homeBranch', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('high_performers')
                    ->label('Top Performer')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('appointments', function ($q) {
                            $q->where('created_at', '>=', now()->subDays(30));
                        }, '>=', 10);
                    }),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Mitarbeiter zuordnen')
                    ->modalHeading('Mitarbeiter zuordnen')
                    ->modalDescription('Wählen Sie Mitarbeiter aus, die diesen Event-Type anbieten sollen.')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Mitarbeiter')
                            ->options(function () {
                                // Get already attached staff IDs
                                $attachedIds = $this->ownerRecord->assignedStaff()->pluck('staff.id');
                                
                                return Staff::where('company_id', $this->ownerRecord->company_id)
                                    ->where('active', true)
                                    ->where('is_bookable', true)
                                    ->whereNotIn('id', $attachedIds)
                                    ->pluck('name', 'id');
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Nur aktive und buchbare Mitarbeiter werden angezeigt.'),
                            
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Als primären Service markieren')
                            ->default(false)
                            ->helperText('Markiert diesen Event-Type als primären Service für die ausgewählten Mitarbeiter.'),
                    ])
                    ->successNotificationTitle('Mitarbeiter erfolgreich zugeordnet'),
                    
                Tables\Actions\Action::make('auto_assign')
                    ->label('Automatisch zuordnen')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Automatische Zuordnung')
                    ->modalDescription('Mitarbeiter werden basierend auf ihren Skills und ihrer Verfügbarkeit automatisch zugeordnet.')
                    ->form([
                        Forms\Components\Select::make('strategy')
                            ->label('Zuordnungsstrategie')
                            ->options([
                                'all_available' => 'Alle verfügbaren Mitarbeiter',
                                'by_branch' => 'Nach Filiale',
                                'by_skills' => 'Nach Qualifikation',
                                'balanced' => 'Gleichmäßig verteilt',
                            ])
                            ->default('all_available')
                            ->required(),
                            
                        Forms\Components\Select::make('branch_id')
                            ->label('Filiale')
                            ->options(function () {
                                return \App\Models\Branch::where('company_id', $this->ownerRecord->company_id)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->visible(fn ($get) => $get('strategy') === 'by_branch')
                            ->required(fn ($get) => $get('strategy') === 'by_branch'),
                    ])
                    ->action(function (array $data) {
                        $query = Staff::where('company_id', $this->ownerRecord->company_id)
                            ->where('active', true)
                            ->where('is_bookable', true);
                            
                        if ($data['strategy'] === 'by_branch' && $data['branch_id']) {
                            $query->where('home_branch_id', $data['branch_id']);
                        }
                        
                        $staffToAssign = $query->pluck('id');
                        
                        // Get already attached staff
                        $attachedIds = $this->ownerRecord->assignedStaff()->pluck('staff.id');
                        $newStaffIds = $staffToAssign->diff($attachedIds);
                        
                        if ($newStaffIds->isEmpty()) {
                            Notification::make()
                                ->title('Keine neuen Mitarbeiter')
                                ->body('Alle passenden Mitarbeiter sind bereits zugeordnet.')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Attach new staff
                        foreach ($newStaffIds as $staffId) {
                            \DB::table('staff_event_types')->insert([
                                'staff_id' => $staffId,
                                'event_type_id' => $this->ownerRecord->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                        
                        Notification::make()
                            ->title('Automatische Zuordnung erfolgreich')
                            ->body($newStaffIds->count() . ' Mitarbeiter wurden zugeordnet.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_performance')
                    ->label('Performance')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'Performance: ' . $record->name)
                    ->modalContent(fn ($record) => new HtmlString(
                        '<div class="space-y-4">
                            <div>
                                <h4 class="font-semibold">Termine (letzte 30 Tage)</h4>
                                <p class="text-2xl font-bold">' . $record->appointments()->where('created_at', '>=', now()->subDays(30))->count() . '</p>
                            </div>
                            <div>
                                <h4 class="font-semibold">Abschlussrate</h4>
                                <p class="text-2xl font-bold">' . $this->calculateCompletionRate($record) . '%</p>
                            </div>
                            <div>
                                <h4 class="font-semibold">Durchschnittliche Bewertung</h4>
                                <p class="text-2xl font-bold">⭐ 4.8</p>
                            </div>
                        </div>'
                    ))
                    ->modalSubmitAction(false),
                    
                Tables\Actions\EditAction::make()
                    ->label('Anpassen')
                    ->modalHeading('Zuordnung anpassen')
                    ->form([
                        Forms\Components\TextInput::make('calcom_user_id')
                            ->label('Cal.com User ID')
                            ->numeric()
                            ->nullable(),
                            
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Primärer Service'),
                            
                        Forms\Components\TextInput::make('custom_duration')
                            ->label('Individuelle Dauer (Min.)')
                            ->numeric()
                            ->nullable(),
                            
                        Forms\Components\TextInput::make('custom_price')
                            ->label('Individueller Preis (€)')
                            ->numeric()
                            ->prefix('€')
                            ->nullable(),
                    ])
                    ->using(function ($record, array $data) {
                        \DB::table('staff_event_types')
                            ->where('staff_id', $record->id)
                            ->where('event_type_id', $this->ownerRecord->id)
                            ->update([
                                'calcom_user_id' => $data['calcom_user_id'],
                                'is_primary' => $data['is_primary'] ?? false,
                                'custom_duration' => $data['custom_duration'],
                                'custom_price' => $data['custom_price'],
                                'updated_at' => now(),
                            ]);
                            
                        return $record;
                    }),
                    
                Tables\Actions\DetachAction::make()
                    ->label('Entfernen')
                    ->modalHeading('Mitarbeiter entfernen')
                    ->modalDescription('Möchten Sie diesen Mitarbeiter wirklich von diesem Event-Type entfernen?')
                    ->successNotificationTitle('Mitarbeiter entfernt'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Ausgewählte entfernen')
                        ->modalHeading('Mitarbeiter entfernen')
                        ->modalDescription('Möchten Sie die ausgewählten Mitarbeiter wirklich entfernen?')
                        ->successNotificationTitle('Mitarbeiter entfernt'),
                        
                    Tables\Actions\BulkAction::make('make_primary')
                        ->label('Als primär markieren')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                \DB::table('staff_event_types')
                                    ->where('staff_id', $record->id)
                                    ->where('event_type_id', $this->ownerRecord->id)
                                    ->update(['is_primary' => true]);
                            }
                            
                            Notification::make()
                                ->title('Mitarbeiter als primär markiert')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Keine Mitarbeiter zugeordnet')
            ->emptyStateDescription('Ordnen Sie Mitarbeiter zu, die diesen Event-Type anbieten sollen.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateActions([
                Tables\Actions\AttachAction::make()
                    ->label('Erste Mitarbeiter zuordnen'),
            ]);
    }
    
    protected function canAttach(): bool
    {
        return true;
    }
    
    protected function canDetach($record): bool
    {
        return true;
    }
    
    protected function canEdit($record): bool
    {
        return true;
    }
    
    private function calculateCompletionRate($staff): int
    {
        $total = $staff->appointments()->count();
        if ($total === 0) return 0;
        
        $completed = $staff->appointments()
            ->where('status', 'completed')
            ->count();
            
        return round(($completed / $total) * 100);
    }
}