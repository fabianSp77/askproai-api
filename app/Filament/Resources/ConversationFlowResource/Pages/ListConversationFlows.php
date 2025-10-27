<?php

namespace App\Filament\Resources\ConversationFlowResource\Pages;

use App\Filament\Resources\ConversationFlowResource;
use App\Models\ConversationFlow;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

class ListConversationFlows extends ListRecords
{
    protected static string $resource = ConversationFlowResource::class;
    protected static string $view = 'filament.resources.conversation-flow.pages.list-conversation-flows';

    // Computed property - called every time the view is rendered
    public function getFlowDataProperty(): array
    {
        $nodeGraphPath = 'conversation_flow/graphs/node_graph.json';

        if (!Storage::disk('local')->exists($nodeGraphPath)) {
            return [];
        }

        $nodeGraph = json_decode(Storage::disk('local')->get($nodeGraphPath), true);

        return [
            'id' => 1,
            'name' => 'AskPro AI Appointment Booking Flow',
            'total_nodes' => $nodeGraph['total_nodes'] ?? 0,
            'total_transitions' => $nodeGraph['total_transitions'] ?? 0,
            'model' => 'gpt-4o-mini',
            'status' => 'generated',
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'flowData' => $this->flowData,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('regenerate')
                ->label('Regenerate Agent')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Regenerate Conversation Flow')
                ->modalDescription('This will regenerate the conversation flow from scratch. This may take a few moments.')
                ->action(function () {
                    try {
                        \Illuminate\Support\Facades\Artisan::call('conversation-flow:migrate');

                        \Filament\Notifications\Notification::make()
                            ->title('Agent Regenerated Successfully')
                            ->body('The conversation flow has been regenerated successfully.')
                            ->success()
                            ->send();

                        // Redirect to reload the page
                        return redirect()->route('filament.admin.resources.conversation-flows.index');

                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Regeneration Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('view_reports')
                ->label('View Reports')
                ->icon('heroicon-o-document-chart-bar')
                ->url(fn () => route('conversation-flow.reports'))
                ->openUrlInNewTab(),
        ];
    }
}
