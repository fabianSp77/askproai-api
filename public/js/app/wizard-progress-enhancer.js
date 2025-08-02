/**
 * Wizard Progress Enhancer
 * Fixes missing connection lines in Filament wizard
 */

document.addEventListener('DOMContentLoaded', function() {
    fixWizardProgress();
    
    // Re-apply on Livewire navigation
    document.addEventListener('livewire:navigated', function() {
        setTimeout(fixWizardProgress, 100);
    });
    
    // Re-apply on wizard step change
    window.addEventListener('wizard-step-changed', function() {
        setTimeout(fixWizardProgress, 100);
    });
});

function fixWizardProgress() {
    const wizards = document.querySelectorAll('.fi-fo-wizard');
    
    wizards.forEach(wizard => {
        const header = wizard.querySelector('.fi-fo-wizard-header');
        if (!header) return;
        
        const nav = header.querySelector('nav');
        if (!nav) return;
        
        const ol = nav.querySelector('ol');
        if (!ol) return;
        
        // Add CSS classes instead of inline styles to avoid conflicts
        ol.classList.add('wizard-steps-enhanced');
        
        // Get all step items
        const steps = ol.querySelectorAll('li');
        const totalSteps = steps.length;
        
        // Find active step
        let activeStepIndex = 0;
        steps.forEach((step, index) => {
            // Add classes instead of inline styles
            step.classList.add('wizard-step-enhanced');
            
            if (step.classList.contains('fi-active') || 
                step.classList.contains('fi-completed') ||
                step.querySelector('[aria-current="step"]')) {
                activeStepIndex = index;
            }
            
            // Remove any existing connection lines
            const existingLine = step.querySelector('.wizard-connection-line');
            if (existingLine) {
                existingLine.remove();
            }
            
            // Add connection line to all steps except the last
            if (index < totalSteps - 1) {
                const connectionLine = document.createElement('div');
                connectionLine.className = 'wizard-connection-line';
                connectionLine.setAttribute('data-state', index < activeStepIndex ? 'completed' : 'pending');
                
                step.appendChild(connectionLine);
            }
            
            // Ensure step button/link is above the line
            const button = step.querySelector('button, a');
            if (button) {
                button.classList.add('wizard-step-button');
            }
        });
        
        // Add progress bar background
        if (!nav.querySelector('.wizard-progress-background')) {
            const progressBg = document.createElement('div');
            progressBg.className = 'wizard-progress-background';
            nav.insertBefore(progressBg, nav.firstChild);
        }
        
        // Add active progress bar
        let progressBar = nav.querySelector('.wizard-progress-active');
        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.className = 'wizard-progress-active';
            nav.insertBefore(progressBar, nav.firstChild.nextSibling);
        }
        
        // Update progress width using data attribute
        const progressPercentage = (activeStepIndex / (totalSteps - 1)) * 90;
        progressBar.setAttribute('data-progress', progressPercentage);
    });
}

// Export for manual trigger if needed
window.fixWizardProgress = fixWizardProgress;