// Fix table content display issues

(function() {
    console.log('Checking table content...');
    
    setTimeout(() => {
        // Find all table cells
        const cells = document.querySelectorAll('.fi-ta-table td, .fi-ta-table th');
        console.log(`Found ${cells.length} table cells`);
        
        let emptyCells = 0;
        let hiddenCells = 0;
        
        cells.forEach(cell => {
            // Check if cell is empty
            if (cell.textContent.trim() === '') {
                emptyCells++;
                
                // Check for child elements that might contain data
                const children = cell.querySelectorAll('*');
                children.forEach(child => {
                    // Force visibility of child elements
                    child.style.visibility = 'visible';
                    child.style.display = 'initial';
                    child.style.opacity = '1';
                });
            }
            
            // Check if cell is hidden
            const style = window.getComputedStyle(cell);
            if (style.visibility === 'hidden' || style.display === 'none' || style.opacity === '0') {
                hiddenCells++;
                cell.style.visibility = 'visible';
                cell.style.display = 'table-cell';
                cell.style.opacity = '1';
            }
            
            // Check for text color issues
            if (style.color === style.backgroundColor || style.color === 'transparent') {
                cell.style.color = '#111827'; // Set to dark gray
            }
        });
        
        console.log(`Empty cells: ${emptyCells}, Hidden cells: ${hiddenCells}`);
        
        // Check for specific Filament table text elements
        const textElements = document.querySelectorAll('.fi-ta-text, .fi-ta-col-wrp, [class*="fi-ta-text"]');
        console.log(`Found ${textElements.length} text elements`);
        
        textElements.forEach(el => {
            el.style.visibility = 'visible';
            el.style.display = 'block';
            el.style.opacity = '1';
            el.style.color = '#111827';
            
            // Check if it has data attributes
            const text = el.textContent || el.innerText;
            if (text && text.trim() !== '') {
                console.log('Text element contains:', text.substring(0, 50));
            }
        });
        
        // Look for data in wire:snapshot attributes (Livewire data)
        const livewireElements = document.querySelectorAll('[wire\\:snapshot]');
        livewireElements.forEach(el => {
            const snapshot = el.getAttribute('wire:snapshot');
            if (snapshot && snapshot.includes('call')) {
                console.log('Livewire data found in element');
            }
        });
        
        // Force column visibility
        const columns = document.querySelectorAll('.fi-ta-col');
        columns.forEach(col => {
            col.style.display = 'table-cell';
            col.style.visibility = 'visible';
        });
        
    }, 1000);
})();