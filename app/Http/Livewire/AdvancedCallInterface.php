<?php

namespace App\Http\Livewire;

use App\Models\Call;
use App\Models\Customer;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AdvancedCallInterface extends Component
{
    use WithPagination;

    // Public properties for filtering and state
    public $searchTerm = '';
    public $activeFilters = [];
    public $selectedCalls = [];
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showRealTimeUpdates = true;
    public $currentView = 'all'; // all, active, priority, completed
    public $bulkActionMode = false;
    
    // Real-time properties
    public $lastUpdate;
    public $activeCalls = [];
    public $queueStats = [];
    
    // Filter presets
    public $filterPresets = [
        'today' => ['label' => 'Today\'s Calls', 'icon' => '📅'],
        'priority' => ['label' => 'High Priority', 'icon' => '🔥'],
        'active' => ['label' => 'Active Calls', 'icon' => '🟢'],
        'appointments' => ['label' => 'With Appointments', 'icon' => '✅'],
        'missed' => ['label' => 'Missed Calls', 'icon' => '❌'],
    ];

    protected $listeners = [
        'refreshCallData' => 'loadCallData',
        'applyFilterPreset' => 'applyFilterPreset',
        'clearFilters' => 'clearFilters',
        'updateCallPriority' => 'updateCallPriority',
        'refreshQueue' => 'loadQueueData',
        'updateNavigationBadges' => '$refresh',
    ];

    public function mount()
    {
        $this->loadCallData();
        $this->loadQueueData();
        $this->lastUpdate = now()->format('H:i:s');
    }

    public function render()
    {
        $calls = $this->getFilteredCalls();
        
        return view('livewire.advanced-call-interface', [
            'calls' => $calls,
            'activeCalls' => $this->activeCalls,
            'queueStats' => $this->queueStats,
            'filterPresets' => $this->filterPresets,
        ]);
    }

    public function loadCallData()
    {
        // Load active calls for real-time monitoring
        $this->activeCalls = Cache::remember('active_calls_' . auth()->id(), 30, function () {
            return Call::whereNull('end_timestamp')
                ->where('created_at', '>', now()->subHours(2))
                ->with(['customer', 'branch'])
                ->get()
                ->map(function ($call) {
                    return [
                        'id' => $call->id,
                        'customer_name' => $call->customer?->name ?? 'Unknown',
                        'from_number' => $call->from_number,
                        'duration' => $call->start_timestamp 
                            ? now()->diffInSeconds($call->start_timestamp) 
                            : 0,
                        'status' => $call->call_status,
                        'priority' => $call->priority ?? 'normal',
                    ];
                })
                ->toArray();
        });

        $this->lastUpdate = now()->format('H:i:s');
    }

    public function loadQueueData()
    {
        $this->queueStats = Cache::remember('queue_stats_' . auth()->id(), 60, function () {
            return [
                'waiting' => Call::where('call_status', 'waiting')->count(),
                'in_progress' => Call::where('call_status', 'in_progress')->count(),
                'completed_today' => Call::whereDate('created_at', today())
                    ->where('call_status', 'completed')
                    ->count(),
                'high_priority' => Call::where('priority', 'high')
                    ->whereNull('end_timestamp')
                    ->count(),
                'average_duration' => Call::whereDate('created_at', today())
                    ->whereNotNull('duration_sec')
                    ->avg('duration_sec') ?? 0,
            ];
        });
    }

    public function getFilteredCalls()
    {
        $query = Call::with(['customer', 'appointment', 'branch']);

        // Apply search
        if (!empty($this->searchTerm)) {
            $query->where(function ($q) {
                $q->where('from_number', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('call_id', 'like', '%' . $this->searchTerm . '%')
                  ->orWhereHas('customer', function ($customerQuery) {
                      $customerQuery->where('name', 'like', '%' . $this->searchTerm . '%')
                                   ->orWhere('email', 'like', '%' . $this->searchTerm . '%');
                  });
            });
        }

        // Apply view filters
        switch ($this->currentView) {
            case 'active':
                $query->whereNull('end_timestamp')
                      ->where('created_at', '>', now()->subHours(2));
                break;
            case 'priority':
                $query->where('priority', 'high');
                break;
            case 'completed':
                $query->where('call_status', 'completed');
                break;
        }

        // Apply active filters
        foreach ($this->activeFilters as $filter => $value) {
            switch ($filter) {
                case 'time_range':
                    $this->applyTimeRangeFilter($query, $value);
                    break;
                case 'appointment_made':
                    $query->where('appointment_made', $value);
                    break;
                case 'call_status':
                    $query->where('call_status', $value);
                    break;
                case 'priority':
                    $query->where('priority', $value);
                    break;
            }
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate(15);
    }

    private function applyTimeRangeFilter($query, $range)
    {
        switch ($range) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'yesterday':
                $query->whereDate('created_at', today()->subDay());
                break;
            case 'this_week':
                $query->whereBetween('created_at', [
                    now()->startOfWeek(), 
                    now()->endOfWeek()
                ]);
                break;
            case 'this_month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
        }
    }

    public function applyFilterPreset($preset)
    {
        $this->activeFilters = [];

        switch ($preset) {
            case 'today':
                $this->activeFilters['time_range'] = 'today';
                break;
            case 'priority':
                $this->activeFilters['priority'] = 'high';
                break;
            case 'active':
                $this->currentView = 'active';
                break;
            case 'appointments':
                $this->activeFilters['appointment_made'] = true;
                break;
            case 'missed':
                $this->activeFilters['call_status'] = 'missed';
                break;
        }

        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->activeFilters = [];
        $this->currentView = 'all';
        $this->searchTerm = '';
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updateCallPriority($callId, $priority)
    {
        $call = Call::find($callId);
        if ($call) {
            $call->update([
                'priority' => $priority,
                'priority_updated_at' => now(),
                'priority_updated_by' => auth()->id(),
            ]);

            // Clear caches
            Cache::tags(['calls', 'priority'])->flush();
            
            $this->emit('notify', [
                'type' => 'success',
                'message' => "Call priority updated to {$priority}"
            ]);

            $this->loadCallData();
        }
    }

    public function toggleBulkActionMode()
    {
        $this->bulkActionMode = !$this->bulkActionMode;
        $this->selectedCalls = [];
    }

    public function selectCall($callId)
    {
        if (in_array($callId, $this->selectedCalls)) {
            $this->selectedCalls = array_diff($this->selectedCalls, [$callId]);
        } else {
            $this->selectedCalls[] = $callId;
        }
    }

    public function bulkUpdatePriority($priority)
    {
        if (empty($this->selectedCalls)) {
            $this->emit('notify', [
                'type' => 'warning',
                'message' => 'Please select calls to update'
            ]);
            return;
        }

        Call::whereIn('id', $this->selectedCalls)->update([
            'priority' => $priority,
            'priority_updated_at' => now(),
            'priority_updated_by' => auth()->id(),
        ]);

        $count = count($this->selectedCalls);
        $this->selectedCalls = [];
        $this->bulkActionMode = false;

        Cache::tags(['calls', 'priority'])->flush();

        $this->emit('notify', [
            'type' => 'success',
            'message' => "Updated priority for {$count} calls"
        ]);

        $this->loadCallData();
    }

    public function exportSelected()
    {
        if (empty($this->selectedCalls)) {
            $this->emit('notify', [
                'type' => 'warning',
                'message' => 'Please select calls to export'
            ]);
            return;
        }

        // Trigger export job
        $this->emit('exportCalls', $this->selectedCalls);
        
        $this->emit('notify', [
            'type' => 'info',
            'message' => 'Export started. You will receive an email when ready.'
        ]);
    }

    public function refreshAll()
    {
        $this->loadCallData();
        $this->loadQueueData();
        
        $this->emit('notify', [
            'type' => 'success',
            'message' => 'Data refreshed successfully'
        ]);
    }

    public function switchView($view)
    {
        $this->currentView = $view;
        $this->resetPage();
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function getCallPriorityColor($priority)
    {
        return match($priority) {
            'high' => 'text-red-600 bg-red-100',
            'medium' => 'text-yellow-600 bg-yellow-100',
            'low' => 'text-green-600 bg-green-100',
            default => 'text-gray-600 bg-gray-100'
        };
    }

    public function getCallStatusColor($status)
    {
        return match($status) {
            'completed' => 'text-green-600 bg-green-100',
            'in_progress' => 'text-blue-600 bg-blue-100',
            'waiting' => 'text-yellow-600 bg-yellow-100',
            'missed' => 'text-red-600 bg-red-100',
            'error' => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100'
        };
    }

    public function formatDuration($seconds)
    {
        if (!$seconds) return '—';
        
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}