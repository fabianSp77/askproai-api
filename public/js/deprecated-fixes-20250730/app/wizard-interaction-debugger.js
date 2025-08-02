/**
 * Wizard Interaction Debugger
 * Helps diagnose interaction issues in the wizard
 */

window.WizardDebugger = {
    enabled: false,
    
    enable() {
        this.enabled = true;
        console.log('🔍 Wizard Debugger Enabled');
        this.attachListeners();
        this.checkLivewire();
        this.showDebugOverlay();
    },
    
    disable() {
        this.enabled = false;
        console.log('🔍 Wizard Debugger Disabled');
        this.removeListeners();
        this.hideDebugOverlay();
    },
    
    checkLivewire() {
        console.group('🔌 Livewire Status');
        console.log('Livewire available:', typeof Livewire !== 'undefined');
        if (typeof Livewire !== 'undefined') {
            console.log('Livewire version:', Livewire.version || 'Unknown');
            console.log('Components:', Object.keys(Livewire.components || {}).length);
        }
        console.groupEnd();
    },
    
    attachListeners() {
        // Log all clicks
        document.addEventListener('click', this.logClick, true);
        
        // Log Livewire events
        if (typeof Livewire !== 'undefined') {
            Livewire.on('message.sent', (message) => {
                console.log('📤 Livewire message sent:', message);
            });
            
            Livewire.on('message.received', (message) => {
                console.log('📥 Livewire message received:', message);
            });
            
            Livewire.on('message.failed', (message) => {
                console.error('❌ Livewire message failed:', message);
            });
        }
        
        // Log form changes
        document.addEventListener('change', this.logChange, true);
        document.addEventListener('input', this.logInput, true);
    },
    
    removeListeners() {
        document.removeEventListener('click', this.logClick, true);
        document.removeEventListener('change', this.logChange, true);
        document.removeEventListener('input', this.logInput, true);
    },
    
    logClick(e) {
        if (!WizardDebugger.enabled) return;
        
        const target = e.target;
        const computed = window.getComputedStyle(target);
        
        console.group(`🖱️ Click on ${target.tagName} ${target.className}`);
        console.log('Element:', target);
        console.log('Pointer Events:', computed.pointerEvents);
        console.log('Z-Index:', computed.zIndex);
        console.log('Position:', computed.position);
        console.log('Wire Model:', target.getAttribute('wire:model'));
        console.log('Event Propagation Stopped:', e.defaultPrevented);
        console.groupEnd();
    },
    
    logChange(e) {
        if (!WizardDebugger.enabled) return;
        
        console.group(`🔄 Change on ${e.target.tagName}`);
        console.log('Element:', e.target);
        console.log('Value:', e.target.value);
        console.log('Wire Model:', e.target.getAttribute('wire:model'));
        console.groupEnd();
    },
    
    logInput(e) {
        if (!WizardDebugger.enabled) return;
        
        // Only log every 500ms to avoid spam
        if (e.target._lastLog && Date.now() - e.target._lastLog < 500) return;
        e.target._lastLog = Date.now();
        
        console.log(`⌨️ Input on ${e.target.tagName}:`, e.target.value);
    },
    
    showDebugOverlay() {
        let overlay = document.getElementById('wizard-debug-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'wizard-debug-overlay';
            overlay.className = 'wizard-debug-overlay';
            document.body.appendChild(overlay);
        }
        
        const updateOverlay = () => {
            if (!this.enabled) return;
            
            const wizards = document.querySelectorAll('.fi-fo-wizard');
            const forms = document.querySelectorAll('form');
            const inputs = document.querySelectorAll('input, select, textarea');
            const blockedElements = Array.from(document.querySelectorAll('*')).filter(el => {
                const computed = window.getComputedStyle(el);
                return computed.pointerEvents === 'none' && !el.classList.contains('wizard-connection-line');
            });
            
            overlay.innerHTML = `
                <h4>🔍 Wizard Debug Info</h4>
                <p>Wizards: ${wizards.length}</p>
                <p>Forms: ${forms.length}</p>
                <p>Inputs: ${inputs.length}</p>
                <p>Blocked Elements: ${blockedElements.length}</p>
                <p>Livewire: ${typeof Livewire !== 'undefined' ? '✅' : '❌'}</p>
                <button onclick="WizardDebugger.testInteraction()">Test Interaction</button>
                <button onclick="WizardDebugger.disable()">Close</button>
            `;
            
            requestAnimationFrame(updateOverlay);
        };
        
        updateOverlay();
    },
    
    hideDebugOverlay() {
        const overlay = document.getElementById('wizard-debug-overlay');
        if (overlay) {
            overlay.remove();
        }
    },
    
    testInteraction() {
        console.group('🧪 Testing Wizard Interaction');
        
        // Find first input in wizard
        const firstInput = document.querySelector('.fi-fo-wizard input[type="text"]');
        if (firstInput) {
            console.log('Found input:', firstInput);
            console.log('Can focus:', document.activeElement !== firstInput);
            firstInput.focus();
            console.log('Focused:', document.activeElement === firstInput);
            
            // Try to change value
            const oldValue = firstInput.value;
            firstInput.value = 'Test ' + Date.now();
            firstInput.dispatchEvent(new Event('input', { bubbles: true }));
            firstInput.dispatchEvent(new Event('change', { bubbles: true }));
            console.log('Value changed from:', oldValue, 'to:', firstInput.value);
        } else {
            console.log('No text input found in wizard');
        }
        
        // Check for blocked elements
        const blocked = [];
        document.querySelectorAll('.fi-fo-wizard *').forEach(el => {
            const computed = window.getComputedStyle(el);
            if (computed.pointerEvents === 'none' && !el.classList.contains('wizard-connection-line')) {
                blocked.push({
                    element: el,
                    tag: el.tagName,
                    classes: el.className,
                    reason: 'pointer-events: none'
                });
            }
        });
        
        if (blocked.length > 0) {
            console.warn('Found blocked elements:', blocked);
        } else {
            console.log('✅ No blocked elements found');
        }
        
        console.groupEnd();
    },
    
    inspectElement(selector) {
        const element = document.querySelector(selector);
        if (!element) {
            console.error('Element not found:', selector);
            return;
        }
        
        const computed = window.getComputedStyle(element);
        const rect = element.getBoundingClientRect();
        
        console.group(`🔍 Inspecting: ${selector}`);
        console.log('Element:', element);
        console.log('Position:', computed.position);
        console.log('Z-Index:', computed.zIndex);
        console.log('Pointer Events:', computed.pointerEvents);
        console.log('Display:', computed.display);
        console.log('Visibility:', computed.visibility);
        console.log('Opacity:', computed.opacity);
        console.log('Bounds:', rect);
        console.log('Wire Attributes:', {
            model: element.getAttribute('wire:model'),
            click: element.getAttribute('wire:click'),
            loading: element.getAttribute('wire:loading')
        });
        console.groupEnd();
    }
};

// Auto-enable on development
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    console.log('🔧 Wizard Debugger available. Use WizardDebugger.enable() to start debugging.');
}

// Export for console access
window.WizardDebugger = WizardDebugger;