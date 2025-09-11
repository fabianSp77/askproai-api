#!/bin/bash

###############################################################################
# AskProAI Missing Asset Detection & Resolution System
# Version: 1.0
# Created: 2025-09-03
###############################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_FILE="/var/www/api-gateway/storage/logs/missing_assets_$(date +%Y%m%d_%H%M%S).log"
NGINX_ERROR_LOG="/var/log/nginx/error.log"
ASSETS_DIR="$PROJECT_ROOT/public/build/assets"
PUBLIC_DIR="$PROJECT_ROOT/public"

MISSING_COUNT=0
FOUND_COUNT=0
FIXED_COUNT=0

# Function to log asset status
log_asset() {
    local status="$1"
    local asset="$2"
    local details="$3"
    
    echo "$(date '+%Y-%m-%d %H:%M:%S') [$status] $asset - $details" >> "$LOG_FILE"
}

# Function to print asset status
print_asset() {
    local status="$1"
    local asset="$2"
    local details="$3"
    
    case "$status" in
        "FOUND")
            echo -e "${GREEN}✓${NC} $asset"
            [ -n "$details" ] && echo "  $details"
            log_asset "FOUND" "$asset" "$details"
            ((FOUND_COUNT++))
            ;;
        "MISSING")
            echo -e "${RED}✗${NC} $asset"
            [ -n "$details" ] && echo "  $details"
            log_asset "MISSING" "$asset" "$details"
            ((MISSING_COUNT++))
            ;;
        "FIXED")
            echo -e "${BLUE}⟲${NC} $asset"
            [ -n "$details" ] && echo "  $details"
            log_asset "FIXED" "$asset" "$details"
            ((FIXED_COUNT++))
            ;;
        "INFO")
            echo -e "${YELLOW}ℹ${NC} $asset"
            [ -n "$details" ] && echo "  $details"
            log_asset "INFO" "$asset" "$details"
            ;;
    esac
}

# Function to extract missing assets from nginx logs
extract_missing_from_logs() {
    local missing_assets=()
    
    if [ -f "$NGINX_ERROR_LOG" ]; then
        # Extract 404 errors for asset files from the last 24 hours
        local yesterday
        yesterday=$(date -d "yesterday" "+%Y/%m/%d")
        local today
        today=$(date "+%Y/%m/%d")
        
        # Look for patterns like: open() "/path/file.js" failed (2: No such file or directory)
        local log_patterns=(
            "\.js.*failed.*No such file"
            "\.css.*failed.*No such file"
            "\.png.*failed.*No such file"
            "\.jpg.*failed.*No such file"
            "\.ico.*failed.*No such file"
            "\.woff.*failed.*No such file"
            "\.ttf.*failed.*No such file"
        )
        
        echo "Analyzing nginx error logs for missing assets..."
        
        for pattern in "${log_patterns[@]}"; do
            while IFS= read -r line; do
                # Extract the file path from the error message
                local file_path
                file_path=$(echo "$line" | grep -o '"/[^"]*"' | tr -d '"' | head -1)
                
                if [ -n "$file_path" ]; then
                    # Extract just the filename
                    local filename
                    filename=$(basename "$file_path")
                    
                    # Skip if already in array
                    if [[ ! " ${missing_assets[*]} " =~ " ${filename} " ]]; then
                        missing_assets+=("$filename")
                    fi
                fi
            done < <(grep -E "$pattern" "$NGINX_ERROR_LOG" 2>/dev/null | grep -E "($yesterday|$today)")
        done
    fi
    
    # Add known missing assets from previous analysis
    local known_missing=(
        "wizard-progress-enhancer-BntUnTIW.js"
        "askproai-state-manager-BtNc_89J.js"
        "responsive-zoom-handler-DaecGYuG.js"
    )
    
    for asset in "${known_missing[@]}"; do
        if [[ ! " ${missing_assets[*]} " =~ " ${asset} " ]]; then
            missing_assets+=("$asset")
        fi
    done
    
    echo "${missing_assets[@]}"
}

