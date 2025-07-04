// Phone Input Fix for Quick Setup Wizard
document.addEventListener('DOMContentLoaded', function() {
    console.log('Phone input fix loaded');
    
    // Function to fix phone input fields
    function fixPhoneInputs() {
        // Find all phone input fields
        const phoneInputs = document.querySelectorAll(
            'input[name*="phone"], input[name*="number"], input[type="tel"]'
        );
        
        phoneInputs.forEach(input => {
            console.log('Found phone input:', input.name);
            
            // Remove any readonly or disabled attributes
            input.removeAttribute('readonly');
            input.removeAttribute('disabled');
            
            // Ensure the input is not being blocked by any overlays
            input.style.zIndex = '9999';
            input.style.position = 'relative';
            
            // Add event listeners to debug
            input.addEventListener('focus', () => {
                console.log('Phone input focused:', input.name);
            });
            
            input.addEventListener('input', (e) => {
                console.log('Phone input changed:', input.name, e.target.value);
            });
            
            input.addEventListener('keydown', (e) => {
                console.log('Key pressed in phone input:', e.key);
            });
        });
    }
    
    // Run the fix immediately
    fixPhoneInputs();
    
    // Run again after a delay (for dynamic content)
    setTimeout(fixPhoneInputs, 1000);
    
    // Also run on Livewire updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            fixPhoneInputs();
        });
    }
});