<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class UserViewer extends Component
{
    public $userId;
    public User $user;
    public $activeTab = 'overview';
    
    public function mount($userId)
    {
        $this->userId = $userId;
        $this->user = User::findOrFail($userId);
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function render()
    {
        return view('livewire.user-viewer', [
            'user' => $this->user,
            'activeTab' => $this->activeTab,
        ]);
    }
}