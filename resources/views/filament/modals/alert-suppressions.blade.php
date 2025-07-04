<div class="space-y-6">
    @php
        $suppressions = \DB::table('billing_alert_suppressions')
            ->where('company_id', $company->id)
            ->where('ends_at', '>', now())
            ->orderBy('starts_at', 'desc')
            ->get();
        
        $alertTypes = [
            'usage_limit' => 'Usage Limit Alerts',
            'payment_reminder' => 'Payment Reminders',
            'subscription_renewal' => 'Subscription Renewal Notices',
            'overage_warning' => 'Overage Warnings',
            'payment_failed' => 'Payment Failed Alerts',
            'budget_exceeded' => 'Budget Alerts',
        ];
    @endphp

    @if($suppressions->count() > 0)
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Active Suppressions</h3>
            <div class="space-y-3">
                @foreach($suppressions as $suppression)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $alertTypes[$suppression->alert_type] ?? ucfirst(str_replace('_', ' ', $suppression->alert_type)) }}
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    Until {{ \Carbon\Carbon::parse($suppression->ends_at)->format('M d, Y H:i') }}
                                </p>
                                @if($suppression->reason)
                                    <p class="text-sm text-gray-600 mt-2">
                                        Reason: {{ $suppression->reason }}
                                    </p>
                                @endif
                            </div>
                            <button 
                                wire:click="removeSuppression('{{ $suppression->id }}')"
                                class="text-sm text-red-600 hover:text-red-900">
                                Remove
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-6">
            <p class="text-gray-500">No active alert suppressions</p>
        </div>
    @endif

    <div class="border-t pt-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Suppression</h3>
        
        <form wire:submit.prevent="addSuppression" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Alert Type</label>
                <select wire:model="suppressionForm.alert_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Select alert type...</option>
                    @foreach($alertTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Duration</label>
                <select wire:model="suppressionForm.days" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="1">1 day</option>
                    <option value="3">3 days</option>
                    <option value="7">1 week</option>
                    <option value="14">2 weeks</option>
                    <option value="30">1 month</option>
                    <option value="90">3 months</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Reason (optional)</label>
                <textarea 
                    wire:model="suppressionForm.reason" 
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    placeholder="Enter reason for suppression..."></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Add Suppression
                </button>
            </div>
        </form>
    </div>
</div>