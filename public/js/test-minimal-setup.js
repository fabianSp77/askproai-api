/**
 * Test Minimal Setup
 * Temporäres Script um zu testen, ob Buttons ohne unsere Fixes funktionieren
 */
console.log('🧪 Test Minimal Setup Active - Alle Fixes deaktiviert');

// Log wenn Livewire Events stattfinden
if (window.Livewire) {
    Livewire.hook('message.sent', (message, component) => {
        console.log('✅ Livewire message sent:', {
            component: component.fingerprint.name,
            calls: message.calls
        });
    });
    
    Livewire.hook('message.received', (message, component) => {
        console.log('✅ Livewire message received:', {
            component: component.fingerprint.name
        });
    });
}

// Log Alpine.js Events
document.addEventListener('alpine:init', () => {
    console.log('✅ Alpine.js initialized');
});

// Log alle Click Events auf Buttons
document.addEventListener('click', (e) => {
    if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
        console.log('🖱️ Button clicked:', e.target);
    }
}, true);