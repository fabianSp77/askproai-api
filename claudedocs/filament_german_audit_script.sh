#!/bin/bash
# Comprehensive Filament German Language Audit Script
# Finds ALL English text in user-facing strings

OUTPUT_FILE="/var/www/api-gateway/claudedocs/FILAMENT_GERMAN_AUDIT_RESULTS.md"

echo "# Filament German Language Audit Results" > "$OUTPUT_FILE"
echo "## Generated: $(date)" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Function to search for English patterns
search_pattern() {
    local pattern="$1"
    local description="$2"
    local file_pattern="$3"

    echo "" >> "$OUTPUT_FILE"
    echo "### $description" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"

    results=$(grep -rn "$pattern" $file_pattern 2>/dev/null | grep -v "//")

    if [ -n "$results" ]; then
        echo '```' >> "$OUTPUT_FILE"
        echo "$results" >> "$OUTPUT_FILE"
        echo '```' >> "$OUTPUT_FILE"
        count=$(echo "$results" | wc -l)
        echo "" >> "$OUTPUT_FILE"
        echo "**Found: $count occurrences**" >> "$OUTPUT_FILE"
    else
        echo "No occurrences found." >> "$OUTPUT_FILE"
    fi
}

echo "## Part 1: Form Labels and Descriptions" >> "$OUTPUT_FILE"

# Search for ->label() with English text
search_pattern "->label\(['\"][A-Z]" "Form Labels (->label)" "/var/www/api-gateway/app/Filament/Resources/"

# Search for ->description() with English text
search_pattern "->description\(['\"]" "Form Descriptions (->description)" "/var/www/api-gateway/app/Filament/Resources/"

# Search for ->placeholder() with English text
search_pattern "->placeholder\(['\"]" "Form Placeholders (->placeholder)" "/var/www/api-gateway/app/Filament/Resources/"

# Search for ->helperText() with English text
search_pattern "->helperText\(['\"]" "Helper Text (->helperText)" "/var/www/api-gateway/app/Filament/Resources/"

# Search for ->hint() with English text
search_pattern "->hint\(['\"]" "Hints (->hint)" "/var/www/api-gateway/app/Filament/Resources/"

echo "" >> "$OUTPUT_FILE"
echo "## Part 2: Table Columns and Headers" >> "$OUTPUT_FILE"

# Search for Column labels
search_pattern "TextColumn::make.*->label\(['\"]" "TextColumn Labels" "/var/www/api-gateway/app/Filament/Resources/"

# Search for heading() with English text
search_pattern "->heading\(['\"]" "Section Headings (->heading)" "/var/www/api-gateway/app/Filament/Resources/"

echo "" >> "$OUTPUT_FILE"
echo "## Part 3: Actions and Buttons" >> "$OUTPUT_FILE"

# Search for action labels
search_pattern "Action::make.*->label\(['\"]" "Action Labels" "/var/www/api-gateway/app/Filament/Resources/"

# Search for button text
search_pattern "->action\(['\"][A-Z]" "Button Text (->action)" "/var/www/api-gateway/app/Filament/Resources/"

echo "" >> "$OUTPUT_FILE"
echo "## Part 4: Badges and Status Labels" >> "$OUTPUT_FILE"

# Search for badge() with English text
search_pattern "->badge\(['\"]" "Badge Labels (->badge)" "/var/www/api-gateway/app/Filament/Resources/"

# Search for enum options with English values
search_pattern "=> ['\"][A-Z]" "Enum/Array Values" "/var/www/api-gateway/app/Filament/Resources/"

echo "" >> "$OUTPUT_FILE"
echo "## Part 5: Validation and Error Messages" >> "$OUTPUT_FILE"

# Search for validation messages
search_pattern "->required\(['\"]" "Required Messages" "/var/www/api-gateway/app/Filament/Resources/"

# Search for custom validation
search_pattern "ValidationException::withMessages" "Validation Exceptions" "/var/www/api-gateway/app/Filament/Resources/"

echo "" >> "$OUTPUT_FILE"
echo "## Part 6: Blade Views" >> "$OUTPUT_FILE"

# Search Blade files for English text in elements
search_pattern "<[^>]*>[A-Z][a-z]+ [A-Z]" "HTML Content with English" "/var/www/api-gateway/resources/views/filament/"

echo "" >> "$OUTPUT_FILE"
echo "## Audit Complete" >> "$OUTPUT_FILE"
echo "Check results above for English text that needs German translation." >> "$OUTPUT_FILE"

chmod +x "$OUTPUT_FILE"
