#!/bin/bash

# Script to add column management to a Filament resource
# Usage: ./add-column-manager.sh ResourceName

RESOURCE=$1

if [ -z "$RESOURCE" ]; then
    echo "Usage: ./add-column-manager.sh ResourceName"
    echo "Example: ./add-column-manager.sh StaffResource"
    exit 1
fi

RESOURCE_PATH="/var/www/api-gateway/app/Filament/Resources/${RESOURCE}.php"
RESOURCE_SLUG=$(echo "${RESOURCE}" | sed 's/Resource$//' | sed 's/\([A-Z]\)/-\1/g' | sed 's/^-//' | tr '[:upper:]' '[:lower:]')
LIST_PAGE_NAME="List$(echo ${RESOURCE} | sed 's/Resource$//')"
LIST_PAGE_PATH="/var/www/api-gateway/app/Filament/Resources/${RESOURCE}/Pages/${LIST_PAGE_NAME}.php"

echo "Adding column management to ${RESOURCE}..."
echo "Resource path: ${RESOURCE_PATH}"
echo "List page: ${LIST_PAGE_PATH}"
echo "Resource slug: ${RESOURCE_SLUG}"

# Check if resource file exists
if [ ! -f "$RESOURCE_PATH" ]; then
    echo "Error: Resource file not found at ${RESOURCE_PATH}"
    exit 1
fi

# Check if list page exists
if [ ! -f "$LIST_PAGE_PATH" ]; then
    echo "Error: List page not found at ${LIST_PAGE_PATH}"
    exit 1
fi

# Add trait to resource if not already present
if ! grep -q "use HasColumnOrdering;" "$RESOURCE_PATH"; then
    echo "Adding HasColumnOrdering trait to resource..."

    # Add the use statement at the top
    sed -i '/^use Filament\\Resources\\Resource;/a use App\\Filament\\Traits\\HasColumnOrdering;' "$RESOURCE_PATH"

    # Add the trait to the class
    sed -i '/^class.*extends Resource$/a \    use HasColumnOrdering;' "$RESOURCE_PATH"

    echo "✓ Trait added to resource"
else
    echo "✓ HasColumnOrdering trait already present"
fi

# Create column data method template
cat << EOF > /tmp/column_data_method.txt

    /**
     * Get column data for column manager
     */
    public static function getColumnData(): array
    {
        return [
            // TODO: Define all columns here
            ['key' => 'id', 'label' => 'ID', 'visible' => true],
            ['key' => 'name', 'label' => 'Name', 'visible' => true],
            ['key' => 'created_at', 'label' => 'Erstellt', 'visible' => false],
            ['key' => 'updated_at', 'label' => 'Aktualisiert', 'visible' => false],
        ];
    }
EOF

# Check if getColumnData method exists
if ! grep -q "getColumnData()" "$RESOURCE_PATH"; then
    echo "Adding getColumnData method to resource..."

    # Add the method before the last closing brace
    sed -i '/^}$/i \
    \
    /**\
     * Get column data for column manager\
     */\
    public static function getColumnData(): array\
    {\
        return [\
            // TODO: Define all columns here\
            ['"'"'key'"'"' => '"'"'id'"'"', '"'"'label'"'"' => '"'"'ID'"'"', '"'"'visible'"'"' => true],\
            ['"'"'key'"'"' => '"'"'name'"'"', '"'"'label'"'"' => '"'"'Name'"'"', '"'"'visible'"'"' => true],\
            ['"'"'key'"'"' => '"'"'created_at'"'"', '"'"'label'"'"' => '"'"'Erstellt'"'"', '"'"'visible'"'"' => false],\
            ['"'"'key'"'"' => '"'"'updated_at'"'"', '"'"'label'"'"' => '"'"'Aktualisiert'"'"', '"'"'visible'"'"' => false],\
        ];\
    }' "$RESOURCE_PATH"

    echo "✓ getColumnData method added (TODO: Update column definitions)"
else
    echo "✓ getColumnData method already exists"
fi

# Update the list page
echo "Updating list page..."

# Check if column manager action already exists
if ! grep -q "manageColumns" "$LIST_PAGE_PATH"; then
    # Create the updated header actions method
    cat << 'EOF' > /tmp/header_actions.txt
    protected function getHeaderActions(): array
    {
        $resource = static::getResource();
        $resourceSlug = $resource::getSlug();

        return [
            Actions\CreateAction::make(),
            Actions\Action::make('manageColumns')
                ->label('Spalten verwalten')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->modalHeading('Spalten verwalten')
                ->modalSubheading('Ordnen Sie die Spalten per Drag & Drop neu an')
                ->modalContent(function () use ($resource, $resourceSlug) {
                    return view('filament.modals.column-manager-simple', [
                        'resource' => $resourceSlug,
                        'columns' => $resource::getColumnData(),
                    ]);
                })
                ->modalWidth('lg')
                ->modalFooterActions([]),
        ];
    }
EOF

    # Replace the getHeaderActions method
    perl -i -pe 'BEGIN{undef $/;} s/protected function getHeaderActions\(\): array\s*\{[^}]*\}/`cat \/tmp\/header_actions.txt`/smge' "$LIST_PAGE_PATH"

    echo "✓ Column manager action added to list page"
else
    echo "✓ Column manager action already present"
fi

# Update table method to use column ordering
echo "Checking table method..."

if ! grep -q "applyColumnOrdering" "$RESOURCE_PATH"; then
    echo "NOTE: You need to manually wrap your table definition with applyColumnOrdering:"
    echo ""
    echo "public static function table(Table \$table): Table"
    echo "{"
    echo "    \$table = Table::make()"
    echo "        ->columns([/* your columns */]);"
    echo ""
    echo "    return static::applyColumnOrdering(\$table, '${RESOURCE_SLUG}');"
    echo "}"
fi

echo ""
echo "✅ Column management setup complete for ${RESOURCE}!"
echo ""
echo "Next steps:"
echo "1. Update the getColumnData() method in ${RESOURCE_PATH} with all your columns"
echo "2. Wrap the table method with applyColumnOrdering() if not already done"
echo "3. Clear caches: php artisan cache:clear && php artisan view:clear"
echo "4. Rebuild assets: npm run build"
echo "5. Test the column manager on the ${RESOURCE_SLUG} page"