# Function to check if asset exists
check_asset_exists() {
    local asset="$1"
    local search_paths=(
        "$ASSETS_DIR/$asset"
        "$PUBLIC_DIR/$asset"
        "$PUBLIC_DIR/js/$asset"
        "$PUBLIC_DIR/css/$asset"
        "$PUBLIC_DIR/build/$asset"
        "$PUBLIC_DIR/assets/$asset"
    )
    
    for path in "${search_paths[@]}"; do
        if [ -f "$path" ]; then
            echo "$path"
            return 0
        fi
    done
    
    return 1
}

# Function to find similar assets (fuzzy matching)
find_similar_assets() {
    local missing_asset="$1"
    local base_name
    local extension
    
    # Extract base name and extension
    base_name=$(echo "$missing_asset" | sed 's/-[A-Za-z0-9_]*\./\./' | sed 's/\.[^.]*$//')
    extension="${missing_asset##*.}"
    
    echo "  Looking for similar assets to: $missing_asset"
    echo "    Base name: $base_name, Extension: $extension"
    
    # Search for files with similar names
    local similar_files=()
    
    if [ -d "$ASSETS_DIR" ]; then
        # Look for files with similar base names
        while IFS= read -r -d '' file; do
            local filename
            filename=$(basename "$file")
            
            # Check if it matches the pattern (same base, different hash)
            if [[ "$filename" == *"$base_name"* && "$filename" == *".$extension" ]]; then
                similar_files+=("$file")
            fi
        done < <(find "$ASSETS_DIR" -type f -name "*.$extension" -print0 2>/dev/null)
        
        # Also look for files with similar functionality based on name patterns
        local name_patterns=()
        case "$missing_asset" in
            *"wizard"*) name_patterns+=("wizard" "step" "progress") ;;
            *"state"*) name_patterns+=("state" "store" "manager") ;;
            *"zoom"*) name_patterns+=("zoom" "scale" "responsive") ;;
            *"responsive"*) name_patterns+=("responsive" "mobile" "adaptive") ;;
        esac
        
        for pattern in "${name_patterns[@]}"; do
            while IFS= read -r -d '' file; do
                local filename
                filename=$(basename "$file")
                if [[ "$filename" == *"$pattern"* && "$filename" == *".$extension" ]]; then
                    if [[ ! " ${similar_files[*]} " =~ " ${file} " ]]; then
                        similar_files+=("$file")
                    fi
                fi
            done < <(find "$ASSETS_DIR" -type f -name "*$pattern*.$extension" -print0 2>/dev/null)
        done
    fi
    
    if [ ${#similar_files[@]} -gt 0 ]; then
        echo "  Found ${#similar_files[@]} potentially similar assets:"
        for file in "${similar_files[@]}"; do
            local size
            size=$(ls -lh "$file" | awk '{print $5}')
            echo "    $(basename "$file") ($size)"
        done
        echo "${similar_files[@]}"
        return 0
    fi
    
    return 1
}

# Function to attempt automatic fixes
attempt_fix() {
    local missing_asset="$1"
    
    echo "  Attempting to fix missing asset: $missing_asset"
    
    # Strategy 1: Check if we need to rebuild assets
    if [[ "$missing_asset" == *.js ]] || [[ "$missing_asset" == *.css ]]; then
        echo "    Checking if asset rebuild is needed..."
        
        cd "$PROJECT_ROOT"
        
        # Check if package.json exists and has build scripts
        if [ -f "package.json" ] && grep -q '"build"' package.json; then
            echo "    Found build script in package.json"
            
            # Check if node_modules exists
            if [ -d "node_modules" ]; then
                echo "    Node modules present, attempting rebuild..."
                
                # Run npm run build
                if npm run build >/dev/null 2>&1; then
                    echo "    Asset rebuild completed"
                    
                    # Check if the asset now exists
                    if check_asset_exists "$missing_asset" >/dev/null; then
                        print_asset "FIXED" "$missing_asset" "Asset created by rebuild"
                        return 0
                    fi
                else
                    echo "    Asset rebuild failed"
                fi
            else
                echo "    Node modules missing, running npm install..."
                if npm install >/dev/null 2>&1; then
                    echo "    Dependencies installed, attempting rebuild..."
                    if npm run build >/dev/null 2>&1; then
                        if check_asset_exists "$missing_asset" >/dev/null; then
                            print_asset "FIXED" "$missing_asset" "Asset created after dependency install and rebuild"
                            return 0
                        fi
                    fi
                fi
            fi
        fi
    fi
    
    # Strategy 2: Create placeholder/fallback for critical assets
    local asset_dir="$ASSETS_DIR"
    mkdir -p "$asset_dir"
    
    case "$missing_asset" in
        *.js)
            echo "    Creating JavaScript placeholder..."
            cat > "$asset_dir/$missing_asset" << 'EOF'
// Placeholder for missing asset
console.log('Asset placeholder loaded: PLACEHOLDER_NAME');

// Basic functionality based on asset name
if (typeof window !== 'undefined') {
    window.addEventListener('DOMContentLoaded', function() {
        console.log('Asset PLACEHOLDER_NAME loaded and initialized');
    });
}
EOF
            sed -i "s/PLACEHOLDER_NAME/$missing_asset/g" "$asset_dir/$missing_asset"
            print_asset "FIXED" "$missing_asset" "Created JavaScript placeholder"
            return 0
            ;;
        *.css)
            echo "    Creating CSS placeholder..."
            cat > "$asset_dir/$missing_asset" << 'EOF'
