<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Configuration Status -->
        @if($this->configStatus)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium mb-4">Configuration Status</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="flex items-center space-x-2">
                    @if($this->configStatus['account_sid'])
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                        <span class="text-sm">Account SID</span>
                    @else
                        <x-heroicon-o-x-circle class="w-5 h-5 text-red-500" />
                        <span class="text-sm">Account SID Missing</span>
                    @endif
                </div>
                
                <div class="flex items-center space-x-2">
                    @if($this->configStatus['auth_token'])
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                        <span class="text-sm">Auth Token</span>
                    @else
                        <x-heroicon-o-x-circle class="w-5 h-5 text-red-500" />
                        <span class="text-sm">Auth Token Missing</span>
                    @endif
                </div>
                
                <div class="flex items-center space-x-2">
                    @if($this->configStatus['phone_number'])
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                        <span class="text-sm">Phone Number</span>
                    @else
                        <x-heroicon-o-x-circle class="w-5 h-5 text-red-500" />
                        <span class="text-sm">Phone Missing</span>
                    @endif
                </div>
                
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-chart-bar class="w-5 h-5 text-blue-500" />
                    <span class="text-sm">{{ $this->configStatus['messages_sent'] }} Messages Sent</span>
                </div>
            </div>
            
            @if(!$this->configStatus['account_sid'] || !$this->configStatus['auth_token'] || !$this->configStatus['phone_number'])
            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    <strong>Configuration Required:</strong> Add your Twilio credentials to the .env file:
                </p>
                <pre class="mt-2 text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded">TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=+49xxxxxxxxx</pre>
            </div>
            @endif
        </div>
        @endif
        
        <!-- Main Form -->
        <form wire:submit.prevent="sendMessage">
            {{ $this->form }}
            
            <div class="mt-6 flex justify-end space-x-3">
                <x-filament::button type="submit" color="primary">
                    {{ $this->testMode ? 'Validate Message' : 'Send Message' }}
                </x-filament::button>
            </div>
        </form>
        
        <!-- Validation Result -->
        @if($this->validationResult)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium mb-4">Phone Validation Result</h3>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Valid</dt>
                    <dd class="text-sm">
                        @if($this->validationResult['valid'])
                            <span class="text-green-600">✓ Yes</span>
                        @else
                            <span class="text-red-600">✗ No</span>
                        @endif
                    </dd>
                </div>
                @if($this->validationResult['valid'])
                <div>
                    <dt class="text-sm font-medium text-gray-500">Formatted</dt>
                    <dd class="text-sm font-mono">{{ $this->validationResult['formatted'] }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">National</dt>
                    <dd class="text-sm font-mono">{{ $this->validationResult['national'] }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Country Code</dt>
                    <dd class="text-sm font-mono">{{ $this->validationResult['country_code'] }}</dd>
                </div>
                @else
                <div>
                    <dt class="text-sm font-medium text-gray-500">Error</dt>
                    <dd class="text-sm text-red-600">{{ $this->validationResult['error'] ?? 'Unknown error' }}</dd>
                </div>
                @endif
            </dl>
        </div>
        @endif
        
        <!-- Last Result -->
        @if($this->lastResult)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium mb-4">Last Test Result</h3>
            
            @if(isset($this->lastResult['type']) && $this->lastResult['type'] === 'rates')
                <!-- Messaging Rates -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMS Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WhatsApp Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->lastResult['rates'] as $country => $rates)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $country }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">€{{ number_format($rates['sms'], 4) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">€{{ number_format($rates['whatsapp'], 4) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Message Result -->
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="text-sm">
                            @if($this->lastResult['success'] ?? false)
                                <span class="text-green-600">✓ Success</span>
                            @else
                                <span class="text-red-600">✗ Failed</span>
                            @endif
                        </dd>
                    </div>
                    
                    @if($this->lastResult['test_mode'] ?? false)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Mode</dt>
                        <dd class="text-sm text-blue-600">Test Mode</dd>
                    </div>
                    @endif
                    
                    @if(isset($this->lastResult['message_sid']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Message SID</dt>
                        <dd class="text-sm font-mono">{{ $this->lastResult['message_sid'] }}</dd>
                    </div>
                    @endif
                    
                    @if(isset($this->lastResult['to']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500">To</dt>
                        <dd class="text-sm">{{ $this->lastResult['to'] }}</dd>
                    </div>
                    @endif
                    
                    @if(isset($this->lastResult['status']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="text-sm">{{ $this->lastResult['status'] }}</dd>
                    </div>
                    @endif
                    
                    @if(isset($this->lastResult['price']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Cost</dt>
                        <dd class="text-sm">{{ $this->lastResult['price'] }} {{ $this->lastResult['price_unit'] }}</dd>
                    </div>
                    @endif
                    
                    @if(isset($this->lastResult['error']))
                    <div class="col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Error</dt>
                        <dd class="text-sm text-red-600">{{ $this->lastResult['error'] }}</dd>
                    </div>
                    @endif
                </dl>
            @endif
        </div>
        @endif
        
        <!-- Recent Messages -->
        @if($this->recentMessages && count($this->recentMessages) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium mb-4">Recent Messages</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Channel</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->recentMessages as $msg)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ \Carbon\Carbon::parse($msg->created_at)->format('d.m. H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $msg->channel === 'whatsapp' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ strtoupper($msg->channel) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $msg->to }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $msg->status === 'delivered' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $msg->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $msg->status === 'queued' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                ">
                                    {{ $msg->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm truncate max-w-xs" title="{{ $msg->message }}">
                                {{ \Str::limit($msg->message, 50) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        
        <!-- Webhook URLs -->
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Webhook Configuration</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Configure these URLs in your Twilio dashboard:
            </p>
            <dl class="space-y-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status Callback URL:</dt>
                    <dd class="text-sm font-mono bg-white dark:bg-gray-800 p-2 rounded">{{ url('/api/twilio/status-callback') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Incoming Message URL:</dt>
                    <dd class="text-sm font-mono bg-white dark:bg-gray-800 p-2 rounded">{{ url('/api/twilio/incoming-message') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</x-filament-panels::page>