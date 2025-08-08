// Column Selector Improvements
document.addEventListener('DOMContentLoaded', function() {
    // Warte auf Filament und Alpine.js
    if (window.Alpine) {
        Alpine.data('columnSelector', () => ({
            init() {
                this.improveColumnSelector();
            },
            
            improveColumnSelector() {
                // Finde alle Column Toggle Dropdowns
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === 1 && node.classList?.contains('fi-ta-col-toggle')) {
                                this.enhanceDropdown(node);
                            }
                        });
                    });
                });
                
                observer.observe(document.body, { childList: true, subtree: true });
                
                // Enhance existing dropdowns
                document.querySelectorAll('.fi-ta-col-toggle').forEach(dropdown => {
                    this.enhanceDropdown(dropdown);
                });
            },
            
            enhanceDropdown(dropdown) {
                const panel = dropdown.querySelector('.fi-dropdown-panel');
                if (!panel) return;
                
                // Gruppiere die Spalten
                const checkboxes = panel.querySelectorAll('input[type="checkbox"]');
                const groups = {
                    'Basis': [],
                    'Kunde': [],
                    'Anruf': [],
                    'Analyse': [],
                    'Status': [],
                    'Weitere': []
                };
                
                checkboxes.forEach(checkbox => {
                    const label = checkbox.closest('label');
                    const text = label?.textContent?.trim() || '';
                    const name = checkbox.name;
                    
                    let group = 'Weitere';
                    
                    // Gruppierung basierend auf Feldname oder Label
                    if (name.includes('customer') || text.includes('Kunde')) {
                        group = 'Kunde';
                    } else if (name.includes('start_timestamp') || name.includes('end_timestamp') || 
                              name.includes('duration') || text.includes('Anruf') || text.includes('Dauer')) {
                        group = 'Anruf';
                    } else if (name.includes('sentiment') || name.includes('urgency') || 
                              name.includes('analysis') || text.includes('Stimmung') || text.includes('Dringlichkeit')) {
                        group = 'Analyse';
                    } else if (name.includes('status') || name.includes('appointment') || 
                              text.includes('Status') || text.includes('Termin')) {
                        group = 'Status';
                    } else if (name.includes('from_number') || name.includes('call_id') || 
                              text.includes('ID') || text.includes('Nummer')) {
                        group = 'Basis';
                    }
                    
                    groups[group].push(label.parentElement);
                });
                
                // Reorganisiere das DOM
                const container = panel.querySelector('.grid') || panel.querySelector('.space-y-4');
                if (container) {
                    container.innerHTML = '';
                    
                    Object.entries(groups).forEach(([groupName, items]) => {
                        if (items.length === 0) return;
                        
                        const groupDiv = document.createElement('div');
                        groupDiv.className = 'column-group mb-4 pb-4 border-b border-gray-200 dark:border-gray-700 last:border-0';
                        
                        const groupTitle = document.createElement('h4');
                        groupTitle.className = 'text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wider';
                        groupTitle.textContent = groupName;
                        
                        const itemsContainer = document.createElement('div');
                        itemsContainer.className = 'grid grid-cols-1 gap-2';
                        
                        items.forEach(item => {
                            itemsContainer.appendChild(item);
                        });
                        
                        groupDiv.appendChild(groupTitle);
                        groupDiv.appendChild(itemsContainer);
                        container.appendChild(groupDiv);
                    });
                }
            }
        }));
    }
});