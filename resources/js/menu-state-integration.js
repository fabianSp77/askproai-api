// Menu State Integration - Bridge zwischen Alpine Store und Menu State Manager

(function() {
    'use strict';

    // Warte auf Alpine
    document.addEventListener('alpine:init', () => {
        // Überschreibe den collapsedGroups Store
        Alpine.store('sidebar').toggleCollapsedGroup = function(group) {
            // Lade aktuelle collapsed groups
            let collapsedGroups = JSON.parse(localStorage.getItem('collapsedGroups') || '[]');
            
            const index = collapsedGroups.indexOf(group);
            
            if (index > -1) {
                // Gruppe ist kollabiert, öffne sie
                collapsedGroups.splice(index, 1);
            } else {
                // Gruppe ist offen, kollabiere sie
                collapsedGroups.push(group);
            }
            
            // Speichere neuen State
            localStorage.setItem('collapsedGroups', JSON.stringify(collapsedGroups));
            
            // Trigger Alpine reactivity
            this.$dispatch('sidebar-group-toggled', { group, collapsed: index === -1 });
        };
        
        // Helper Funktion um zu prüfen ob Gruppe kollabiert ist
        Alpine.store('sidebar').groupIsCollapsed = function(group) {
            const collapsedGroups = JSON.parse(localStorage.getItem('collapsedGroups') || '[]');
            return collapsedGroups.includes(group);
        };
        
        // Erweitere den Store um eine Methode zum Resetten
        Alpine.store('sidebar').resetCollapsedGroups = function() {
            localStorage.setItem('collapsedGroups', '[]');
            this.$dispatch('sidebar-reset');
        };
        
        // Stelle sicher dass aktive Gruppen sichtbar sind
        Alpine.store('sidebar').ensureActiveGroupsVisible = function() {
            const activeItems = document.querySelectorAll('.fi-sidebar-item.fi-active');
            
            activeItems.forEach(item => {
                const group = item.closest('.fi-sidebar-group');
                if (group) {
                    const label = group.dataset.groupLabel;
                    if (label && this.groupIsCollapsed(label)) {
                        this.toggleCollapsedGroup(label);
                    }
                }
            });
        };
    });

    // Initialisiere nach DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMenuState);
    } else {
        initMenuState();
    }

    function initMenuState() {
        // Stelle sicher dass aktive Gruppen beim Laden sichtbar sind
        setTimeout(() => {
            if (window.Alpine && window.Alpine.store('sidebar')) {
                window.Alpine.store('sidebar').ensureActiveGroupsVisible();
            }
        }, 100);
        
        // Füge Event Listener für bessere Touch-Unterstützung hinzu
        addTouchSupport();
    }

    function addTouchSupport() {
        let touchStartX = 0;
        let touchStartY = 0;
        let touchStartTime = 0;
        
        document.addEventListener('touchstart', function(e) {
            const touch = e.touches[0];
            touchStartX = touch.clientX;
            touchStartY = touch.clientY;
            touchStartTime = Date.now();
        }, { passive: true });
        
        document.addEventListener('touchend', function(e) {
            const touch = e.changedTouches[0];
            const touchEndX = touch.clientX;
            const touchEndY = touch.clientY;
            const touchEndTime = Date.now();
            
            // Berechne Distanz und Zeit
            const distanceX = touchEndX - touchStartX;
            const distanceY = Math.abs(touchEndY - touchStartY);
            const duration = touchEndTime - touchStartTime;
            
            // Swipe-Erkennung für Sidebar
            if (duration < 300 && distanceY < 50) {
                if (distanceX > 50 && touchStartX < 20) {
                    // Swipe von links - öffne Sidebar
                    if (window.Alpine && window.Alpine.store('sidebar')) {
                        window.Alpine.store('sidebar').open();
                    }
                } else if (distanceX < -50) {
                    // Swipe nach links - schließe Sidebar
                    const sidebar = e.target.closest('.fi-sidebar');
                    if (sidebar && window.Alpine && window.Alpine.store('sidebar')) {
                        window.Alpine.store('sidebar').close();
                    }
                }
            }
        }, { passive: true });
    }

})();