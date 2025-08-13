import 'flowbite';

// Initialize Flowbite components when Livewire updates
document.addEventListener('livewire:navigated', () => {
    initFlowbite();
});

// Also initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initFlowbite();
});