<?php

namespace App\Livewire;

use App\Services\TutorialService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class TutorialOverlay extends Component
{
    public $tutorials = [];
    public $currentTutorial = null;
    public $showTutorial = false;
    public $tutorialProgress = [];

    protected $listeners = [
        'showTutorial' => 'loadTutorial',
        'tutorialCompleted' => 'markCompleted',
        'skipAllTutorials' => 'skipAll',
    ];

    public function mount()
    {
        $this->loadTutorials();
    }

    public function loadTutorials()
    {
        $tutorialService = app(TutorialService::class);
        $currentRoute = request()->path();
        $user = Auth::user();

        if ($user) {
            $this->tutorials = $tutorialService->getTutorialsForCurrentPage($currentRoute, $user)->toArray();
            $this->tutorialProgress = $tutorialService->getUserProgress($user);
            
            // Auto-show first unviewed tutorial
            $nextTutorial = $tutorialService->getNextTutorial($user, $currentRoute);
            if ($nextTutorial && !session('tutorials_disabled')) {
                $this->loadTutorial($nextTutorial->id);
            }
        }
    }

    public function loadTutorial($tutorialId)
    {
        $tutorial = collect($this->tutorials)->firstWhere('id', $tutorialId);
        if ($tutorial) {
            $this->currentTutorial = $tutorial;
            $this->showTutorial = true;
            
            // Mark as viewed
            $tutorialService = app(TutorialService::class);
            $tutorialService->markAsViewed(Auth::user(), $tutorialId);
        }
    }

    public function nextTutorial()
    {
        if (!$this->currentTutorial) return;
        
        $currentIndex = collect($this->tutorials)->search(function ($item) {
            return $item['id'] === $this->currentTutorial['id'];
        });
        
        if ($currentIndex !== false && isset($this->tutorials[$currentIndex + 1])) {
            $this->loadTutorial($this->tutorials[$currentIndex + 1]['id']);
        } else {
            $this->closeTutorial();
        }
    }

    public function previousTutorial()
    {
        if (!$this->currentTutorial) return;
        
        $currentIndex = collect($this->tutorials)->search(function ($item) {
            return $item['id'] === $this->currentTutorial['id'];
        });
        
        if ($currentIndex !== false && $currentIndex > 0) {
            $this->loadTutorial($this->tutorials[$currentIndex - 1]['id']);
        }
    }

    public function markCompleted()
    {
        if ($this->currentTutorial) {
            $tutorialService = app(TutorialService::class);
            $tutorialService->markAsCompleted(Auth::user(), $this->currentTutorial['id']);
            
            // Reload tutorials to update completion status
            $this->loadTutorials();
            
            // Move to next tutorial
            $this->nextTutorial();
        }
    }

    public function closeTutorial()
    {
        $this->showTutorial = false;
        $this->currentTutorial = null;
    }

    public function skipAll()
    {
        session(['tutorials_disabled' => true]);
        $this->closeTutorial();
    }

    public function render()
    {
        return view('livewire.tutorial-overlay');
    }
}