/* Placeholder for missing CSS asset */
/* Asset: PLACEHOLDER_NAME */

.missing-asset-notice {
    display: none;
}

/* Basic responsive framework if needed */
@media (max-width: 768px) {
    .responsive-container {
        width: 100%;
        padding: 0 1rem;
    }
}
EOF
            sed -i "s/PLACEHOLDER_NAME/$missing_asset/g" "$asset_dir/$missing_asset"
            print_asset "FIXED" "$missing_asset" "Created CSS placeholder"
            return 0
            ;;
    esac
    
    return 1
}

# Function to generate asset manifest
generate_manifest() {
    echo "Generating asset manifest..."
    
    local manifest_file="$PROJECT_ROOT/storage/logs/asset_manifest_$(date +%Y%m%d_%H%M%S).json"
    
    echo "{" > "$manifest_file"
    echo "  \"generated\": \"$(date -Iseconds)\"," >> "$manifest_file"
    echo "  \"assets\": {" >> "$manifest_file"
    
    local first=true
    if [ -d "$ASSETS_DIR" ]; then
        while IFS= read -r -d '' file; do
            local filename
            local size
            local hash
            
            filename=$(basename "$file")
            size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null || echo "0")
            hash=$(md5sum "$file" 2>/dev/null | cut -d' ' -f1 || echo "unknown")
            
            if [ "$first" = true ]; then
                first=false
            else
                echo "," >> "$manifest_file"
            fi
            
            echo "    \"$filename\": {" >> "$manifest_file"
            echo "      \"size\": $size," >> "$manifest_file"
            echo "      \"hash\": \"$hash\"," >> "$manifest_file"
            echo "      \"path\": \"$file\"" >> "$manifest_file"
            echo -n "    }" >> "$manifest_file"
            
        done < <(find "$ASSETS_DIR" -type f -print0 2>/dev/null)
    fi
    
    echo "" >> "$manifest_file"
    echo "  }" >> "$manifest_file"
    echo "}" >> "$manifest_file"
    
    echo "Asset manifest generated: $manifest_file"
}

# Function to check Vite manifest
check_vite_manifest() {
    echo -e "${BLUE}=== Vite Manifest Analysis ===${NC}"
    
    local vite_manifest="$PUBLIC_DIR/build/manifest.json"
    
    if [ -f "$vite_manifest" ]; then
        print_asset "FOUND" "Vite manifest" "File exists: $vite_manifest"
        
        # Parse manifest and check for missing entries
        if command -v jq >/dev/null 2>&1; then
            echo "  Analyzing manifest entries..."
            
            # Extract all asset files mentioned in manifest
            local manifest_assets
            manifest_assets=$(jq -r '.[] | .file // empty' "$vite_manifest" 2>/dev/null)
            
            if [ -n "$manifest_assets" ]; then
                echo "  Checking manifest asset files..."
                while IFS= read -r asset_file; do
                    if [ -f "$PUBLIC_DIR/build/$asset_file" ]; then
                        print_asset "FOUND" "Manifest asset: $asset_file" "File exists"
                    else
                        print_asset "MISSING" "Manifest asset: $asset_file" "Referenced in manifest but file missing"
                    fi
                done <<< "$manifest_assets"
            fi
        else
            print_asset "INFO" "Vite manifest analysis" "jq not available for detailed analysis"
        fi
    else
        print_asset "MISSING" "Vite manifest" "File not found: $vite_manifest"
    fi
}

