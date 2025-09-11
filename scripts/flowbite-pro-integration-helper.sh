#!/bin/bash

# Flowbite Pro Integration Helper
# ================================
# This script helps you integrate Flowbite Pro files from Google Drive

echo "🎨 Flowbite Pro Integration Helper"
echo "=================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Base directory
BASE_DIR="/var/www/api-gateway"
FLOWBITE_DIR="$BASE_DIR/resources/flowbite-pro"

# Step 1: Instructions for downloading from Google Drive
echo -e "${BLUE}📥 Step 1: Download Flowbite Pro from Google Drive${NC}"
echo "----------------------------------------"
echo "1. Open your Google Drive link:"
echo "   https://drive.google.com/drive/folders/1MEpv9w12cpdC_upis9VRomEXF47T1KEe"
echo ""
echo "2. Download the entire folder as ZIP:"
echo "   - Click the folder name"
echo "   - Press Ctrl+A (or Cmd+A on Mac) to select all"
echo "   - Right-click → Download"
echo "   - Google Drive will create a ZIP file"
echo ""
echo "3. Transfer the ZIP to your server using one of these methods:"
echo ""
echo -e "${YELLOW}Option A: Direct upload via SCP (from your local machine):${NC}"
echo "   scp ~/Downloads/flowbite-pro.zip root@api.askproai.de:/tmp/"
echo ""
echo -e "${YELLOW}Option B: Use a file transfer service:${NC}"
echo "   - Upload to transfer.sh: curl --upload-file flowbite-pro.zip https://transfer.sh/flowbite-pro.zip"
echo "   - Then on server: wget [URL_from_transfer.sh] -O /tmp/flowbite-pro.zip"
echo ""
read -p "Press Enter when you have uploaded the ZIP file to /tmp/flowbite-pro.zip..."

# Step 2: Check if file exists
echo ""
echo -e "${BLUE}📋 Step 2: Checking for uploaded file...${NC}"
if [ -f "/tmp/flowbite-pro.zip" ]; then
    echo -e "${GREEN}✓ File found!${NC}"
    FILE_SIZE=$(du -h /tmp/flowbite-pro.zip | cut -f1)
    echo "   File size: $FILE_SIZE"
else
    echo -e "${RED}✗ File not found at /tmp/flowbite-pro.zip${NC}"
    echo "Please upload the file first, then run this script again."
    exit 1
fi

# Step 3: Extract files
echo ""
echo -e "${BLUE}📦 Step 3: Extracting Flowbite Pro files...${NC}"
mkdir -p $FLOWBITE_DIR
cd $FLOWBITE_DIR

# Extract with proper directory structure
unzip -q /tmp/flowbite-pro.zip
echo -e "${GREEN}✓ Files extracted${NC}"

# Step 4: Organize files based on Flowbite Pro structure
echo ""
echo -e "${BLUE}🗂️ Step 4: Organizing Flowbite Pro components...${NC}"

# Expected Flowbite Pro structure (adjust based on actual structure)
EXPECTED_DIRS=(
    "src/components"
    "src/layouts"
    "src/pages"
    "dist/css"
    "dist/js"
    "examples"
    "figma"
)

# Check what we got
echo "Found the following structure:"
ls -la $FLOWBITE_DIR/

# Reorganize if needed
if [ -d "$FLOWBITE_DIR/flowbite-pro-main" ]; then
    echo "Moving files from subfolder..."
    mv $FLOWBITE_DIR/flowbite-pro-main/* $FLOWBITE_DIR/
    rm -rf $FLOWBITE_DIR/flowbite-pro-main
fi

# Step 5: Create Laravel integration files
echo ""
echo -e "${BLUE}🔧 Step 5: Creating Laravel integration files...${NC}"

# Create Blade component for Flowbite Pro
cat > $BASE_DIR/resources/views/layouts/flowbite.blade.php << 'EOF'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AskProAI') }}</title>
    
    <!-- Flowbite Pro CSS -->
    <link href="{{ asset('flowbite-pro/dist/flowbite.min.css') }}" rel="stylesheet">
    
    <!-- Custom CSS -->
    @vite(['resources/css/app.css'])
    
    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    {{ $slot }}
    
    <!-- Flowbite Pro JS -->
    <script src="{{ asset('flowbite-pro/dist/flowbite.min.js') }}"></script>
    
    <!-- Alpine.js -->
    <script src="//unpkg.com/alpinejs" defer></script>
    
    <!-- Custom JS -->
    @vite(['resources/js/app.js'])
    
    @stack('scripts')
</body>
</html>
EOF

# Create symlink for public access
ln -sf $FLOWBITE_DIR/dist $BASE_DIR/public/flowbite-pro

# Step 6: Create example components
echo ""
echo -e "${BLUE}📝 Step 6: Creating example components...${NC}"

# Create example data table component
cat > $BASE_DIR/resources/views/components/flowbite/data-table.blade.php << 'EOF'
@props(['data' => [], 'columns' => []])

<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                @foreach($columns as $column)
                    <th scope="col" class="px-6 py-3">
                        {{ $column['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-700">
                    @foreach($columns as $column)
                        <td class="px-6 py-4">
                            {{ data_get($row, $column['field']) }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
EOF

# Create example chart component
cat > $BASE_DIR/resources/views/components/flowbite/chart.blade.php << 'EOF'
@props(['id' => 'chart-'.uniqid(), 'type' => 'line', 'data' => []])

<div id="{{ $id }}" class="bg-white rounded-lg shadow dark:bg-gray-800 p-4 md:p-6">
    <div class="flex justify-between">
        <div>
            <h5 class="leading-none text-3xl font-bold text-gray-900 dark:text-white pb-2">
                {{ $title ?? 'Chart' }}
            </h5>
            <p class="text-base font-normal text-gray-500 dark:text-gray-400">
                {{ $subtitle ?? '' }}
            </p>
        </div>
    </div>
    <div id="{{ $id }}-chart"></div>
</div>

@push('scripts')
<script>
    // ApexCharts integration
    document.addEventListener('DOMContentLoaded', function() {
        const options = {
            chart: {
                type: '{{ $type }}',
                height: 350
            },
            series: @json($data),
            xaxis: {
                categories: @json($categories ?? [])
            }
        };
        
        const chart = new ApexCharts(document.querySelector("#{{ $id }}-chart"), options);
        chart.render();
    });
</script>
@endpush
EOF

# Step 7: Update Tailwind config
echo ""
echo -e "${BLUE}⚙️ Step 7: Updating Tailwind configuration...${NC}"

# Check if Flowbite is in tailwind.config.js
if ! grep -q "flowbite-pro" $BASE_DIR/tailwind.config.js; then
    # Add Flowbite Pro to content paths
    sed -i "/content: \[/a\\    './resources/flowbite-pro/**/*.{html,js}'," $BASE_DIR/tailwind.config.js
    echo -e "${GREEN}✓ Tailwind config updated${NC}"
