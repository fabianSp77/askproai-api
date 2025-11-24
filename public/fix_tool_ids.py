#!/usr/bin/env python3
"""
Retell Flow Tool ID Fixer
Korrigiert alle tool_id Felder automatisch

Usage:
    python3 fix_tool_ids.py flow_export.json

Output:
    flow_export_FIXED.json
"""

import json
import sys
from pathlib import Path

# Die 10 Korrekturen (ALLE Tools!)
TOOL_ID_FIXES = {
    "tool-get-current-context": "get_current_context",
    "tool-check-customer": "check_customer",
    "tool-check-availability": "check_availability_v17",  # WICHTIG: v17!
    "tool-get-alternatives": "get_alternatives",
    "tool-start-booking": "start_booking",
    "tool-get-appointments": "query_appointment",  # WICHTIG: query_appointment!
    "tool-cancel-appointment": "cancel_appointment",
    "tool-get-services": "get_available_services",
    "tool-reschedule-appointment": "reschedule_appointment",
    "tool-request-callback": "request_callback",
}

def fix_tool_ids(flow_data):
    """Korrigiert alle tool_id Felder im Flow"""
    corrections_made = []

    # Fix im tools Array
    if "tools" in flow_data:
        for tool in flow_data["tools"]:
            if "tool_id" in tool:
                old_id = tool["tool_id"]
                if old_id in TOOL_ID_FIXES:
                    new_id = TOOL_ID_FIXES[old_id]
                    tool["tool_id"] = new_id
                    corrections_made.append(f"✅ tools[]: {old_id} → {new_id}")

    # Fix in nodes Array (function nodes)
    if "nodes" in flow_data:
        for node in flow_data["nodes"]:
            if node.get("type") == "function" and "tool_id" in node:
                old_id = node["tool_id"]
                if old_id in TOOL_ID_FIXES:
                    new_id = TOOL_ID_FIXES[old_id]
                    node["tool_id"] = new_id
                    node_name = node.get("name", "unknown")
                    corrections_made.append(f"✅ nodes[{node_name}]: {old_id} → {new_id}")

    return flow_data, corrections_made

def main():
    if len(sys.argv) < 2:
        print("❌ Fehler: Keine Datei angegeben!")
        print(f"Usage: python3 {sys.argv[0]} flow_export.json")
        sys.exit(1)

    input_file = Path(sys.argv[1])

    if not input_file.exists():
        print(f"❌ Fehler: Datei nicht gefunden: {input_file}")
        sys.exit(1)

    print(f"📖 Lese Datei: {input_file}")

    try:
        with open(input_file, 'r', encoding='utf-8') as f:
            flow_data = json.load(f)
    except json.JSONDecodeError as e:
        print(f"❌ JSON Parse Fehler: {e}")
        sys.exit(1)

    print("🔧 Korrigiere tool_id Felder...")
    fixed_data, corrections = fix_tool_ids(flow_data)

    if not corrections:
        print("ℹ️  Keine Korrekturen nötig - alle tool_ids bereits korrekt!")
        sys.exit(0)

    # Ausgabedatei
    output_file = input_file.parent / f"{input_file.stem}_FIXED.json"

    print(f"\n📝 Korrekturen:")
    for correction in corrections:
        print(f"   {correction}")

    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(fixed_data, f, indent=2, ensure_ascii=False)

    print(f"\n✅ Fertig! Korrigierte Datei gespeichert:")
    print(f"   {output_file}")
    print(f"\n🎯 Nächster Schritt:")
    print(f"   1. Im Dashboard: 'Import Flow' oder 'Upload JSON'")
    print(f"   2. Wähle: {output_file.name}")
    print(f"   3. Save & Publish")

if __name__ == "__main__":
    main()
