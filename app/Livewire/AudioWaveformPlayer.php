<?php

namespace App\Livewire;

use Livewire\Component;

class AudioWaveformPlayer extends Component
{
    public $audioUrl;
    public $duration;
    public $callId;
    
    // Player states
    public $isPlaying = false;
    public $currentTime = 0;
    public $volume = 75;
    public $playbackRate = 1.0;
    
    // Waveform data
    public $waveformData = [];
    public $peaks = [];
    
    protected $listeners = ['updateTime', 'togglePlayback'];
    
    public function mount($audioUrl, $duration, $callId)
    {
        $this->audioUrl = $audioUrl;
        $this->duration = $duration;
        $this->callId = $callId;
        $this->generateWaveformData();
    }
    
    protected function generateWaveformData()
    {
        // Generate mock waveform data for visualization
        // In production, this would analyze the actual audio file
        $samples = 100;
        for ($i = 0; $i < $samples; $i++) {
            // Create realistic-looking waveform patterns
            $base = sin($i * 0.2) * 0.3 + 0.5;
            $variation = (rand(0, 100) / 100) * 0.4;
            $this->waveformData[] = max(0.1, min(1, $base + $variation));
        }
        
        // Generate peak indicators
        $peakCount = rand(3, 7);
        for ($i = 0; $i < $peakCount; $i++) {
            $this->peaks[] = rand(10, 90);
        }
    }
    
    public function togglePlayback()
    {
        $this->isPlaying = !$this->isPlaying;
        
        if ($this->isPlaying) {
            $this->dispatchBrowserEvent('audio-play', [
                'url' => $this->audioUrl,
                'time' => $this->currentTime,
                'rate' => $this->playbackRate
            ]);
        } else {
            $this->dispatchBrowserEvent('audio-pause');
        }
    }
    
    public function seek($percentage)
    {
        $this->currentTime = ($percentage / 100) * $this->duration;
        $this->dispatchBrowserEvent('audio-seek', [
            'time' => $this->currentTime
        ]);
    }
    
    public function updateTime($time)
    {
        $this->currentTime = $time;
    }
    
    public function setVolume($volume)
    {
        $this->volume = $volume;
        $this->dispatchBrowserEvent('audio-volume', [
            'volume' => $volume / 100
        ]);
    }
    
    public function setPlaybackRate($rate)
    {
        $this->playbackRate = $rate;
        $this->dispatchBrowserEvent('audio-rate', [
            'rate' => $rate
        ]);
    }
    
    public function skipForward()
    {
        $this->seek(min(100, ($this->currentTime / $this->duration * 100) + 10));
    }
    
    public function skipBackward()
    {
        $this->seek(max(0, ($this->currentTime / $this->duration * 100) - 10));
    }
    
    public function downloadRecording()
    {
        return redirect()->away($this->audioUrl);
    }
    
    public function formatTime($seconds)
    {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
    }
    
    public function getProgressPercentage()
    {
        if ($this->duration == 0) return 0;
        return ($this->currentTime / $this->duration) * 100;
    }
    
    public function render()
    {
        return view('livewire.audio-waveform-player');
    }
}