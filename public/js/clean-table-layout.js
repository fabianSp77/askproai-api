/**
 * Clean Table Layout Handler
 * Responsive Tabellen OHNE horizontales Scrollen
 */
(function() {
    'use strict';
    
    console.log('🎯 Clean Table Layout Active - NO horizontal scrolling');
    
    // Entferne alle overflow-x: auto Styles
    function removeHorizontalScroll() {
        const elementsWithScroll = document.querySelectorAll('[style*="overflow-x"]');
        elementsWithScroll.forEach(el => {
            el.style.removeProperty('overflow-x');
            el.style.removeProperty('-webkit-overflow-scrolling');
        });
        
        // Spezifisch für Tabellen-Container
        const tableContainers = document.querySelectorAll('.fi-ta-ctn, .fi-ta-content, .fi-ta-wrp');
        tableContainers.forEach(container => {
            container.style.overflowX = 'visible';
            container.style.maxWidth = '100%';
            container.style.width = '100%';
        });
    }
    
    // Optimiere Tabellen-Layout für verfügbaren Platz
    function optimizeTableLayout() {
        const tables = document.querySelectorAll('.fi-ta-table');
        
        tables.forEach(table => {
            // Reset table styles
            table.style.width = '100%';
            table.style.tableLayout = 'auto';
            table.style.minWidth = 'unset';
            
            // Optimiere Spaltenbreiten
            const headerCells = table.querySelectorAll('th');
            headerCells.forEach(th => {
                const columnClass = Array.from(th.classList).find(c => c.startsWith('fi-ta-col-'));
                
                if (columnClass) {
                    // Checkbox-Spalte
                    if (th.querySelector('.fi-ta-header-checkbox')) {
                        th.style.width = '40px';
                        th.style.minWidth = '40px';
                    }
                    // Actions-Spalte
                    else if (columnClass.includes('actions')) {
                        th.style.width = 'auto';
                        th.style.minWidth = '80px';
                    }
                    // Text-Spalten
                    else {
                        th.style.width = 'auto';
                        th.style.whiteSpace = 'nowrap';
                    }
                }
            });
            
            console.log('Optimized table layout');
        });
    }
    
    // Stelle sicher dass Checkboxen und Actions sichtbar sind
    function ensureVisibility() {
        // Checkboxen
        const checkboxCells = document.querySelectorAll('.fi-ta-record-checkbox, .fi-ta-header-checkbox');
        checkboxCells.forEach(cell => {
            const wrapper = cell.closest('td, th');
            if (wrapper) {
                wrapper.style.paddingLeft = '8px';
                wrapper.style.paddingRight = '8px';
            }
        });
        
        // Action Buttons
        const actionCells = document.querySelectorAll('.fi-ta-actions');
        actionCells.forEach(cell => {
            cell.style.paddingRight = '8px';
            
            // Stelle sicher dass Buttons sichtbar sind
            const buttons = cell.querySelectorAll('button, a');
            buttons.forEach(btn => {
                btn.style.visibility = 'visible';
                btn.style.opacity = '1';
            });
        });
    }
    
    // Responsive Text-Handling
    function handleResponsiveText() {
        const viewportWidth = window.innerWidth;
        const textCells = document.querySelectorAll('.fi-ta-text-item');
        
        textCells.forEach(cell => {
            // Auf kleinen Bildschirmen Text abschneiden
            if (viewportWidth < 1280) {
                cell.style.maxWidth = '200px';
                cell.style.overflow = 'hidden';
                cell.style.textOverflow = 'ellipsis';
                cell.style.whiteSpace = 'nowrap';
                
                // Tooltip für vollständigen Text
                if (!cell.title && cell.textContent) {
                    cell.title = cell.textContent.trim();
                }
            } else {
                // Auf großen Bildschirmen normaler Text
                cell.style.maxWidth = 'none';
                cell.style.overflow = 'visible';
                cell.style.whiteSpace = 'normal';
            }
        });
    }
    
    // Hauptfunktion
    function applyCleanLayout() {
        removeHorizontalScroll();
        optimizeTableLayout();
        ensureVisibility();
        handleResponsiveText();
        
        // Debug Info
        const mainContent = document.querySelector('.fi-main');
        if (mainContent) {
            console.log('Layout applied:', {
                contentWidth: mainContent.offsetWidth,
                windowWidth: window.innerWidth,
                hasHorizontalScroll: document.body.scrollWidth > window.innerWidth
            });
        }
    }
    
    // Initial ausführen
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyCleanLayout);
    } else {
        applyCleanLayout();
    }
    
    // Nach Livewire Updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            setTimeout(applyCleanLayout, 50);
        });
    }
    
    // Bei Window Resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            applyCleanLayout();
        }, 250);
    });
    
    // MutationObserver für neue Inhalte
    const observer = new MutationObserver(() => {
        removeHorizontalScroll();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style']
    });
    
    // Debug-Funktion
    window.debugCleanLayout = function() {
        console.log('=== CLEAN LAYOUT DEBUG ===');
        console.log('Body scroll width:', document.body.scrollWidth);
        console.log('Window width:', window.innerWidth);
        console.log('Has horizontal scroll:', document.body.scrollWidth > window.innerWidth);
        
        const tables = document.querySelectorAll('.fi-ta-table');
        tables.forEach((table, i) => {
            console.log(`Table ${i + 1}: width=${table.offsetWidth}`);
        });
    };
})();