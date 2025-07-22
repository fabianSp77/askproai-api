/**
 * Wizard Form Handler - Spezifische Lösung für QuickSetupWizardV2
 */
(function() {
    'use strict';
    
    console.log('🎯 Wizard Form Handler Active');
    
    let setupModeValue = null;
    let companySelectInterval = null;
    
    // Funktion um das Company Select zu zeigen
    function showCompanySelect() {
        console.log('Attempting to show company select...');
        
        // Suche alle Elemente die "Firma auswählen" enthalten
        const allElements = document.querySelectorAll('*');
        let foundSelect = false;
        
        allElements.forEach(el => {
            if (el.textContent && el.textContent.includes('Firma auswählen') && 
                !el.querySelector('*') && el.tagName !== 'SCRIPT') {
                // Das ist wahrscheinlich das Label
                console.log('Found label:', el);
                
                // Finde das nächste Select-Element
                let parent = el.parentElement;
                while (parent && !foundSelect) {
                    const select = parent.querySelector('select');
                    if (select) {
                        console.log('Found select element:', select);
                        
                        // Mache alle Parent-Elemente sichtbar
                        let current = select;
                        while (current) {
                            if (current.style) {
                                current.style.display = '';
                                current.style.visibility = 'visible';
                                current.style.opacity = '1';
                            }
                            if (current.classList) {
                                current.classList.remove('hidden');
                                current.classList.remove('invisible');
                                current.classList.remove('opacity-0');
                            }
                            // Entferne Alpine.js Bedingungen
                            if (current.hasAttribute('x-show')) {
                                current.removeAttribute('x-show');
                            }
                            if (current.hasAttribute('x-cloak')) {
                                current.removeAttribute('x-cloak');
                            }
                            current = current.parentElement;
                        }
                        
                        foundSelect = true;
                        
                        // Highlight das Feld
                        const wrapper = select.closest('.filament-forms-field-wrapper');
                        if (wrapper) {
                            wrapper.style.border = '2px solid #3B82F6';
                            wrapper.style.padding = '10px';
                            wrapper.style.borderRadius = '8px';
                            wrapper.style.backgroundColor = '#EFF6FF';
                            
                            setTimeout(() => {
                                wrapper.style.transition = 'all 0.3s ease';
                                wrapper.style.border = '';
                                wrapper.style.padding = '';
                                wrapper.style.backgroundColor = '';
                            }, 2000);
                        }
                    }
                    parent = parent.parentElement;
                }
            }
        });
        
        if (!foundSelect) {
            console.log('Company select not found, will retry...');
        } else {
            console.log('✅ Company select made visible!');
            if (companySelectInterval) {
                clearInterval(companySelectInterval);
                companySelectInterval = null;
            }
        }
        
        return foundSelect;
    }
    
    // Überwache Radio-Button-Änderungen
    document.addEventListener('change', function(e) {
        if (e.target.type === 'radio' && e.target.name && e.target.name.includes('setup_mode')) {
            console.log('Setup mode changed to:', e.target.value);
            setupModeValue = e.target.value;
            
            if (e.target.value === 'edit') {
                // Starte Intervall um Company Select zu suchen
                if (companySelectInterval) {
                    clearInterval(companySelectInterval);
                }
                
                let attempts = 0;
                companySelectInterval = setInterval(() => {
                    attempts++;
                    console.log(`Attempt ${attempts} to show company select...`);
                    
                    if (showCompanySelect() || attempts > 20) {
                        clearInterval(companySelectInterval);
                        companySelectInterval = null;
                        
                        if (attempts > 20) {
                            console.error('Failed to show company select after 20 attempts');
                            // Letzter Versuch: Zeige ALLE versteckten Elemente
                            forceShowAllHidden();
                        }
                    }
                }, 200);
            }
        }
    });
    
    // Klick-Handler für Toggle Buttons
    document.addEventListener('click', function(e) {
        const label = e.target.closest('label');
        if (label) {
            const radio = label.querySelector('input[type="radio"]');
            if (radio && radio.name && radio.name.includes('setup_mode')) {
                console.log('Toggle button clicked:', radio.value);
                
                // Manuell das change event triggern
                setTimeout(() => {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                }, 50);
            }
        }
    });
    
    // Force zeige alle versteckten Elemente
    function forceShowAllHidden() {
        console.log('🔴 FORCING ALL HIDDEN ELEMENTS TO SHOW');
        
        // Entferne alle display:none
        document.querySelectorAll('[style*="display: none"], [style*="display:none"]').forEach(el => {
            el.style.display = '';
        });
        
        // Entferne hidden Klassen
        document.querySelectorAll('.hidden, .invisible, .opacity-0').forEach(el => {
            el.classList.remove('hidden', 'invisible', 'opacity-0');
        });
        
        // Entferne x-show Attribute
        document.querySelectorAll('[x-show]').forEach(el => {
            el.removeAttribute('x-show');
        });
        
        // Spezifisch für Filament Forms
        document.querySelectorAll('.filament-forms-field-wrapper').forEach(wrapper => {
            wrapper.style.display = '';
            wrapper.style.visibility = 'visible';
            
            // Prüfe ob es ein Select mit company im Namen hat
            const select = wrapper.querySelector('select');
            if (select && (select.name.includes('company') || select.name.includes('selected_company'))) {
                console.log('Found hidden company select, making it visible:', select);
                wrapper.style.border = '3px solid red';
            }
        });
    }
    
    // Globale Funktionen
    window.forceShowCompanySelect = function() {
        console.log('🚀 Manually forcing company select');
        showCompanySelect();
        
        // Wenn nicht gefunden, zeige alle versteckten Elemente
        setTimeout(() => {
            if (!document.querySelector('select[name*="company"]:not([style*="display: none"])')) {
                forceShowAllHidden();
            }
        }, 500);
    };
    
    window.debugFormState = function() {
        console.log('=== FORM DEBUG STATE ===');
        
        // Alle Radio Buttons
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            console.log(`Radio: ${radio.name} = ${radio.value}, checked: ${radio.checked}`);
        });
        
        // Alle Selects
        document.querySelectorAll('select').forEach(select => {
            const visible = getComputedStyle(select).display !== 'none';
            console.log(`Select: ${select.name}, visible: ${visible}`);
        });
        
        // Alle versteckten Elemente
        const hidden = document.querySelectorAll('[style*="display: none"], .hidden');
        console.log(`Hidden elements: ${hidden.length}`);
        hidden.forEach(el => {
            if (el.querySelector('select')) {
                console.log('Hidden element with select:', el);
            }
        });
    };
    
    console.log('💡 Commands:');
    console.log('   window.forceShowCompanySelect() - Force show company select');
    console.log('   window.debugFormState() - Debug form state');
    
    // Initial check nach 2 Sekunden
    setTimeout(() => {
        console.log('Initial form check...');
        debugFormState();
    }, 2000);
})();