@php
    $call = $this->record;
    $sentimentEmoji = match($call->sentiment) {
        'positive' => '😊',
        'negative' => '😔',
        default => '😐'
    };
    
    $sentimentColor = match($call->sentiment) {
        'positive' => 'text-green-600',
        'negative' => 'text-red-600',
        default => 'text-gray-600'
    };
    
    // Mock analytics data (in real app, this would come from the analysis)
    $keywords = ['appointment', 'schedule', 'service', 'consultation'];
    $topics = ['General Inquiry' => 40, 'Appointment Booking' => 35, 'Service Question' => 25];
@endphp

<div class="call-analytics-container space-y-4">
    <!-- Sentiment Overview -->
    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div>
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Overall Sentiment</h4>
            <p class="text-2xl font-bold {{ $sentimentColor }} mt-1">
                {{ $sentimentEmoji }} {{ ucfirst($call->sentiment) }}
            </p>
        </div>
        <div class="text-right">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Confidence Score</h4>
            <p class="text-2xl font-bold text-primary-600 mt-1">
                {{ $call->sentiment_score }}/10
            </p>
        </div>
    </div>

    <!-- Key Topics -->
    <div>
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Topics Discussed</h4>
        <div class="space-y-2">
            @foreach($topics as $topic => $percentage)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>{{ $topic }}</span>
                        <span class="text-gray-500">{{ $percentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Keywords -->
    <div>
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Key Terms</h4>
        <div class="flex flex-wrap gap-2">
            @foreach($keywords as $keyword)
                <span class="px-3 py-1 bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 rounded-full text-sm">
                    {{ $keyword }}
                </span>
            @endforeach
        </div>
    </div>

    <!-- Call Metrics -->
    <div class="grid grid-cols-2 gap-3">
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
            <x-heroicon-o-clock class="w-6 h-6 text-gray-400 mx-auto mb-1" />
            <p class="text-sm text-gray-500">Talk Time</p>
            <p class="font-semibold">{{ gmdate('i:s', $call->duration) }}</p>
        </div>
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
            <x-heroicon-o-chat-bubble-left-right class="w-6 h-6 text-gray-400 mx-auto mb-1" />
            <p class="text-sm text-gray-500">Words Spoken</p>
            <p class="font-semibold">{{ rand(200, 800) }}</p>
        </div>
    </div>

    <!-- AI Insights -->
    <div class="p-3 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg">
        <h4 class="text-sm font-medium text-purple-800 dark:text-purple-300 mb-2 flex items-center gap-1">
            <x-heroicon-o-sparkles class="w-4 h-4" />
            AI Insights
        </h4>
        <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
            <li class="flex items-start gap-2">
                <x-heroicon-o-check-circle class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" />
                <span>Customer expressed interest in booking an appointment</span>
            </li>
            <li class="flex items-start gap-2">
                <x-heroicon-o-information-circle class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" />
                <span>Preferred time slot: Mornings (9-11 AM)</span>
            </li>
            <li class="flex items-start gap-2">
                <x-heroicon-o-light-bulb class="w-4 h-4 text-yellow-500 mt-0.5 flex-shrink-0" />
                <span>Follow-up recommended within 24 hours</span>
            </li>
        </ul>
    </div>
</div>