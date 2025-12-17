<?php

namespace App\Filament\Resources;

use Filament\Facades\Filament;

use App\Filament\Resources\ConversationFlowResource\Pages;
use App\Models\ConversationFlow;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class ConversationFlowResource extends Resource
{
    protected static ?string $model = ConversationFlow::class;

    /**
     * Resource disabled - conversation_flows table doesn't exist in Sept 21 database backup
     * TODO: Re-enable when database is fully restored
     */
    public static function shouldRegisterNavigation(): bool
    {
        // ✅ Super admin can see all resources
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }


    public static function canViewAny(): bool
    {
        // ✅ Super admin can access all resources
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Conversation Flow';
    protected static ?string $navigationGroup = 'Retell AI';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->query(function () {
                // Return empty query - data will come from getTableRecords
                return (new ConversationFlow)->newQuery();
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Flow Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_nodes')
                    ->label('Nodes')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_transitions')
                    ->label('Transitions')
                    ->badge(),
                Tables\Columns\TextColumn::make('model')
                    ->label('LLM Model')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'generated' => 'warning',
                        'deployed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('download_json')
                    ->label('Download JSON')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => route('conversation-flow.download-json'))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('view_guide')
                    ->label('Setup Guide')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => route('conversation-flow.download-guide'))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('deploy')
                    ->label('Deploy to Retell')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Deploy Conversation Flow')
                    ->modalDescription('This will deploy the conversation flow to Retell.ai. Make sure the agent ID is correct.')
                    ->form([
                        Forms\Components\TextInput::make('agent_id')
                            ->label('Retell Agent ID')
                            ->default('agent_616d645570ae613e421edb98e7')
                            ->required()
                    ])
                    ->action(function (array $data) {
                        $agentId = $data['agent_id'];

                        // Execute deployment via Artisan
                        \Illuminate\Support\Facades\Artisan::call('conversation-flow:deploy', [
                            'agent_id' => $agentId,
                            '--no-interaction' => true
                        ]);

                        $output = \Illuminate\Support\Facades\Artisan::output();

                        if (str_contains($output, 'successful')) {
                            Notification::make()
                                ->title('Deployment Successful')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Deployment Failed')
                                ->body($output)
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversationFlows::route('/'),
        ];
    }
}
