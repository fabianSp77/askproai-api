#!/bin/bash

# Flow V26 - Alternative Selection - Quick Commands
# Status: ✅ APPLIED & VERIFIED

echo "╔════════════════════════════════════════════════════════════╗"
echo "║  Flow V26 - Alternative Selection Commands                 ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Function to display menu
show_menu() {
    echo "Select an action:"
    echo ""
    echo "  [1] Verify flow changes"
    echo "  [2] View current flow structure"
    echo "  [3] Enable test call logging"
    echo "  [4] Tail logs (live monitoring)"
    echo "  [5] Publish agent (after testing)"
    echo "  [6] Show quick reference"
    echo "  [7] Show test scenario"
    echo "  [q] Quit"
    echo ""
}

# Main loop
while true; do
    show_menu
    read -p "Choice: " choice
    echo ""

    case $choice in
        1)
            echo "🔍 Running verification..."
            php scripts/verify_flow_v26_extract.php
            ;;
        2)
            echo "📊 Current flow structure:"
            php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\$flow = Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.retellai.api_key')
])->get('https://api.retellai.com/get-conversation-flow/conversation_flow_a58405e3f67a')->json();
echo 'Version: V' . \$flow['version'] . \"\n\";
echo 'Total nodes: ' . count(\$flow['nodes']) . \"\n\";
echo 'Extract node: ' . (collect(\$flow['nodes'])->contains('id', 'node_extract_alternative_selection') ? '✅' : '❌') . \"\n\";
echo 'Confirm node: ' . (collect(\$flow['nodes'])->contains('id', 'node_confirm_alternative') ? '✅' : '❌') . \"\n\";
"
            ;;
        3)
            echo "📝 Enabling test call logging..."
            php scripts/enable_testcall_logging.sh
            echo "✅ Logging enabled"
            ;;
        4)
            echo "📡 Monitoring logs (Ctrl+C to stop)..."
            tail -f storage/logs/laravel.log | grep --color=auto -E "RETELL|selected_alternative_time"
            ;;
        5)
            echo "🚀 Publishing agent..."
            read -p "Are you sure? Tests passed? [y/N]: " confirm
            if [[ $confirm == "y" || $confirm == "Y" ]]; then
                php scripts/publish_agent_v16.php
            else
                echo "❌ Cancelled"
            fi
            ;;
        6)
            cat FLOW_V26_QUICK_REFERENCE.md
            ;;
        7)
            echo "═══════════════════════════════════════════════════════════"
            echo "TEST SCENARIO - Alternative Selection"
            echo "═══════════════════════════════════════════════════════════"
            echo ""
            echo "Step 1: Request appointment with unavailable time"
            echo "  You: 'Herrenhaarschnitt für morgen 14 Uhr, Max Mustermann'"
            echo ""
            echo "Step 2: Agent offers alternatives"
            echo "  Agent: 'Leider ist 14:00 nicht verfügbar.'"
            echo "  Agent: 'Alternativen: 06:55, 14:30, 16:00'"
            echo ""
            echo "Step 3: Select alternative (KEY TEST POINT)"
            echo "  You: 'Um 06:55'"
            echo ""
            echo "Expected Flow:"
            echo "  1. Extract: selected_alternative_time = '06:55'"
            echo "  2. Agent: 'Perfekt! Einen Moment, ich prüfe...'"
            echo "  3. Check availability with uhrzeit = '06:55'"
            echo "  4. Show result for 06:55"
            echo "  5. Book with 06:55 (if confirmed)"
            echo ""
            echo "Verify in logs:"
            echo "  grep 'selected_alternative_time' storage/logs/laravel.log"
            echo ""
            ;;
        q|Q)
            echo "Goodbye!"
            exit 0
            ;;
        *)
            echo "❌ Invalid choice"
            ;;
    esac

    echo ""
    read -p "Press Enter to continue..."
    clear
done
