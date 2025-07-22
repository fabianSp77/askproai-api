/**
 * Admin Table Responsive Fix
 * Dynamische Anpassungen für Tabellen im Admin Panel
 */
(function() {
    'use strict';
    
    console.log('📊 Admin Table Responsive Fix Active');
    
    // Funktion um Tabellen responsive zu machen
    function makeTablesResponsive() {
        // Finde alle Tabellen-Container
        const tableContainers = document.querySelectorAll('.fi-ta-ctn, .fi-ta-content');
        
        tableContainers.forEach(container => {
            // Prüfe ob Container bereits bearbeitet wurde
            if (container.dataset.responsiveFixed) return;
            
            // Markiere als bearbeitet
            container.dataset.responsiveFixed = 'true';
            
            // Stelle sicher dass Container scrollbar ist
            container.style.overflowX = 'auto';
            container.style.maxWidth = '100%';
            
            // Finde die Tabelle innerhalb
            const table = container.querySelector('.fi-ta-table, table');
            if (table) {
                // Setze min-width für die Tabelle
                table.style.minWidth = 'max-content';
                
                // Log für Debugging
                console.log('Made table responsive:', {
                    containerWidth: container.offsetWidth,
                    tableWidth: table.offsetWidth,
                    needsScroll: table.offsetWidth > container.offsetWidth
                });
            }
        });
        
        // Fix für Checkboxen
        fixCheckboxes();
        
        // Fix für Actions-Spalte
        makeActionsStickyIfNeeded();
    }
    
    // Fix für abgeschnittene Checkboxen
    function fixCheckboxes() {
        const checkboxes = document.querySelectorAll('.fi-ta-record-checkbox, .fi-ta-header-checkbox');
        
        checkboxes.forEach(checkbox => {
            const wrapper = checkbox.closest('td, th');
            if (wrapper) {
                wrapper.style.minWidth = '40px';
                wrapper.style.paddingLeft = '8px';
            }
        });
    }
    
    // Mache Actions-Spalte sticky wenn Tabelle scrollt
    function makeActionsStickyIfNeeded() {
        const tables = document.querySelectorAll('.fi-ta-table');
        
        tables.forEach(table => {
            const container = table.closest('.fi-ta-content, .fi-ta-ctn');
            if (!container) return;
            
            // Prüfe ob Tabelle breiter als Container
            if (table.offsetWidth > container.offsetWidth) {
                // Finde alle Actions-Zellen
                const actionCells = table.querySelectorAll('.fi-ta-actions, [class*="actions"]');
                
                actionCells.forEach(cell => {
                    cell.style.position = 'sticky';
                    cell.style.right = '0';
                    cell.style.backgroundColor = getComputedStyle(cell).backgroundColor || '#fff';
                    cell.style.zIndex = '10';
                    
                    // Füge Schatten hinzu
                    cell.style.boxShadow = '-4px 0 8px -4px rgba(0,0,0,0.1)';
                });
                
                console.log('Made actions sticky for table');
            }
        });
    }
    
    // Funktion um die Seitenbreite anzupassen
    function adjustPageWidth() {
        const mainContent = document.querySelector('.fi-main');
        const sidebar = document.querySelector('.fi-sidebar');
        
        if (mainContent && sidebar) {
            // Berechne verfügbare Breite
            const sidebarWidth = sidebar.offsetWidth;
            const windowWidth = window.innerWidth;
            const availableWidth = windowWidth - sidebarWidth;
            
            // Setze maximale Breite für Hauptinhalt
            mainContent.style.maxWidth = `${availableWidth}px`;
            mainContent.style.width = '100%';
            
            console.log('Adjusted page width:', {
                windowWidth,
                sidebarWidth,
                availableWidth
            });
        }
    }
    
    // Initialisierung
    function init() {
        makeTablesResponsive();
        adjustPageWidth();
    }
    
    // Führe bei DOM ready aus
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Re-run nach Livewire Updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            setTimeout(init, 100);
        });
    }
    
    // Re-run bei Window Resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            adjustPageWidth();
            makeActionsStickyIfNeeded();
        }, 250);
    });
    
    // Monitor für neue Tabellen
    const observer = new MutationObserver((mutations) => {
        let hasNewTables = false;
        
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && 
                    (node.classList?.contains('fi-ta-ctn') || 
                     node.querySelector?.('.fi-ta-table'))) {
                    hasNewTables = true;
                }
            });
        });
        
        if (hasNewTables) {
            setTimeout(init, 100);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Globale Debug-Funktion
    window.debugTableLayout = function() {
        console.log('=== TABLE LAYOUT DEBUG ===');
        
        const tables = document.querySelectorAll('.fi-ta-table');
        tables.forEach((table, index) => {
            const container = table.closest('.fi-ta-content, .fi-ta-ctn');
            console.log(`Table ${index + 1}:`, {
                tableWidth: table.offsetWidth,
                containerWidth: container?.offsetWidth,
                needsScroll: table.offsetWidth > (container?.offsetWidth || 0),
                scrollLeft: container?.scrollLeft
            });
        });
        
        const mainContent = document.querySelector('.fi-main');
        console.log('Main content:', {
            width: mainContent?.offsetWidth,
            maxWidth: getComputedStyle(mainContent || document.body).maxWidth
        });
    };
    
    console.log('💡 Use window.debugTableLayout() to debug table layout issues');
})();