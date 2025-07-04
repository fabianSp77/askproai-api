/**
 * Quick Setup Wizard V2 - Enhanced Functionality
 * Fixes form interactivity and adds visual improvements
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeWizardEnhancements();
});

// Re-initialize after Livewire updates
document.addEventListener('livewire:load', function() {
    Livewire.hook('message.processed', () => {
        initializeWizardEnhancements();
    });
});

function initializeWizardEnhancements() {
    const wizard = document.querySelector('.fi-fo-wizard');
    if (!wizard) return;

    // Fix form element z-index and pointer events
    fixFormInteractivity();
    
    // Enhance wizard progress visualization
    updateWizardProgress();
    
    // Add smooth scrolling to steps
    addSmoothScrolling();
    
    // Auto-save indicator
    addAutoSaveIndicator();
    
    // Add keyboard navigation
    addKeyboardNavigation();
    
    // Fix Alpine.js conflicts
    fixAlpineConflicts();
}

/**
 * Ensure all form elements are interactive
 */
function fixFormInteractivity() {
    // Remove any blocking overlays
    const blockers = document.querySelectorAll('.fi-fo-wizard [style*="pointer-events: none"]');
    blockers.forEach(el => {
        el.style.pointerEvents = 'auto';
    });
    
    // Ensure form inputs are clickable
    const formElements = document.querySelectorAll('.fi-fo-wizard input, .fi-fo-wizard select, .fi-fo-wizard textarea, .fi-fo-wizard button');
    formElements.forEach(el => {
        el.style.position = 'relative';
        el.style.zIndex = '20';
        el.style.pointerEvents = 'auto';
        
        // Add focus indication
        el.addEventListener('focus', function() {
            this.closest('.fi-fo-field')?.classList.add('focused');
        });
        
        el.addEventListener('blur', function() {
            this.closest('.fi-fo-field')?.classList.remove('focused');
        });
    });
}

/**
 * Update wizard progress bar based on current step
 */
function updateWizardProgress() {
    const wizard = document.querySelector('.fi-fo-wizard');
    const steps = wizard?.querySelectorAll('nav li');
    const currentStep = wizard?.querySelector('nav li[aria-current="step"]');
    
    if (!steps || !currentStep) return;
    
    const totalSteps = steps.length;
    const currentIndex = Array.from(steps).indexOf(currentStep);
    const progressPercentage = (currentIndex / (totalSteps - 1)) * 100;
    
    // Update CSS variable for progress line
    const nav = wizard.querySelector('nav ol');
    if (nav) {
        nav.style.setProperty('--wizard-progress', progressPercentage + '%');
    }
    
    // Mark completed steps
    steps.forEach((step, index) => {
        if (index < currentIndex) {
            step.classList.add('fi-completed');
            
            // Add checkmark icon for completed steps
            const icon = step.querySelector('svg');
            if (icon) {
                icon.innerHTML = '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />';
            }
        }
    });
}

/**
 * Add smooth scrolling when navigating between steps
 */
function addSmoothScrolling() {
    const stepButtons = document.querySelectorAll('.fi-fo-wizard nav button');
    
    stepButtons.forEach(button => {
        button.addEventListener('click', function() {
            setTimeout(() => {
                const wizardContent = document.querySelector('.fi-fo-wizard-step-content');
                if (wizardContent) {
                    wizardContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 100);
        });
    });
}

/**
 * Add auto-save indicator
 */
function addAutoSaveIndicator() {
    const wizard = document.querySelector('.fi-fo-wizard');
    if (!wizard) return;
    
    // Create indicator element
    const indicator = document.createElement('div');
    indicator.className = 'wizard-autosave-indicator';
    indicator.innerHTML = `
        <span class="saving" style="display: none;">
            <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Speichert...
        </span>
        <span class="saved" style="display: none;">
            <svg class="h-4 w-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
            Gespeichert
        </span>
    `;
    
    // Style the indicator
    indicator.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        padding: 10px 20px;
        border-radius: 6px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        font-size: 14px;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    document.body.appendChild(indicator);
    
    // Listen for Livewire events
    document.addEventListener('livewire:update', () => {
        showAutoSaveIndicator('saving');
    });
    
    document.addEventListener('livewire:updated', () => {
        showAutoSaveIndicator('saved');
    });
}

function showAutoSaveIndicator(status) {
    const indicator = document.querySelector('.wizard-autosave-indicator');
    if (!indicator) return;
    
    const savingSpan = indicator.querySelector('.saving');
    const savedSpan = indicator.querySelector('.saved');
    
    indicator.style.opacity = '1';
    
    if (status === 'saving') {
        savingSpan.style.display = 'flex';
        savedSpan.style.display = 'none';
    } else {
        savingSpan.style.display = 'none';
        savedSpan.style.display = 'flex';
        
        setTimeout(() => {
            indicator.style.opacity = '0';
        }, 2000);
    }
}

/**
 * Add keyboard navigation support
 */
function addKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        if (!document.querySelector('.fi-fo-wizard')) return;
        
        // Ctrl/Cmd + Arrow keys for navigation
        if ((e.ctrlKey || e.metaKey) && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
            e.preventDefault();
            
            const currentStep = document.querySelector('.fi-fo-wizard nav li[aria-current="step"]');
            const steps = Array.from(document.querySelectorAll('.fi-fo-wizard nav li'));
            const currentIndex = steps.indexOf(currentStep);
            
            if (e.key === 'ArrowLeft' && currentIndex > 0) {
                // Go to previous step
                const prevButton = steps[currentIndex - 1].querySelector('button');
                if (prevButton) prevButton.click();
            } else if (e.key === 'ArrowRight' && currentIndex < steps.length - 1) {
                // Go to next step (if available)
                const nextButton = document.querySelector('.fi-fo-wizard button[wire\\:click*="nextStep"]');
                if (nextButton) nextButton.click();
            }
        }
    });
}

/**
 * Fix Alpine.js conflicts with Livewire
 */
function fixAlpineConflicts() {
    // Ensure Alpine components are properly initialized
    const alpineComponents = document.querySelectorAll('[x-data]');
    
    alpineComponents.forEach(component => {
        // Re-initialize if needed
        if (component._x_dataStack === undefined) {
            Alpine.initTree(component);
        }
    });
}

/**
 * Show completion animation
 */
function showCompletionAnimation() {
    const wizardContent = document.querySelector('.fi-fo-wizard-step-content');
    if (!wizardContent) return;
    
    wizardContent.innerHTML = `
        <div class="wizard-complete">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
            <h2 class="text-2xl font-bold text-gray-900 mt-4">Setup erfolgreich abgeschlossen!</h2>
            <p class="text-gray-600 mt-2">Ihr System ist jetzt einsatzbereit.</p>
            <button class="mt-6 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                Zum Dashboard
            </button>
        </div>
    `;
    
    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #4ade80;
            stroke-miterlimit: 10;
            margin: 0 auto;
            box-shadow: inset 0px 0px 0px #4ade80;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }
        
        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #4ade80;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        
        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }
        
        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }
        
        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }
        
        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #4ade80;
            }
        }
    `;
    document.head.appendChild(style);
}

// Export for use in other scripts
window.WizardV2Enhancements = {
    initialize: initializeWizardEnhancements,
    showCompletion: showCompletionAnimation,
    showAutoSave: showAutoSaveIndicator
};