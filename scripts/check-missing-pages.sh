#!/bin/bash

echo "Checking Filament Resources and Their Pages"
echo "==========================================="
echo

resources=(
    "AppointmentResource"
    "BranchResource"
    "CallResource"
    "CompanyResource"
    "CustomerResource"
    "EnhancedCallResource"
    "FlowbiteComponentResource"
    "FlowbiteComponentResourceFixed"
    "FlowbiteSimpleResource"
    "IntegrationResource"
    "PhoneNumberResource"
    "RetellAgentResource"
    "ServiceResource"
    "StaffResource"
    "TenantResource"
    "UserResource"
    "WorkingHourResource"
)

for resource in "${resources[@]}"; do
    echo "📁 $resource"
    echo "─────────────────────────────"
    
    # Check if resource file exists
    resource_file="/var/www/api-gateway/app/Filament/Admin/Resources/${resource}.php"
    if [ ! -f "$resource_file" ]; then
        echo "  ❌ Resource file not found"
        echo
        continue
    fi
    
    # Check navigation status
    if grep -q "protected static bool \$shouldRegisterNavigation = false" "$resource_file" 2>/dev/null; then
        echo "  ⚠️  Hidden from navigation"
    fi
    
    # Check for Pages directory
    pages_dir="/var/www/api-gateway/app/Filament/Admin/Resources/${resource}/Pages"
    if [ ! -d "$pages_dir" ]; then
        echo "  ❌ No Pages directory"
        echo
        continue
    fi
    
    # List all pages
    echo "  📄 Available Pages:"
    
    # Check for standard pages
    pages=("List" "Create" "Edit" "View")
    for page_type in "${pages[@]}"; do
        # Find files matching the pattern
        found=false
        for file in "$pages_dir"/*${page_type}*.php; do
            if [ -f "$file" ]; then
                basename=$(basename "$file" .php)
                echo "    ✅ $basename"
                found=true
            fi
        done
        
        if [ "$found" = false ]; then
            echo "    ❌ No ${page_type} page"
        fi
    done
    
    echo
done

echo "Summary"
echo "======="
echo

# Count resources with missing pages
total_resources=0
resources_with_all_pages=0
resources_with_missing_pages=0

for resource in "${resources[@]}"; do
    resource_file="/var/www/api-gateway/app/Filament/Admin/Resources/${resource}.php"
    if [ -f "$resource_file" ]; then
        ((total_resources++))
        
        pages_dir="/var/www/api-gateway/app/Filament/Admin/Resources/${resource}/Pages"
        if [ -d "$pages_dir" ]; then
            # Count pages
            page_count=$(ls -1 "$pages_dir"/*.php 2>/dev/null | wc -l)
            if [ "$page_count" -ge 4 ]; then
                ((resources_with_all_pages++))
            else
                ((resources_with_missing_pages++))
                echo "⚠️  $resource has only $page_count pages"
            fi
        else
            ((resources_with_missing_pages++))
            echo "❌ $resource has no pages directory"
        fi
    fi
done

echo
echo "Total Resources: $total_resources"
echo "Resources with all pages: $resources_with_all_pages"
echo "Resources with missing pages: $resources_with_missing_pages"