<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use App\Models\Company;
use App\Services\ServiceMatcher;
use Filament\Widgets\Widget;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Widgets\TableWidget as BaseWidget;

class ServiceAssignmentWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        try {
        return $table
            ->query(
                Service::query()
                    ->with(['company'])
                    ->whereNotNull('calcom_event_type_id')
            )
            ->heading('Service to Company Assignment')
            ->description('Manage service assignments to companies with AI-powered suggestions')
            ->columns([
                TextColumn::make('name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Service $record) => $record->description)
                    ->wrap(),

                TextColumn::make('company.name')
                    ->label('Current Company')
                    ->placeholder('Not assigned')
                    ->searchable()
                    ->sortable()
                    ->color(fn ($state) => $state ? 'success' : 'danger'),

                BadgeColumn::make('assignment_method')
                    ->label('Method')
                    ->colors([
                        'primary' => 'manual',
                        'success' => 'auto',
                        'warning' => 'suggested',
                        'info' => 'import',
                    ])
                    ->icons([
                        'heroicon-o-user' => 'manual',
                        'heroicon-o-cpu-chip' => 'auto',
                        'heroicon-o-light-bulb' => 'suggested',
                        'heroicon-o-arrow-down-tray' => 'import',
                    ]),

                TextColumn::make('assignment_confidence')
                    ->label('Confidence')
                    ->formatStateUsing(fn ($state) => $state ? "{$state}%" : null)
                    ->color(fn ($state) => match(true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'warning',
                        $state >= 40 => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('formatted_sync_status')
                    ->label('Cal.com Sync')
                    ->html(),
            ])
            ->filters([
                SelectFilter::make('assignment_status')
                    ->label('Status')
                    ->options([
                        'assigned' => 'Assigned',
                        'unassigned' => 'Not Assigned',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'assigned') {
                            return $query->whereNotNull('company_id');
                        }
                        if ($data['value'] === 'unassigned') {
                            return $query->whereNull('company_id');
                        }
                    }),

                SelectFilter::make('assignment_method')
                    ->label('Assignment Method')
                    ->multiple()
                    ->options([
                        'manual' => 'Manual',
                        'auto' => 'Auto',
                        'suggested' => 'Suggested',
                        'import' => 'Import',
                    ]),
            ])
            ->actions([
                Action::make('suggest')
                    ->label('Get Suggestions')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->action(function (Service $record) {
                        $matcher = app(ServiceMatcher::class);
                        $suggestions = $matcher->suggestCompanies($record);

                        if ($suggestions->isEmpty()) {
                            Notification::make()
                                ->title('No suggestions available')
                                ->danger()
                                ->send();
                            return;
                        }

                        $suggestionText = $suggestions->take(3)->map(function ($s) {
                            return "{$s['company']->name} ({$s['confidence']}%)";
                        })->join("\n");

                        Notification::make()
                            ->title('Top Suggestions')
                            ->body($suggestionText)
                            ->info()
                            ->persistent()
                            ->send();
                    }),

                Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-link')
                    ->form([
                        \Filament\Forms\Components\Select::make('company_id')
                            ->label('Select Company')
                            ->options(Company::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText(function (Service $record) {
                                $matcher = app(ServiceMatcher::class);
                                $suggestions = $matcher->suggestCompanies($record);
                                if ($suggestions->isNotEmpty()) {
                                    $top = $suggestions->first();
                                    return "Top suggestion: {$top['company']->name} ({$top['confidence']}%)";
                                }
                                return null;
                            }),
                    ])
                    ->action(function (Service $record, array $data) {
                        $record->update([
                            'company_id' => $data['company_id'],
                            'assignment_method' => 'manual',
                            'assignment_confidence' => null,
                            'assignment_notes' => 'Manually assigned via Filament widget',
                            'assignment_date' => now(),
                            'assigned_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Service assigned successfully')
                            ->success()
                            ->send();
                    }),

                Action::make('auto_assign')
                    ->label('Auto Assign')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (Service $record) {
                        $matcher = app(ServiceMatcher::class);
                        $suggestions = $matcher->suggestCompanies($record);
                        return $suggestions->isNotEmpty() && $suggestions->first()['confidence'] >= 80;
                    })
                    ->action(function (Service $record) {
                        $matcher = app(ServiceMatcher::class);
                        $company = $matcher->autoAssign($record, 80);

                        if ($company) {
                            Notification::make()
                                ->title('Auto-assigned successfully')
                                ->body("Assigned to {$company->name} with {$record->assignment_confidence}% confidence")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Auto-assignment failed')
                                ->body('Confidence too low for automatic assignment')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkAction::make('bulk_auto_assign')
                    ->label('Auto Assign Selected')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $matcher = app(ServiceMatcher::class);
                        $assigned = 0;
                        $failed = 0;

                        foreach ($records as $service) {
                            $company = $matcher->autoAssign($service, 70);
                            if ($company) {
                                $assigned++;
                            } else {
                                $failed++;
                            }
                        }

                        Notification::make()
                            ->title('Bulk assignment complete')
                            ->body("Assigned: {$assigned}, Failed: {$failed}")
                            ->success()
                            ->send();
                    }),

                BulkAction::make('bulk_assign_to_company')
                    ->label('Assign to Company')
                    ->icon('heroicon-o-link')
                    ->form([
                        \Filament\Forms\Components\Select::make('company_id')
                            ->label('Select Company')
                            ->options(Company::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $count = 0;
                        foreach ($records as $service) {
                            $service->update([
                                'company_id' => $data['company_id'],
                                'assignment_method' => 'manual',
                                'assignment_confidence' => null,
                                'assignment_notes' => 'Bulk manual assignment via Filament',
                                'assignment_date' => now(),
                                'assigned_by' => auth()->id(),
                            ]);
                            $count++;
                        }

                        Notification::make()
                            ->title('Bulk assignment complete')
                            ->body("{$count} services assigned to " . Company::find($data['company_id'])->name)
                            ->success()
                            ->send();
                    }),

                BulkAction::make('clear_assignments')
                    ->label('Clear Assignments')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        foreach ($records as $service) {
                            $service->update([
                                'company_id' => null,
                                'assignment_method' => null,
                                'assignment_confidence' => null,
                                'assignment_notes' => null,
                                'assignment_date' => null,
                                'assigned_by' => null,
                            ]);
                        }

                        Notification::make()
                            ->title('Assignments cleared')
                            ->success()
                            ->send();
                    }),
            ]);
        } catch (\Exception $e) {
            \Log::error('ServiceAssignmentWidget Error: ' . $e->getMessage());
            return $table
                ->query(Service::query()->whereRaw('0=1')) // Empty query on error
                ->columns([]);
        }
    }
}