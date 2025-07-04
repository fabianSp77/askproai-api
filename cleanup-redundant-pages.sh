#!/bin/bash

# Script zum Löschen redundanter Filament Pages
# Ausführen mit: bash cleanup-redundant-pages.sh

echo "Bereinigung redundanter Filament Pages..."

# Backup erstellen
BACKUP_DIR="storage/app/backup/pages_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Liste der zu löschenden Pages
PAGES_TO_DELETE=(
    # Dashboard-Duplikate
    "app/Filament/Admin/Pages/OperationsDashboard.php"
    "app/Filament/Admin/Pages/MCPDashboard.php"
    
    # System-Status Duplikate
    "app/Filament/Admin/Pages/BasicSystemStatus.php"
    "app/Filament/Admin/Pages/SystemHealthBasic.php"
    "app/Filament/Admin/Pages/SystemHealthSimple.php"
    "app/Filament/Admin/Pages/SystemHealthMonitorDebug.php"
    "app/Filament/Admin/Pages/SystemMonitoring.php"
    "app/Filament/Admin/Pages/CompanyConfigStatus.php"
    "app/Filament/Admin/Pages/QuantumSystemMonitoring.php"
    
    # Setup-Wizard Duplikate
    "app/Filament/Admin/Pages/QuickSetupWizardV2.php"
    "app/Filament/Admin/Pages/SimpleOnboarding.php"
    "app/Filament/Admin/Pages/SimpleCompanyIntegrationPortal.php"
    "app/Filament/Admin/Pages/BasicCompanyConfig.php"
    
    # Sync-Manager Duplikate
    "app/Filament/Admin/Pages/SimpleSyncManager.php"
    "app/Filament/Admin/Pages/IntelligentSyncManager.php"
    "app/Filament/Admin/Pages/CalcomSyncStatus.php"
    
    # Event-Assignment Duplikate
    "app/Filament/Admin/Pages/StaffEventAssignmentModern.php"
    
    # Retell-Duplikate
    "app/Filament/Admin/Pages/RetellConfigurationCenter.php"
    "app/Filament/Admin/Pages/RetellAgentEditor.php"
    "app/Filament/Admin/Pages/RetellAgentImportWizard.php"
    
    # Sonstige
    "app/Filament/Admin/Pages/ErrorFallback.php"
    "app/Filament/Admin/Pages/TableDebug.php"
    "app/Filament/Admin/Pages/SetupSuccessPage.php"
    "app/Filament/Admin/Pages/PricingCalculator.php"
    "app/Filament/Admin/Pages/SystemImprovements.php"
    "app/Filament/Admin/Pages/MLTrainingDashboardLivewire.php"
    "app/Filament/Admin/Pages/ListCompanies.php"
)

# Backup und Löschen
for file in "${PAGES_TO_DELETE[@]}"; do
    if [ -f "$file" ]; then
        echo "Backup: $file"
        cp "$file" "$BACKUP_DIR/"
        echo "Lösche: $file"
        rm "$file"
    else
        echo "Nicht gefunden: $file"
    fi
done

echo ""
echo "Bereinigung abgeschlossen!"
echo "Backup erstellt in: $BACKUP_DIR"
echo ""
echo "Nächste Schritte:"
echo "1. php artisan optimize:clear"
echo "2. php artisan filament:cache-components"
echo "3. Teste die Navigation im Admin Panel"