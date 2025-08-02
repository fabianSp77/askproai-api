<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Quick Call Section --}}
        <div class="lg:col-span-1">
            <form wire:submit="makeQuickCall">
                {{ $this->form }}
                
                <div class="mt-4">
                    <x-filament::button type="submit" icon="heroicon-o-phone-arrow-up-right">
                        Initiate Call
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Campaigns Section --}}
        <div class="lg:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Call Campaigns
                </h2>
                <x-filament::button 
                    wire:click="createCampaign" 
                    icon="heroicon-o-plus"
                    size="sm"
                >
                    {{ $showCampaignForm ? 'Create Campaign' : 'New Campaign' }}
                </x-filament::button>
            </div>

            @if($showCampaignForm)
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <form wire:submit="createCampaign">
                        {{ $this->campaignForm }}
                        
                        <div class="mt-4 flex gap-2">
                            <x-filament::button type="submit" icon="heroicon-o-check">
                                Create Campaign
                            </x-filament::button>
                            <x-filament::button 
                                type="button"
                                color="gray" 
                                wire:click="$set('showCampaignForm', false)"
                            >
                                Cancel
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            @endif

            {{ $this->table }}
        </div>
    </div>

    {{-- Campaign Details Modal --}}
    <x-filament::modal id="campaign-details" width="4xl">
        @if($selectedCampaignId)
            @php
                $campaign = \App\Models\RetellAICallCampaign::find($selectedCampaignId);
            @endphp
            
            @if($campaign)
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold">{{ $campaign->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $campaign->description }}</p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold">{{ $campaign->total_targets }}</div>
                            <div class="text-sm text-gray-500">Total Targets</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-success-600">{{ $campaign->calls_completed }}</div>
                            <div class="text-sm text-gray-500">Completed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-danger-600">{{ $campaign->calls_failed }}</div>
                            <div class="text-sm text-gray-500">Failed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold">{{ $campaign->success_rate }}%</div>
                            <div class="text-sm text-gray-500">Success Rate</div>
                        </div>
                    </div>

                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">Campaign Details</h4>
                        <dl class="grid grid-cols-2 gap-2 text-sm">
                            <dt class="text-gray-500">Status:</dt>
                            <dd>
                                <x-filament::badge :color="match($campaign->status) {
                                    'draft' => 'gray',
                                    'scheduled' => 'warning',
                                    'running' => 'primary',
                                    'paused' => 'info',
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                }">
                                    {{ ucfirst($campaign->status) }}
                                </x-filament::badge>
                            </dd>
                            <dt class="text-gray-500">Target Type:</dt>
                            <dd>{{ ucfirst(str_replace('_', ' ', $campaign->target_type)) }}</dd>
                            <dt class="text-gray-500">Created:</dt>
                            <dd>{{ $campaign->created_at->format('M d, Y H:i') }}</dd>
                            @if($campaign->started_at)
                                <dt class="text-gray-500">Started:</dt>
                                <dd>{{ $campaign->started_at->format('M d, Y H:i') }}</dd>
                            @endif
                            @if($campaign->completed_at)
                                <dt class="text-gray-500">Completed:</dt>
                                <dd>{{ $campaign->completed_at->format('M d, Y H:i') }}</dd>
                            @endif
                        </dl>
                    </div>

                    @if($campaign->dynamic_variables && count($campaign->dynamic_variables) > 0)
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
                            <h4 class="font-semibold mb-2">Campaign Variables</h4>
                            <dl class="grid grid-cols-2 gap-2 text-sm">
                                @foreach($campaign->dynamic_variables as $key => $value)
                                    <dt class="text-gray-500">{{ $key }}:</dt>
                                    <dd>{{ $value }}</dd>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </x-filament::modal>

    @push('scripts')
        <script>
            // Auto-refresh campaign stats every 30 seconds when a campaign is running
            setInterval(() => {
                @this.call('$refresh');
            }, 30000);
        </script>
    @endpush
</x-filament-panels::page>