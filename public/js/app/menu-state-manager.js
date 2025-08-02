// Menu State Manager - Verbessertes State-Management für Sidebar-Navigation

(function() {
    'use strict';

    // Konstanten
    const STORAGE_KEY = 'filament.sidebar.state';
    const ANIMATION_DURATION = 300;

    // State Manager Klasse
    class MenuStateManager {
        constructor() {
            this.state = this.loadState();
            this.init();
        }

        // Lade gespeicherten State aus localStorage
        loadState() {
            try {
                const savedState = localStorage.getItem(STORAGE_KEY);
                return savedState ? JSON.parse(savedState) : {
                    collapsedGroups: [],
                    lastActiveGroup: null,
                    sidebarOpen: true
                };
            } catch (e) {
                console.error('Error loading menu state:', e);
                return {
                    collapsedGroups: [],
                    lastActiveGroup: null,
                    sidebarOpen: true
                };
            }
        }

        // Speichere State in localStorage
        saveState() {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this.state));
            } catch (e) {
                console.error('Error saving menu state:', e);
            }
        }

        // Initialisierung
        init() {
            // Warte auf Alpine.js
            document.addEventListener('alpine:init', () => {
                this.initializeAlpineStore();
            });

            // Falls Alpine bereits geladen ist
            if (window.Alpine) {
                this.initializeAlpineStore();
            }

            // Event Listener für Menü-Interaktionen
            this.attachEventListeners();
        }

        // Alpine Store erweitern
        initializeAlpineStore() {
            const self = this;

            // Erweitere den bestehenden Sidebar Store
            if (window.Alpine && window.Alpine.store('sidebar')) {
                const originalStore = Alpine.store('sidebar');
                
                // Override der Toggle-Funktion
                const originalToggleCollapsed = originalStore.toggleCollapsedGroup;
                originalStore.toggleCollapsedGroup = function(group) {
                    // Originale Funktion aufrufen
                    if (originalToggleCollapsed) {
                        originalToggleCollapsed.call(this, group);
                    }
                    
                    // State aktualisieren
                    self.toggleGroup(group);
                };

                // Initialer State anwenden
                self.applyInitialState();
            }
        }

        // Event Listener hinzufügen
        attachEventListeners() {
            // Delegierte Event-Handler für bessere Performance
            document.addEventListener('click', (e) => {
                // Klick auf Gruppen-Button
                const groupButton = e.target.closest('.fi-sidebar-group-button');
                if (groupButton && groupButton.classList.contains('cursor-pointer')) {
                    const group = groupButton.closest('.fi-sidebar-group');
                    const label = group?.dataset.groupLabel;
                    if (label) {
                        this.handleGroupClick(label, e);
                    }
                }

                // Klick auf Collapse-Button
                const collapseButton = e.target.closest('.fi-sidebar-group-collapse-button');
                if (collapseButton) {
                    e.stopPropagation();
                    const group = collapseButton.closest('.fi-sidebar-group');
                    const label = group?.dataset.groupLabel;
                    if (label) {
                        this.toggleGroup(label);
                    }
                }
            });

            // Touch-Events für Mobile
            if ('ontouchstart' in window) {
                this.addTouchSupport();
            }

            // Keyboard Navigation
            this.addKeyboardSupport();
        }

        // Handle Gruppen-Klick
        handleGroupClick(label, event) {
            // Verhindere Default nur wenn collapsible
            const button = event.currentTarget;
            if (button && button.classList.contains('cursor-pointer')) {
                event.preventDefault();
                this.toggleGroup(label);
            }
        }

        // Toggle Gruppen-Status
        toggleGroup(label) {
            const index = this.state.collapsedGroups.indexOf(label);
            
            if (index > -1) {
                // Gruppe öffnen
                this.state.collapsedGroups.splice(index, 1);
                this.animateGroupOpen(label);
            } else {
                // Gruppe schließen
                this.state.collapsedGroups.push(label);
                this.animateGroupClose(label);
            }
            
            this.state.lastActiveGroup = label;
            this.saveState();
            
            // Alpine Store aktualisieren
            if (window.Alpine && window.Alpine.store('sidebar')) {
                // Trigger Alpine reactivity
                window.Alpine.store('sidebar').$el?.dispatchEvent(
                    new CustomEvent('sidebar-state-changed', { detail: { label } })
                );
            }
        }

        // Animations
        animateGroupOpen(label) {
            const group = document.querySelector(`[data-group-label="${label}"]`);
            if (!group) return;

            const items = group.querySelector('.fi-sidebar-group-items');
            const button = group.querySelector('.fi-sidebar-group-collapse-button');
            
            if (items) {
                items.style.height = 'auto';
                const height = items.offsetHeight;
                items.style.height = '0';
                
                requestAnimationFrame(() => {
                    items.style.transition = `height ${ANIMATION_DURATION}ms ease`;
                    items.style.height = height + 'px';
                    
                    setTimeout(() => {
                        items.style.height = 'auto';
                        items.style.transition = '';
                    }, ANIMATION_DURATION);
                });
            }

            if (button) {
                button.classList.remove('-rotate-180');
            }
        }

        animateGroupClose(label) {
            const group = document.querySelector(`[data-group-label="${label}"]`);
            if (!group) return;

            const items = group.querySelector('.fi-sidebar-group-items');
            const button = group.querySelector('.fi-sidebar-group-collapse-button');
            
            if (items) {
                const height = items.offsetHeight;
                items.style.height = height + 'px';
                
                requestAnimationFrame(() => {
                    items.style.transition = `height ${ANIMATION_DURATION}ms ease`;
                    items.style.height = '0';
                });
            }

            if (button) {
                button.classList.add('-rotate-180');
            }
        }

        // Touch Support
        addTouchSupport() {
            let touchStartY = 0;
            let touchEndY = 0;

            document.addEventListener('touchstart', (e) => {
                touchStartY = e.changedTouches[0].screenY;
            }, { passive: true });

            document.addEventListener('touchend', (e) => {
                touchEndY = e.changedTouches[0].screenY;
                
                // Swipe detection für Sidebar toggle
                const swipeDistance = touchEndY - touchStartY;
                if (Math.abs(swipeDistance) > 50) {
                    // Swipe logic hier wenn gewünscht
                }
            }, { passive: true });
        }

        // Keyboard Support
        addKeyboardSupport() {
            document.addEventListener('keydown', (e) => {
                const activeElement = document.activeElement;
                
                // Prüfe ob wir in der Sidebar sind
                if (!activeElement?.closest('.fi-sidebar')) return;

                switch(e.key) {
                    case 'Enter':
                    case ' ':
                        if (activeElement.classList.contains('fi-sidebar-group-button')) {
                            e.preventDefault();
                            activeElement.click();
                        }
                        break;
                        
                    case 'ArrowUp':
                    case 'ArrowDown':
                        e.preventDefault();
                        this.navigateMenu(e.key === 'ArrowUp' ? -1 : 1);
                        break;
                        
                    case 'ArrowLeft':
                        // Gruppe schließen
                        this.collapseCurrentGroup();
                        break;
                        
                    case 'ArrowRight':
                        // Gruppe öffnen
                        this.expandCurrentGroup();
                        break;
                }
            });
        }

        // Navigation Helper
        navigateMenu(direction) {
            const focusableElements = Array.from(
                document.querySelectorAll('.fi-sidebar-group-button, .fi-sidebar-item-button')
            );
            
            const currentIndex = focusableElements.indexOf(document.activeElement);
            const nextIndex = Math.max(0, Math.min(focusableElements.length - 1, currentIndex + direction));
            
            focusableElements[nextIndex]?.focus();
        }

        // Collapse/Expand Helpers
        collapseCurrentGroup() {
            const group = document.activeElement?.closest('.fi-sidebar-group');
            const label = group?.dataset.groupLabel;
            
            if (label && !this.state.collapsedGroups.includes(label)) {
                this.toggleGroup(label);
            }
        }

        expandCurrentGroup() {
            const group = document.activeElement?.closest('.fi-sidebar-group');
            const label = group?.dataset.groupLabel;
            
            if (label && this.state.collapsedGroups.includes(label)) {
                this.toggleGroup(label);
            }
        }

        // Initial State anwenden
        applyInitialState() {
            // Wende gespeicherten State auf alle Gruppen an
            this.state.collapsedGroups.forEach(label => {
                const group = document.querySelector(`[data-group-label="${label}"]`);
                if (group) {
                    const items = group.querySelector('.fi-sidebar-group-items');
                    const button = group.querySelector('.fi-sidebar-group-collapse-button');
                    
                    if (items) {
                        items.style.display = 'none';
                        items.style.height = '0';
                    }
                    
                    if (button) {
                        button.classList.add('-rotate-180');
                    }
                }
            });

            // Stelle sicher, dass die aktive Gruppe sichtbar ist
            this.ensureActiveGroupVisible();
        }

        // Stelle sicher, dass aktive Menüpunkte sichtbar sind
        ensureActiveGroupVisible() {
            const activeItem = document.querySelector('.fi-sidebar-item.fi-active');
            if (activeItem) {
                const group = activeItem.closest('.fi-sidebar-group');
                const label = group?.dataset.groupLabel;
                
                if (label && this.state.collapsedGroups.includes(label)) {
                    // Öffne die Gruppe wenn sie ein aktives Item enthält
                    this.toggleGroup(label);
                }
            }
        }
    }

    // Initialisiere den Manager
    window.menuStateManager = new MenuStateManager();

    // Exportiere für Alpine/Livewire Integration
    window.FilamentMenuState = {
        toggle: (label) => window.menuStateManager.toggleGroup(label),
        isCollapsed: (label) => window.menuStateManager.state.collapsedGroups.includes(label),
        getState: () => window.menuStateManager.state,
        reset: () => {
            window.menuStateManager.state = {
                collapsedGroups: [],
                lastActiveGroup: null,
                sidebarOpen: true
            };
            window.menuStateManager.saveState();
            window.location.reload();
        }
    };

})();