// Browser-Konsole Befehl: Alle versteckten Spalten auf einmal einblenden
// Kopieren Sie diesen gesamten Code und führen Sie ihn in der Browser-Konsole aus (F12)

(function() {
    console.log('🔍 Suche nach versteckten Spalten...');
    
    // 1. Finde das Spalten-Toggle-Menü
    const toggleButton = document.querySelector('button[x-on\\:click*="columnsOpen"]');
    if (toggleButton) {
        console.log('✅ Spalten-Toggle-Button gefunden');
        
        // Öffne das Menü
        toggleButton.click();
        
        setTimeout(() => {
            // Finde alle Checkboxen
            const checkboxes = document.querySelectorAll('input[type="checkbox"][wire\\:model*="toggledColumns"]');
            let activated = 0;
            
            checkboxes.forEach(cb => {
                if (!cb.checked) {
                    const label = cb.closest('label');
                    const columnName = label ? label.textContent.trim() : 'Unbekannt';
                    cb.click();
                    activated++;
                    console.log(`✅ Aktiviert: ${columnName}`);
                }
            });
            
            if (activated > 0) {
                console.log(`\n🎉 ${activated} versteckte Spalten wurden aktiviert!`);
                console.log('⚠️  Die Seite lädt neu, um die Änderungen anzuzeigen...');
            } else {
                console.log('ℹ️  Alle Spalten sind bereits sichtbar.');
            }
            
            // Schließe das Menü
            setTimeout(() => {
                const closeButton = document.querySelector('[x-on\\:click="columnsOpen = false"]');
                if (closeButton) closeButton.click();
            }, 500);
            
        }, 300);
    } else {
        console.log('❌ Kein Spalten-Toggle-Button gefunden. Versuche alternative Methode...');
        
        // Alternative: Direkt Livewire aufrufen
        const component = document.querySelector('[wire\\:id]');
        if (component && window.Livewire) {
            const wireId = component.getAttribute('wire:id');
            console.log('🔧 Verwende Livewire-Direktzugriff...');
            
            // Hole alle möglichen Spalten-IDs
            const allColumns = [
                'branch.name', 'company.name', 'assigned_staff_id', 
                'email', 'created_at', 'updated_at', 'calcom_user_id',
                'booking_calcom_reference'
            ];
            
            allColumns.forEach(col => {
                try {
                    Livewire.find(wireId).set(`tableColumnToggleFormState.${col}`, true);
                    console.log(`✅ Aktiviert via Livewire: ${col}`);
                } catch (e) {
                    // Spalte existiert nicht in dieser Ansicht
                }
            });
        }
    }
    
    // 2. Zusätzlich: Scrollbar erzwingen
    setTimeout(() => {
        document.querySelectorAll('.fi-ta-content').forEach(container => {
            container.style.cssText = `
                overflow-x: auto !important;
                overflow-y: visible !important;
                max-width: 100% !important;
                width: 100% !important;
                display: block !important;
            `;
            console.log('✅ Horizontale Scrollbar aktiviert');
        });
    }, 1000);
})();