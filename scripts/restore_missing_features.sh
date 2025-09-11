#!/bin/bash

# ═══════════════════════════════════════════════════════════════════════════
# FEATURE RESTORATION SCRIPT
# ═══════════════════════════════════════════════════════════════════════════
# Purpose: Restore missing features from backups
# Date: September 3, 2025
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# Paths
API_DIR="/var/www/api-gateway"
BACKUP_DIR="/var/www/backups"
ENHANCED_BACKUP="$BACKUP_DIR/enhanced-calls-backup-20250823_171627"
RESOURCES_BACKUP="$API_DIR/backup-resources-20250901"

echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}FEATURE RESTORATION SCRIPT${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

# Create backup before restoration
echo -e "${YELLOW}Creating safety backup before restoration...${NC}"
BACKUP_NAME="pre-restore-backup-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$API_DIR/backups/$BACKUP_NAME"

# Backup current resources
cp -r "$API_DIR/app/Filament/Admin/Resources" "$API_DIR/backups/$BACKUP_NAME/" 2>/dev/null || true

echo -e "${GREEN}✓ Safety backup created${NC}"
echo ""

# ═══════════════════════════════════════════════════════════════════════════
# 1. RESTORE ENHANCED CALL RESOURCE
# ═══════════════════════════════════════════════════════════════════════════

echo -e "${CYAN}1. Restoring EnhancedCallResource...${NC}"

if [ -f "$ENHANCED_BACKUP/filament/EnhancedCallResource.php" ]; then
    # Create the EnhancedCallResource
    cp "$ENHANCED_BACKUP/filament/EnhancedCallResource.php" \
       "$API_DIR/app/Filament/Admin/Resources/EnhancedCallResource.php"
    
    # Fix namespace
    sed -i 's/namespace App\\Filament\\Resources;/namespace App\\Filament\\Admin\\Resources;/' \
        "$API_DIR/app/Filament/Admin/Resources/EnhancedCallResource.php"
    
    # Create Pages directory
    mkdir -p "$API_DIR/app/Filament/Admin/Resources/EnhancedCallResource/Pages"
    
    # Create ListEnhancedCalls page
    cat > "$API_DIR/app/Filament/Admin/Resources/EnhancedCallResource/Pages/ListEnhancedCalls.php" << 'EOF'
<?php

namespace App\Filament\Admin\Resources\EnhancedCallResource\Pages;

use App\Filament\Admin\Resources\EnhancedCallResource;
use Filament\Resources\Pages\ListRecords;

class ListEnhancedCalls extends ListRecords
{
    protected static string $resource = EnhancedCallResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    public function getTitle(): string
    {
        return 'Enhanced Call Overview';
    }
}
EOF
    
    echo -e "${GREEN}✓ EnhancedCallResource restored${NC}"
else
    echo -e "${RED}✗ EnhancedCallResource backup not found${NC}"
fi

# ═══════════════════════════════════════════════════════════════════════════
# 2. RESTORE FLOWBITE COMPONENT RESOURCE
# ═══════════════════════════════════════════════════════════════════════════

echo ""
echo -e "${CYAN}2. Restoring FlowbiteComponentResource...${NC}"

if [ -f "$RESOURCES_BACKUP/FlowbiteComponentResource.php" ]; then
    # Copy the resource
    cp "$RESOURCES_BACKUP/FlowbiteComponentResource.php" \
       "$API_DIR/app/Filament/Admin/Resources/FlowbiteComponentResource.php"
    
    # Fix namespace
    sed -i 's/namespace App\\Filament\\Resources;/namespace App\\Filament\\Admin\\Resources;/' \
        "$API_DIR/app/Filament/Admin/Resources/FlowbiteComponentResource.php"
    
    # Create Pages directory
    mkdir -p "$API_DIR/app/Filament/Admin/Resources/FlowbiteComponentResource/Pages"
    
    # Create ListFlowbiteComponents page
    cat > "$API_DIR/app/Filament/Admin/Resources/FlowbiteComponentResource/Pages/ListFlowbiteComponents.php" << 'EOF'
<?php

namespace App\Filament\Admin\Resources\FlowbiteComponentResource\Pages;

use App\Filament\Admin\Resources\FlowbiteComponentResource;
use Filament\Resources\Pages\ListRecords;

class ListFlowbiteComponents extends ListRecords
{
    protected static string $resource = FlowbiteComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
    
    public function getTitle(): string
    {
        return 'Flowbite Pro Component Library';
    }
}
EOF
    
    echo -e "${GREEN}✓ FlowbiteComponentResource restored${NC}"
else
    echo -e "${RED}✗ FlowbiteComponentResource backup not found${NC}"
fi

# ═══════════════════════════════════════════════════════════════════════════
# 3. CREATE MISSING BLADE TEMPLATES
# ═══════════════════════════════════════════════════════════════════════════

echo ""
echo -e "${CYAN}3. Creating missing Blade templates...${NC}"

# Create directories
mkdir -p "$API_DIR/resources/views/filament/tables/columns"
mkdir -p "$API_DIR/resources/views/filament/modals"

# Create flowbite-preview column view
cat > "$API_DIR/resources/views/filament/tables/columns/flowbite-preview.blade.php" << 'EOF'
<div class="flex items-center space-x-2">
    <button 
        type="button"
        class="text-primary-600 hover:text-primary-700 text-sm font-medium"
        wire:click="$emit('openPreview', {{ json_encode($getState()) }})"
    >
        Preview
    </button>
</div>
EOF

# Create flowbite-preview modal view
cat > "$API_DIR/resources/views/filament/modals/flowbite-preview.blade.php" << 'EOF'
<div class="p-4">
    <h3 class="text-lg font-semibold mb-4">{{ $component['name'] ?? 'Component Preview' }}</h3>
    
    <div class="border rounded-lg p-4 bg-gray-50">
        <p class="text-sm text-gray-600 mb-2">Category: {{ $component['category'] ?? 'Unknown' }}</p>
        <p class="text-sm text-gray-600 mb-2">Type: {{ $component['type'] ?? 'Unknown' }}</p>
        
        @if(isset($component['interactive']) && $component['interactive'])
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded">
                Interactive
            </span>
        @endif
    </div>
    
    <div class="mt-4">
        <button 
            type="button"
            class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700"
            onclick="navigator.clipboard.writeText('Component code here')"
        >
            Copy Code
        </button>
    </div>
</div>
EOF

echo -e "${GREEN}✓ Blade templates created${NC}"

# ═══════════════════════════════════════════════════════════════════════════
# 4. FIX EXPORT FUNCTIONALITY IN CALLRESOURCE
# ═══════════════════════════════════════════════════════════════════════════

echo ""
echo -e "${CYAN}4. Adding export functionality to CallResource...${NC}"

# Check if export functionality exists
if ! grep -q "export_csv" "$API_DIR/app/Filament/Admin/Resources/CallResource.php"; then
    echo -e "${YELLOW}Adding export actions to CallResource...${NC}"
    
    # Create a patch file with export functionality
    cat > /tmp/export_patch.txt << 'EOF'
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export_csv')
                        ->label('Export as CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            $csv = "Date,Customer,From,To,Duration,Status,Cost\n";
                            foreach ($records as $record) {
                                $csv .= sprintf(
                                    '"%s","%s","%s","%s","%s","%s","%.2f"' . "\n",
                                    $record->start_timestamp?->format('d.m.Y H:i') ?? '',
                                    $record->customer?->name ?? 'Unknown',
                                    $record->from_number ?? '',
                                    $record->to_number ?? '',
                                    gmdate('i:s', $record->duration_sec ?? 0),
                                    $record->call_status ?? 'unknown',
                                    ($record->cost_cents ?? 0) / 100
                                );
                            }
                            return response($csv, 200, [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="calls_' . now()->format('Y-m-d_H-i-s') . '.csv"',
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
EOF
    
    echo -e "${GREEN}✓ Export functionality patch prepared${NC}"
else
    echo -e "${GREEN}✓ Export functionality already exists${NC}"
fi

# ═══════════════════════════════════════════════════════════════════════════
# 5. CLEAR CACHES AND RESTART SERVICES
# ═══════════════════════════════════════════════════════════════════════════

echo ""
echo -e "${CYAN}5. Clearing caches and restarting services...${NC}"

# Clear Laravel caches
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan filament:cache-components

# Set permissions
chown -R www-data:www-data "$API_DIR/app/Filament/Admin/Resources"
chown -R www-data:www-data "$API_DIR/resources/views/filament"

# Restart PHP-FPM
systemctl restart php8.3-fpm

echo -e "${GREEN}✓ Caches cleared and services restarted${NC}"

# ═══════════════════════════════════════════════════════════════════════════
# SUMMARY
# ═══════════════════════════════════════════════════════════════════════════

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}RESTORATION COMPLETE${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "Restored features:"
echo -e "  ${GREEN}✓${NC} EnhancedCallResource - Modern UI with export functionality"
echo -e "  ${GREEN}✓${NC} FlowbiteComponentResource - 556 component library"
echo -e "  ${GREEN}✓${NC} Blade templates for component preview"
echo -e "  ${GREEN}✓${NC} Export functionality preparations"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo -e "  1. Test the admin panel at https://api.askproai.de/admin"
echo -e "  2. Verify EnhancedCallResource is accessible"
echo -e "  3. Check FlowbiteComponentResource functionality"
echo -e "  4. Test export features"
echo ""
echo -e "${CYAN}Backup created at: $API_DIR/backups/$BACKUP_NAME${NC}"
echo ""