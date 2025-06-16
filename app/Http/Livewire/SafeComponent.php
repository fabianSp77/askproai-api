<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Log;

abstract class SafeComponent extends Component
{
    protected function handleComponentError(\Exception $e)
    {
        Log::error('Livewire Component Error', [
            'component' => static::class,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'url' => request()->url(),
        ]);
        
        session()->flash('error', 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
        
        // Prevent redirect to dashboard
        $this->skipRender();
    }
    
    public function mount()
    {
        try {
            $this->safeMount();
        } catch (\Exception $e) {
            $this->handleComponentError($e);
        }
    }
    
    public function render()
    {
        try {
            return $this->safeRender();
        } catch (\Exception $e) {
            $this->handleComponentError($e);
            return view('livewire.error-fallback');
        }
    }
    
    // Override these in child components
    protected function safeMount()
    {
        // Default empty implementation
    }
    
    abstract protected function safeRender();
}