// Column Toggle Enhancements for Filament Tables
document.addEventListener('DOMContentLoaded', function() {
    // Function to group column toggles
    function groupColumnToggles() {
        const columnToggleDropdowns = document.querySelectorAll('.fi-ta-col-toggle');
        
        columnToggleDropdowns.forEach(dropdown => {
            // Wait for dropdown to be opened
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        const panel = dropdown.querySelector('.fi-dropdown-panel');
                        if (panel && !panel.dataset.grouped) {
                            organizeColumns(panel);
                            panel.dataset.grouped = 'true';
                        }
                    }
                });
            });
            
            observer.observe(dropdown, { childList: true, subtree: true });
        });
    }
    
    function organizeColumns(panel) {
        const form = panel.querySelector('form');
        if (!form) return;
        
        const checkboxes = form.querySelectorAll('.fi-fo-field-wrp');
        if (checkboxes.length === 0) return;
        
        // Define column groups
        const columnGroups = {
            'Basis': ['name', 'email', 'phone', 'id'],
            'Organisation': ['company', 'branch', 'company.name', 'branch.name'],
            'Termine': ['appointments_count', 'last_appointment', 'upcoming_appointments'],
            'Status': ['tags', 'status', 'notes_count'],
            'Zeiten': ['created_at', 'updated_at', 'last_contact_at'],
            'Weitere': [] // For ungrouped columns
        };
        
        // Also check for German field labels for better matching
        const labelToGroup = {
            'Name': 'Basis',
            'E-Mail': 'Basis',
            'Telefon': 'Basis',
            'ID': 'Basis',
            'Unternehmen': 'Organisation',
            'Filiale': 'Organisation',
            'Termine': 'Termine',
            'Letzter Termin': 'Termine',
            'Tags': 'Status',
            'Erstellt am': 'Zeiten',
            'Aktualisiert am': 'Zeiten'
        };
        
        // Create grouped structure
        const groupedElements = {};
        Object.keys(columnGroups).forEach(group => {
            groupedElements[group] = [];
        });
        
        // Sort checkboxes into groups
        checkboxes.forEach(checkbox => {
            const label = checkbox.querySelector('label');
            const input = checkbox.querySelector('input');
            if (!label || !input) return;
            
            const fieldName = input.name.match(/toggledTableColumns\.(.*?)\./)?.[1] || '';
            const labelText = label.textContent.trim();
            
            let placed = false;
            
            // First check by field name
            for (const [group, fields] of Object.entries(columnGroups)) {
                if (fields.includes(fieldName)) {
                    groupedElements[group].push(checkbox);
                    placed = true;
                    break;
                }
            }
            
            // If not placed, check by label text
            if (!placed && labelToGroup[labelText]) {
                groupedElements[labelToGroup[labelText]].push(checkbox);
                placed = true;
            }
            
            // If still not placed, add to "Weitere"
            if (!placed) {
                groupedElements['Weitere'].push(checkbox);
            }
        });
        
        // Clear form and rebuild with groups
        const formContent = form.querySelector('.grid');
        if (!formContent) return;
        
        formContent.innerHTML = '';
        
        Object.entries(groupedElements).forEach(([group, elements], index) => {
            if (elements.length === 0) return;
            
            const groupDiv = document.createElement('div');
            groupDiv.className = 'column-toggle-group';
            
            const groupLabel = document.createElement('div');
            groupLabel.className = 'column-toggle-group-label';
            groupLabel.textContent = group;
            groupDiv.appendChild(groupLabel);
            
            const groupGrid = document.createElement('div');
            groupGrid.className = 'grid gap-y-2';
            
            elements.forEach(element => {
                groupGrid.appendChild(element);
            });
            
            groupDiv.appendChild(groupGrid);
            formContent.appendChild(groupDiv);
        });
    }
    
    // Initialize on page load
    groupColumnToggles();
    
    // Re-initialize on Livewire navigation
    document.addEventListener('livewire:navigated', function() {
        groupColumnToggles();
    });
    
    // Tab descriptions functionality
    function initializeTabDescriptions() {
        const tabContainers = document.querySelectorAll('.fi-tabs-component');
        
        tabContainers.forEach(container => {
            const tabs = container.querySelectorAll('[role="tab"]');
            const widget = container.closest('.fi-page')?.querySelector('.tab-descriptions-widget');
            
            if (!widget) return;
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('aria-controls')?.replace('tab-', '');
                    updateActiveTabDescription(widget, tabId);
                });
            });
            
            // Set initial active tab
            const activeTab = container.querySelector('[role="tab"][aria-selected="true"]');
            if (activeTab) {
                const tabId = activeTab.getAttribute('aria-controls')?.replace('tab-', '');
                updateActiveTabDescription(widget, tabId);
            }
        });
    }
    
    function updateActiveTabDescription(widget, activeTabId) {
        const descriptions = widget.querySelectorAll('.tab-description');
        
        descriptions.forEach(desc => {
            const isActive = desc.dataset.tab === activeTabId;
            if (isActive) {
                desc.classList.add('active');
            } else {
                desc.classList.remove('active');
            }
        });
    }
    
    // Initialize tab descriptions
    initializeTabDescriptions();
    
    document.addEventListener('livewire:navigated', function() {
        initializeTabDescriptions();
    });
});