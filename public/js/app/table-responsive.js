// Table Responsive Enhancement
document.addEventListener('DOMContentLoaded', function() {
    // Function to add data labels to table cells for mobile view
    function addDataLabels() {
        const tables = document.querySelectorAll('.fi-ta-table');
        
        tables.forEach(table => {
            const headers = table.querySelectorAll('thead th');
            const rows = table.querySelectorAll('tbody tr');
            
            // Get header text for each column
            const headerTexts = Array.from(headers).map(header => {
                // Get text content, but filter out icon/button content
                const textElement = header.querySelector('.fi-ta-header-cell-label span') || 
                                  header.querySelector('span') || 
                                  header;
                return textElement.textContent.trim();
            });
            
            // Add data-label to each cell
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    if (headerTexts[index] && headerTexts[index] !== '') {
                        cell.setAttribute('data-label', headerTexts[index]);
                    }
                });
            });
        });
    }
    
    // Run on initial load
    addDataLabels();
    
    // Re-run when Livewire updates the DOM
    if (window.Livewire) {
        Livewire.hook('message.processed', (message, component) => {
            setTimeout(addDataLabels, 100);
        });
    }
    
    // Handle dynamic content updates
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.target.classList.contains('fi-ta-table')) {
                addDataLabels();
            }
        });
    });
    
    // Observe table containers for changes
    const tableContainers = document.querySelectorAll('.fi-ta-content');
    tableContainers.forEach(container => {
        observer.observe(container, {
            childList: true,
            subtree: true
        });
    });
});

// Handle window resize to ensure proper layout
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        // Force re-render of responsive elements
        document.querySelectorAll('.fi-ta-table-wrapper').forEach(wrapper => {
            wrapper.style.display = 'none';
            wrapper.offsetHeight; // Force reflow
            wrapper.style.display = '';
        });
    }, 250);
});