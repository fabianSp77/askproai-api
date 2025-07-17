<?php

namespace App\Livewire;

use App\Services\UnifiedSearchService;
use Livewire\Component;
use Illuminate\Support\Collection;

class GlobalSearch extends Component
{
    public string $query = '';
    public bool $isOpen = false;
    public Collection $results;
    public Collection $suggestions;
    public Collection $recentSearches;
    public int $selectedIndex = -1;
    public ?string $selectedCategory = null;
    
    protected $listeners = ['openGlobalSearch' => 'open'];
    
    protected UnifiedSearchService $searchService;

    public function boot(UnifiedSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function mount()
    {
        $this->results = collect();
        $this->suggestions = collect();
        $this->recentSearches = collect();
    }

    public function updatedQuery()
    {
        if (strlen($this->query) >= 2) {
            $this->search();
            $this->suggestions = $this->searchService->getSuggestions($this->query);
        } else {
            $this->results = collect();
            $this->suggestions = collect();
            $this->loadRecentSearches();
        }
        
        $this->selectedIndex = -1;
    }

    public function search()
    {
        $this->results = $this->searchService->search($this->query, $this->selectedCategory);
        $this->isOpen = true;
    }

    public function selectCategory(?string $category)
    {
        $this->selectedCategory = $category;
        if ($this->query) {
            $this->search();
        }
    }

    public function selectResult(int $index)
    {
        if (isset($this->results[$index])) {
            $result = $this->results[$index];
            
            // Record selection in history
            if (auth()->check()) {
                \App\Models\SearchHistory::where('user_id', auth()->id())
                    ->where('query', $this->query)
                    ->latest()
                    ->first()
                    ?->update([
                        'selected_type' => $result['type'],
                        'selected_id' => $result['id'],
                    ]);
            }
            
            // Navigate to result
            return redirect($result['route']);
        }
    }

    public function quickAction(int $index, string $action)
    {
        if (!isset($this->results[$index])) return;
        
        $result = $this->results[$index];
        
        switch ($action) {
            case 'call':
                $this->dispatch('openCallDialog', ['customerId' => $result['id']]);
                break;
            case 'appointment':
                $this->dispatch('openAppointmentDialog', ['customerId' => $result['id']]);
                break;
            case 'edit':
                return redirect($result['route'] . '/edit');
            case 'play':
                $this->dispatch('playCallRecording', ['callId' => $result['id']]);
                break;
        }
        
        $this->close();
    }

    public function open()
    {
        $this->isOpen = true;
        $this->loadRecentSearches();
        $this->dispatch('focusSearchInput');
    }

    public function close()
    {
        $this->isOpen = false;
        $this->query = '';
        $this->results = collect();
        $this->selectedIndex = -1;
    }

    public function loadRecentSearches()
    {
        $this->recentSearches = $this->searchService->getRecentSearches();
    }

    public function searchRecent(string $query)
    {
        $this->query = $query;
        $this->search();
    }

    public function navigateResults($direction)
    {
        if ($this->results->isEmpty()) return;
        
        if ($direction === 'up') {
            $this->selectedIndex = max(0, $this->selectedIndex - 1);
        } else {
            $this->selectedIndex = min($this->results->count() - 1, $this->selectedIndex + 1);
        }
    }

    public function selectHighlighted()
    {
        if ($this->selectedIndex >= 0 && $this->selectedIndex < $this->results->count()) {
            $this->selectResult($this->selectedIndex);
        }
    }

    public function render()
    {
        return view('livewire.global-search', [
            'categories' => [
                'customers' => ['label' => 'Kunden', 'icon' => 'heroicon-o-user'],
                'appointments' => ['label' => 'Termine', 'icon' => 'heroicon-o-calendar'],
                'calls' => ['label' => 'Anrufe', 'icon' => 'heroicon-o-phone'],
                'staff' => ['label' => 'Mitarbeiter', 'icon' => 'heroicon-o-user-group'],
            ],
        ]);
    }
}