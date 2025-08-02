// Modern Column Editor Enhancement
document.addEventListener('DOMContentLoaded', function() {
    
    // Warte auf Filament
    const initColumnEditor = () => {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.querySelector?.('.fi-ta-col-toggle .fi-dropdown-panel')) {
                        enhanceColumnEditor(node.querySelector('.fi-dropdown-panel'));
                    }
                });
            });
        });
        
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Enhance existing
        const existing = document.querySelector('.fi-ta-col-toggle .fi-dropdown-panel');
        if (existing) {
            enhanceColumnEditor(existing);
        }
    };
    
    function enhanceColumnEditor(panel) {
        if (panel.dataset.enhanced) return;
        panel.dataset.enhanced = 'true';
        
        // Hole alle Checkboxen
        const checkboxes = panel.querySelectorAll('input[type="checkbox"]');
        if (!checkboxes.length) return;
        
        // Gruppiere Spalten nach Kategorien
        const groups = {
            'Basis Informationen': {
                icon: '<svg class="column-group-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                items: [],
                keywords: ['start_timestamp', 'from_number', 'call_id', 'duration']
            },
            'Kunde & Kontakt': {
                icon: '<svg class="column-group-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
                items: [],
                keywords: ['customer', 'kunde', 'phone', 'email', 'name']
            },
            'Analyse & Stimmung': {
                icon: '<svg class="column-group-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
                items: [],
                keywords: ['sentiment', 'urgency', 'analysis', 'stimmung', 'dringlichkeit']
            },
            'Status & Termine': {
                icon: '<svg class="column-group-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
                items: [],
                keywords: ['appointment', 'termin', 'status', 'tags']
            },
            'Kosten & Details': {
                icon: '<svg class="column-group-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                items: [],
                keywords: ['cost', 'kosten', 'recording', 'aufnahme', 'details']
            },
            'Weitere': {
                icon: '<svg class="column-group-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>',
                items: [],
                keywords: []
            }
        };
        
        // Zuordnung der Checkboxen zu Gruppen
        checkboxes.forEach(checkbox => {
            const label = checkbox.closest('label');
            if (!label) return;
            
            const text = label.textContent.trim();
            const name = checkbox.name || '';
            let assigned = false;
            
            // Prüfe Keywords für Gruppenzuordnung
            for (const [groupName, group] of Object.entries(groups)) {
                if (groupName === 'Weitere') continue;
                
                for (const keyword of group.keywords) {
                    if (name.toLowerCase().includes(keyword) || text.toLowerCase().includes(keyword)) {
                        group.items.push({ checkbox, label, text });
                        assigned = true;
                        break;
                    }
                }
                if (assigned) break;
            }
            
            // Fallback zu "Weitere"
            if (!assigned) {
                groups['Weitere'].items.push({ checkbox, label, text });
            }
        });
        
        // Erstelle neues Layout
        const newContent = document.createElement('div');
        newContent.className = 'column-editor-wrapper';
        
        // Header
        newContent.innerHTML = `
            <div class="column-editor-header">
                <h3 class="column-editor-title">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Spalten anpassen
                </h3>
                
                <div class="column-search-container">
                    <svg class="column-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" class="column-search-input" placeholder="Spalten suchen...">
                </div>
                
                <div class="column-quick-actions">
                    <button class="column-quick-action select-all">Alle auswählen</button>
                    <button class="column-quick-action deselect-all">Alle abwählen</button>
                    <button class="column-quick-action reset">Standard</button>
                </div>
            </div>
            
            <div class="column-editor-content">
                <!-- Groups will be inserted here -->
            </div>
            
            <div class="column-editor-footer">
                <div class="column-count">
                    <strong class="selected-count">0</strong> von <span class="total-count">0</span> Spalten ausgewählt
                </div>
                <button class="column-apply-btn">Übernehmen</button>
            </div>
        `;
        
        const contentArea = newContent.querySelector('.column-editor-content');
        
        // Erstelle Gruppen
        Object.entries(groups).forEach(([groupName, group]) => {
            if (group.items.length === 0) return;
            
            const groupEl = document.createElement('div');
            groupEl.className = 'column-group';
            groupEl.innerHTML = `
                <div class="column-group-header">
                    <div class="column-group-title">
                        ${group.icon}
                        ${groupName}
                    </div>
                    <a class="column-group-toggle" data-group="${groupName}">Alle auswählen</a>
                </div>
                <div class="column-items"></div>
            `;
            
            const itemsContainer = groupEl.querySelector('.column-items');
            
            group.items.forEach(({ checkbox, label, text }) => {
                const itemEl = document.createElement('div');
                itemEl.className = 'column-item' + (checkbox.checked ? ' selected' : '');
                
                // Beschreibung basierend auf Feldname
                let description = '';
                const name = checkbox.name || '';
                if (name.includes('start_timestamp')) description = 'Zeitpunkt des Anrufbeginns';
                else if (name.includes('from_number')) description = 'Telefonnummer des Anrufers';
                else if (name.includes('customer')) description = 'Zugeordneter Kunde';
                else if (name.includes('duration')) description = 'Länge des Anrufs';
                else if (name.includes('sentiment')) description = 'Emotionale Bewertung';
                else if (name.includes('urgency')) description = 'Wichtigkeit des Anrufs';
                else if (name.includes('cost')) description = 'Kosten des Anrufs';
                else if (name.includes('appointment')) description = 'Gebuchter Termin';
                else if (name.includes('tags')) description = 'Kategorisierung';
                
                itemEl.innerHTML = `
                    <input type="checkbox" ${checkbox.checked ? 'checked' : ''}>
                    <div class="column-item-label">${text}</div>
                    ${description ? `<div class="column-item-desc">${description}</div>` : ''}
                `;
                
                // Event Handlers
                const newCheckbox = itemEl.querySelector('input');
                itemEl.addEventListener('click', (e) => {
                    if (e.target === newCheckbox) return;
                    newCheckbox.checked = !newCheckbox.checked;
                    checkbox.checked = newCheckbox.checked;
                    itemEl.classList.toggle('selected', newCheckbox.checked);
                    updateCounts();
                });
                
                newCheckbox.addEventListener('change', () => {
                    checkbox.checked = newCheckbox.checked;
                    itemEl.classList.toggle('selected', newCheckbox.checked);
                    updateCounts();
                });
                
                itemsContainer.appendChild(itemEl);
            });
            
            contentArea.appendChild(groupEl);
        });
        
        // Replace original content
        panel.innerHTML = '';
        panel.appendChild(newContent);
        
        // Event Handlers
        const searchInput = newContent.querySelector('.column-search-input');
        const selectAllBtn = newContent.querySelector('.select-all');
        const deselectAllBtn = newContent.querySelector('.deselect-all');
        const resetBtn = newContent.querySelector('.reset');
        const applyBtn = newContent.querySelector('.column-apply-btn');
        
        // Search functionality
        searchInput.addEventListener('input', (e) => {
            const search = e.target.value.toLowerCase();
            newContent.querySelectorAll('.column-item').forEach(item => {
                const label = item.querySelector('.column-item-label').textContent.toLowerCase();
                const desc = item.querySelector('.column-item-desc')?.textContent.toLowerCase() || '';
                const matches = label.includes(search) || desc.includes(search);
                item.style.display = matches ? '' : 'none';
                item.classList.toggle('search-match', matches && search.length > 0);
            });
        });
        
        // Select/Deselect All
        selectAllBtn.addEventListener('click', () => {
            newContent.querySelectorAll('.column-item input').forEach(cb => {
                cb.checked = true;
                cb.dispatchEvent(new Event('change'));
            });
        });
        
        deselectAllBtn.addEventListener('click', () => {
            newContent.querySelectorAll('.column-item input').forEach(cb => {
                cb.checked = false;
                cb.dispatchEvent(new Event('change'));
            });
        });
        
        // Reset to defaults
        resetBtn.addEventListener('click', () => {
            const defaults = ['start_timestamp', 'from_number', 'customer', 'sentiment', 'duration', 'tags'];
            checkboxes.forEach(cb => {
                const shouldBeChecked = defaults.some(d => cb.name?.includes(d));
                cb.checked = shouldBeChecked;
            });
            
            // Update UI
            newContent.querySelectorAll('.column-item').forEach(item => {
                const checkbox = item.querySelector('input');
                const name = checkbox.name || '';
                const shouldBeChecked = defaults.some(d => name.includes(d));
                checkbox.checked = shouldBeChecked;
                item.classList.toggle('selected', shouldBeChecked);
            });
            
            updateCounts();
        });
        
        // Group toggles
        newContent.querySelectorAll('.column-group-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                const groupName = toggle.dataset.group;
                const group = toggle.closest('.column-group');
                const checkboxes = group.querySelectorAll('.column-item input');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                
                checkboxes.forEach(cb => {
                    cb.checked = !allChecked;
                    cb.dispatchEvent(new Event('change'));
                });
                
                toggle.textContent = allChecked ? 'Alle auswählen' : 'Alle abwählen';
            });
        });
        
        // Apply button
        applyBtn.addEventListener('click', () => {
            // Trigger Filament's submit
            const form = panel.closest('form');
            if (form) {
                // Find the original submit button
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.click();
                }
            }
            
            // Close dropdown
            const dropdown = panel.closest('[x-data]');
            if (dropdown && window.Alpine) {
                Alpine.$data(dropdown).columnsOpen = false;
            }
        });
        
        // Update counts
        function updateCounts() {
            const total = newContent.querySelectorAll('.column-item input').length;
            const selected = newContent.querySelectorAll('.column-item input:checked').length;
            newContent.querySelector('.selected-count').textContent = selected;
            newContent.querySelector('.total-count').textContent = total;
            
            // Update group toggles
            newContent.querySelectorAll('.column-group').forEach(group => {
                const toggle = group.querySelector('.column-group-toggle');
                const checkboxes = group.querySelectorAll('.column-item input');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                toggle.textContent = allChecked ? 'Alle abwählen' : 'Alle auswählen';
            });
        }
        
        updateCounts();
    }
    
    // Initialize
    initColumnEditor();
});