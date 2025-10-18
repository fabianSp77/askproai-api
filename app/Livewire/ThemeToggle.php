<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Log;

/**
 * ThemeToggle Component
 *
 * Manages light/dark mode theme switching with localStorage persistence
 *
 * Features:
 * - Toggle between light and dark modes
 * - Persist preference in localStorage
 * - Respect system preference (prefers-color-scheme)
 * - Smooth CSS transitions between themes
 *
 * Usage:
 * <livewire:theme-toggle />
 */
class ThemeToggle extends Component
{
    /**
     * Current theme mode
     * 'light' or 'dark'
     *
     * @var string
     */
    public string $theme = 'light';

    /**
     * Component mount
     * Load theme preference from browser
     */
    public function mount(): void
    {
        // Theme preference will be loaded client-side via Alpine/JavaScript
        // This ensures we respect browser's localStorage first
        Log::debug('[ThemeToggle] Component mounted');
    }

    /**
     * Toggle between light and dark mode
     *
     * Called from JavaScript/Alpine
     * Updates the theme and broadcasts to all components
     */
    public function toggleTheme(): void
    {
        $this->theme = $this->theme === 'light' ? 'dark' : 'light';

        Log::info('[ThemeToggle] Theme toggled', [
            'new_theme' => $this->theme,
        ]);

        // Dispatch event for other components to react
        $this->dispatch('theme-changed', theme: $this->theme);

        // JavaScript will handle localStorage and HTML class update
        $this->js("window.setTheme('{$this->theme}')");
    }

    /**
     * Set theme explicitly
     *
     * @param string $mode 'light' or 'dark'
     */
    public function setTheme(string $mode): void
    {
        if (!in_array($mode, ['light', 'dark'])) {
            return;
        }

        $this->theme = $mode;

        Log::info('[ThemeToggle] Theme set', [
            'theme' => $this->theme,
        ]);

        $this->dispatch('theme-changed', theme: $this->theme);
        $this->js("window.setTheme('{$this->theme}')");
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.theme-toggle');
    }
}
