<?php

namespace App\Filament\Admin\Widgets;

use App\Models\RetellAICallCampaign;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ProgressColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActiveCampaignsWidget extends BaseWidget
{
    protected static ?string $heading = 'Active Campaigns';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RetellAICallCampaign::query()
                    ->where('company_id', auth()->user()->company_id)
                    ->whereIn('status', ['running', 'scheduled', 'paused'])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->limit(30),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'scheduled',
                        'primary' => 'running',
                        'info' => 'paused',
                    ]),

                ProgressColumn::make('completion_percentage')
                    ->label('Progress')
                    ->getStateUsing(fn ($record) => $record->completion_percentage),

                TextColumn::make('calls_completed')
                    ->label('Completed')
                    ->alignCenter()
                    ->formatStateUsing(fn ($record) => $record->calls_completed . '/' . $record->total_targets),

                TextColumn::make('success_rate')
                    ->label('Success')
                    ->alignCenter()
                    ->formatStateUsing(fn ($record) => $record->success_rate . '%')
                    ->color(fn ($record) => match (true) {
                        $record->success_rate >= 80 => 'success',
                        $record->success_rate >= 60 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('M j, g:i A')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.admin.pages.ai-call-center') . '?campaign=' . $record->id),
            ])
            ->paginated([5])
            ->poll('30s');
    }
}
