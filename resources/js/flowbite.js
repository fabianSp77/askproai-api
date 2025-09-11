import 'flowbite';
import { initFlowbite } from 'flowbite';

// Initialize Flowbite components when Livewire updates
document.addEventListener('livewire:navigated', () => {
    initFlowbite();
});

// Also initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initFlowbite();
});

// Re-initialize when Alpine updates
document.addEventListener('alpine:initialized', () => {
    initFlowbite();
});