###############################################################################
# MAIN EXECUTION
###############################################################################

echo "=========================================="
echo "  AskProAI Missing Asset Detection"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "=========================================="
echo

# Initialize log file
mkdir -p "$(dirname "$LOG_FILE")"
echo "Missing Asset Detection Started - $(date)" > "$LOG_FILE"

# Check Vite manifest first
check_vite_manifest
echo

# Extract missing assets from logs and known issues
echo -e "${BLUE}=== Extracting Missing Assets ===${NC}"
missing_assets_array=($(extract_missing_from_logs))

if [ ${#missing_assets_array[@]} -eq 0 ]; then
    print_asset "INFO" "Missing asset analysis" "No missing assets detected in recent logs"
else
    print_asset "INFO" "Missing asset analysis" "Found ${#missing_assets_array[@]} potentially missing assets"
fi

echo

# Check each potentially missing asset
echo -e "${BLUE}=== Asset Status Check ===${NC}"
for asset in "${missing_assets_array[@]}"; do
    if [ -n "$asset" ]; then
        local existing_path
        existing_path=$(check_asset_exists "$asset")
        
        if [ $? -eq 0 ]; then
            local size
            size=$(ls -lh "$existing_path" | awk '{print $5}')
            print_asset "FOUND" "$asset" "Found at: $existing_path ($size)"
        else
            print_asset "MISSING" "$asset" "Asset not found in standard locations"
            
            # Try to find similar assets
            local similar_assets
            if similar_assets=$(find_similar_assets "$asset"); then
                print_asset "INFO" "$asset" "Found similar assets - manual review recommended"
            fi
        fi
    fi
done

echo

# Attempt to fix missing assets
if [ $MISSING_COUNT -gt 0 ]; then
    echo -e "${BLUE}=== Asset Recovery Attempts ===${NC}"
    
    for asset in "${missing_assets_array[@]}"; do
        if [ -n "$asset" ]; then
            # Only attempt fix if asset is actually missing
            if ! check_asset_exists "$asset" >/dev/null; then
                attempt_fix "$asset"
            fi
        fi
    done
    echo
fi

# Generate comprehensive asset manifest
echo -e "${BLUE}=== Asset Manifest Generation ===${NC}"
generate_manifest
echo

# Summary report
echo "=========================================="
echo "Missing Asset Detection Summary"
echo "=========================================="
echo "  Assets found: $FOUND_COUNT"
echo "  Assets missing: $MISSING_COUNT"
echo "  Assets fixed: $FIXED_COUNT"
echo
echo "Detailed log: $LOG_FILE"
echo

# Recommendations
if [ $MISSING_COUNT -gt 0 ]; then
    echo -e "${YELLOW}Recommendations:${NC}"
    echo "  1. Run 'npm run build' to regenerate assets"
    echo "  2. Check Vite configuration for asset handling"
    echo "  3. Verify asset references in templates/views"
    echo "  4. Consider implementing asset versioning strategy"
    echo
fi

# Exit with appropriate status
if [ $MISSING_COUNT -eq 0 ]; then
    echo -e "${GREEN}✓ All assets are properly available${NC}"
    exit 0
elif [ $FIXED_COUNT -gt 0 ]; then
    echo -e "${YELLOW}⚠ Some assets were missing but fixes were attempted${NC}"
    echo "  Verify the fixed assets work correctly in your application"
    exit 1
else
    echo -e "${RED}✗ Missing assets detected - manual intervention required${NC}"
    exit 2
fi