else
    echo "✓ Tailwind already configured"
fi

# Step 8: Create component registry
echo ""
echo -e "${BLUE}📚 Step 8: Creating component registry...${NC}"

cat > $BASE_DIR/resources/flowbite-pro/component-registry.json << 'EOF'
{
  "components": {
    "alerts": [
      "default", "dismissible", "border", "icon", "list", "additional"
    ],
    "badges": [
      "default", "large", "icon", "notification", "button"
    ],
    "buttons": [
      "default", "gradient", "outline", "size", "icon", "loader", "social"
    ],
    "cards": [
      "default", "image", "horizontal", "user", "product", "interactive"
    ],
    "charts": [
      "area", "line", "column", "bar", "pie", "donut", "radial"
    ],
    "datatables": [
      "default", "search", "pagination", "checkbox", "action"
    ],
    "forms": [
      "input", "textarea", "select", "checkbox", "radio", "toggle", "range"
    ],
    "modals": [
      "default", "popup", "drawer", "success", "delete"
    ],
    "navigation": [
      "navbar", "sidebar", "breadcrumb", "pagination", "tabs", "stepper"
    ]
  },
  "layouts": {
    "admin": [
      "default", "sidebar", "dual-sidebar", "compact"
    ],
    "authentication": [
      "login", "register", "forgot-password", "reset-password", "verify-email"
    ],
    "marketing": [
      "landing", "pricing", "about", "contact", "blog"
    ]
  },
  "integrations": {
    "laravel": true,
    "filament": true,
    "alpine": true,
    "livewire": true
  }
}
EOF

# Step 9: Build assets
echo ""
echo -e "${BLUE}🔨 Step 9: Building assets...${NC}"
cd $BASE_DIR
npm run build

# Step 10: Create test page
echo ""
echo -e "${BLUE}🧪 Step 10: Creating test page...${NC}"

cat > $BASE_DIR/resources/views/flowbite-test.blade.php << 'EOF'
<x-layouts.flowbite>
    <div class="p-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">
            Flowbite Pro Components Test
        </h1>
        
        <!-- Alert Component -->
        <div class="mb-8">
            <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
                <span class="font-medium">Info alert!</span> Flowbite Pro is successfully integrated.
            </div>
        </div>
        
        <!-- Button Components -->
        <div class="mb-8 space-x-4">
            <button type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                Default
            </button>
            <button type="button" class="text-white bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 font-medium rounded-lg text-sm px-5 py-2.5">
                Gradient
            </button>
        </div>
        
        <!-- Data Table -->
        <x-flowbite.data-table 
            :columns="[
                ['field' => 'name', 'label' => 'Name'],
                ['field' => 'email', 'label' => 'Email'],
                ['field' => 'status', 'label' => 'Status']
            ]"
            :data="[
                ['name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'Active'],
                ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'status' => 'Active']
            ]"
        />
    </div>
</x-layouts.flowbite>
EOF

# Add test route
echo "Route::get('/flowbite-test', fn() => view('flowbite-test'));" >> $BASE_DIR/routes/web.php

# Step 11: Summary
echo ""
echo "═══════════════════════════════════════════════════════"
echo -e "${GREEN}🎉 Flowbite Pro Integration Complete!${NC}"
echo "═══════════════════════════════════════════════════════"
echo ""
echo "✅ Files extracted and organized"
echo "✅ Laravel components created"
echo "✅ Tailwind configuration updated"
echo "✅ Component registry created"
echo "✅ Test page available"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo "1. Test the integration: https://api.askproai.de/flowbite-test"
echo "2. Check component registry: resources/flowbite-pro/component-registry.json"
echo "3. Use components in your Blade views with <x-flowbite.component-name />"
echo "4. Customize themes in tailwind.config.js"
echo ""
echo -e "${YELLOW}📚 Documentation:${NC}"
echo "- Flowbite Pro Docs: https://flowbite.com/pro/docs/"
echo "- Component Examples: resources/flowbite-pro/examples/"
echo "- Figma Files: resources/flowbite-pro/figma/"
echo ""
echo "═══════════════════════════════════════════════════════"

# Cleanup
rm -f /tmp/flowbite-pro.zip
echo -e "${GREEN}✓ Temporary files cleaned up${NC}"