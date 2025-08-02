/**
 * Responsive Zoom Handler for Filament Admin Panel
 * Detects zoom level and adjusts UI accordingly
 */

document.addEventListener('DOMContentLoaded', function() {
    let currentZoom = 1;
    let resizeTimer;
    let isInitialized = false;
    
    // Function to detect zoom level
    function detectZoomLevel() {
        // Method 1: window.devicePixelRatio / initial ratio
        const zoom = Math.round((window.devicePixelRatio / window.devicePixelRatio) * 100) / 100;
        
        // Method 2: outerWidth/innerWidth (more reliable for browser zoom)
        const zoomOuter = Math.round((window.outerWidth / window.innerWidth) * 100) / 100;
        
        // Method 3: document.documentElement.clientWidth / window.innerWidth
        const zoomClient = Math.round((document.documentElement.clientWidth / window.innerWidth) * 100) / 100;
        
        // Use the most reliable detection
        return Math.min(zoom, zoomOuter, zoomClient);
    }
    
    // Function to update UI based on zoom level
    function updateUIForZoom() {
        const zoom = detectZoomLevel();
        const body = document.body;
        
        // Remove all zoom classes
        body.classList.remove('zoom-out-small', 'zoom-out-medium', 'zoom-out-large', 'zoom-normal', 'zoom-in');
        
        // Add appropriate class based on zoom level
        if (zoom <= 0.5) {
            body.classList.add('zoom-out-large');
        } else if (zoom <= 0.75) {
            body.classList.add('zoom-out-medium');
        } else if (zoom <= 0.9) {
            body.classList.add('zoom-out-small');
        } else if (zoom >= 1.1) {
            body.classList.add('zoom-in');
        } else {
            body.classList.add('zoom-normal');
        }
        
        // Store zoom level for CSS calculations
        document.documentElement.style.setProperty('--zoom-level', zoom);
        
        // Adjust table density based on zoom
        adjustTableDensity(zoom);
        
        // Only log significant changes in development
        if (Math.abs(zoom - currentZoom) > 0.05 && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
            console.debug('Zoom level changed:', zoom);
        }
        currentZoom = zoom;
    }
    
    // Function to adjust table density
    function adjustTableDensity(zoom) {
        const tables = document.querySelectorAll('.fi-ta-table');
        
        tables.forEach(table => {
            if (zoom <= 0.75) {
                // Compact mode for zoomed out view
                table.classList.add('compact-mode');
                table.classList.remove('comfortable-mode');
            } else {
                // Comfortable mode for normal/zoomed in view
                table.classList.remove('compact-mode');
                table.classList.add('comfortable-mode');
            }
        });
    }
    
    // Function to optimize column widths (with performance optimization)
    function optimizeColumnWidths() {
        // Use requestAnimationFrame to avoid forced reflow
        requestAnimationFrame(() => {
            const tables = document.querySelectorAll('.fi-ta-table');
            
            // Batch all DOM reads first
            const tableData = Array.from(tables).map(table => {
                const columns = table.querySelectorAll('th');
                return {
                    table,
                    columns: Array.from(columns).map((th, index) => {
                        const content = th.textContent.trim();
                        let minWidth = 100; // Default minimum
                        
                        // Adjust based on column type
                        if (content.includes('Kunde') || content.includes('Customer')) {
                            minWidth = 200;
                        } else if (content.includes('Datum') || content.includes('Zeit')) {
                            minWidth = 160;
                        } else if (content.includes('Status')) {
                            minWidth = 120;
                        } else if (content.includes('ID')) {
                            minWidth = 80;
                        }
                        
                        return { index, minWidth };
                    })
                };
            });
            
            // Then batch all DOM writes
            tableData.forEach(({ table, columns }) => {
                columns.forEach(({ index, minWidth }) => {
                    const th = table.querySelectorAll('th')[index];
                    if (th) th.style.minWidth = minWidth + 'px';
                    
                    // Apply to corresponding td elements
                    const tds = table.querySelectorAll(`td:nth-child(${index + 1})`);
                    tds.forEach(td => {
                        td.style.minWidth = minWidth + 'px';
                    });
                });
            });
        });
    }
    
    // Function to handle responsive sidebars
    function handleResponsiveSidebar() {
        const sidebar = document.querySelector('.fi-sidebar');
        const main = document.querySelector('.fi-main');
        const zoom = detectZoomLevel();
        
        if (sidebar && main) {
            if (zoom <= 0.75 && window.innerWidth < 1400) {
                // Auto-collapse sidebar when zoomed out on smaller screens
                sidebar.classList.add('fi-sidebar-collapsed');
                main.classList.add('fi-sidebar-collapsed');
            }
        }
    }
    
    // Initialize with debounce to prevent multiple calls
    function initialize() {
        if (!isInitialized) {
            isInitialized = true;
            updateUIForZoom();
            optimizeColumnWidths();
            handleResponsiveSidebar();
        }
    }
    
    // Initialize after a short delay to ensure DOM is ready
    setTimeout(initialize, 100);
    
    // Listen for resize events (includes zoom changes)
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            updateUIForZoom();
            // Only optimize columns if zoom changed significantly
            if (Math.abs(detectZoomLevel() - currentZoom) > 0.1) {
                optimizeColumnWidths();
                handleResponsiveSidebar();
            }
        }, 250);
    });
    
    // Listen for Livewire navigation (for Filament page changes)
    document.addEventListener('livewire:navigated', function() {
        setTimeout(function() {
            updateUIForZoom();
            optimizeColumnWidths();
        }, 100);
    });
    
    // Expose functions globally for debugging
    window.FilamentZoomHandler = {
        detectZoomLevel,
        updateUIForZoom,
        optimizeColumnWidths
    };
});

// Additional CSS classes for zoom levels
const zoomStyles = `
<style>
/* Zoom-out large (50% or less) */
.zoom-out-large .fi-ta-table {
    font-size: 0.7rem !important;
}
.zoom-out-large .fi-ta-table th,
.zoom-out-large .fi-ta-table td {
    padding: 0.25rem 0.5rem !important;
}
.zoom-out-large .fi-badge {
    padding: 0.0625rem 0.25rem !important;
    font-size: 0.6rem !important;
}

/* Zoom-out medium (51-75%) */
.zoom-out-medium .fi-ta-table {
    font-size: 0.8rem !important;
}
.zoom-out-medium .fi-ta-table th,
.zoom-out-medium .fi-ta-table td {
    padding: 0.375rem 0.625rem !important;
}
.zoom-out-medium .fi-badge {
    padding: 0.125rem 0.375rem !important;
    font-size: 0.7rem !important;
}

/* Zoom-out small (76-90%) */
.zoom-out-small .fi-ta-table {
    font-size: 0.875rem !important;
}
.zoom-out-small .fi-ta-table th,
.zoom-out-small .fi-ta-table td {
    padding: 0.5rem 0.75rem !important;
}

/* Compact mode for tables */
.fi-ta-table.compact-mode tr {
    height: 32px !important;
}
.fi-ta-table.compact-mode .fi-ta-actions {
    gap: 0.25rem !important;
}
.fi-ta-table.compact-mode .fi-ta-icon {
    width: 1rem !important;
    height: 1rem !important;
}

/* Comfortable mode for tables */
.fi-ta-table.comfortable-mode tr {
    height: 48px !important;
}
.fi-ta-table.comfortable-mode .fi-ta-actions {
    gap: 0.5rem !important;
}
</style>
`;

// Inject zoom styles
document.head.insertAdjacentHTML('beforeend', zoomStyles);