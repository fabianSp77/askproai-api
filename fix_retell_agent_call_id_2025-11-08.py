#!/usr/bin/env python3
"""
FIX: Add parameter_mapping for call_id to start_booking and confirm_booking tools

ROOT CAUSE: Tools have parameter_mapping=null, causing LLM to guess call_id
SOLUTION: Add {"call_id": "{{call_id}}"} mapping to both tools

Date: 2025-11-08
"""

import json
import sys
from pathlib import Path

def fix_agent_config(input_file, output_file):
    """Fix parameter_mapping for booking tools"""

    print(f"\n{'='*60}")
    print(f"  FIXING RETELL AGENT CONFIG")
    print(f"{'='*60}\n")

    # Load config
    print(f"📖 Reading: {input_file}")
    with open(input_file, 'r', encoding='utf-8') as f:
        config = json.load(f)

    # Track changes
    changes_made = 0

    # Find and fix tools
    if 'conversationFlow' in config and 'tools' in config['conversationFlow']:
        tools = config['conversationFlow']['tools']

        for i, tool in enumerate(tools):
            tool_name = tool.get('name', '')

            if tool_name in ['start_booking', 'confirm_booking']:
                print(f"\n🔍 Found tool: {tool_name}")
                print(f"   Index: {i}")

                # Check current parameter_mapping
                current_mapping = tool.get('parameter_mapping')
                print(f"   Current mapping: {current_mapping}")

                # Add/fix parameter_mapping
                if current_mapping is None or 'call_id' not in (current_mapping or {}):
                    new_mapping = current_mapping or {}
                    new_mapping['call_id'] = '{{call_id}}'

                    tool['parameter_mapping'] = new_mapping
                    changes_made += 1

                    print(f"   ✅ FIXED: Added call_id mapping")
                    print(f"   New mapping: {new_mapping}")
                else:
                    print(f"   ℹ️  Already has call_id mapping")

    # Summary
    print(f"\n{'='*60}")
    print(f"  SUMMARY")
    print(f"{'='*60}\n")
    print(f"Changes made: {changes_made}")

    if changes_made > 0:
        # Save fixed config
        print(f"\n💾 Writing fixed config to: {output_file}")
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(config, f, indent=2, ensure_ascii=False)

        print(f"✅ Config saved successfully!")
        return True
    else:
        print(f"ℹ️  No changes needed - config already correct")
        return False

if __name__ == '__main__':
    input_file = '/var/www/api-gateway/retell_agent_v51_complete_fixed.json'
    output_file = '/var/www/api-gateway/retell_agent_v51_call_id_fixed_2025-11-08.json'

    try:
        fixed = fix_agent_config(input_file, output_file)

        if fixed:
            print(f"\n📋 NEXT STEPS:")
            print(f"   1. Review: {output_file}")
            print(f"   2. Upload to Retell AI Dashboard")
            print(f"   3. Test with new call")
            print(f"   4. Verify cache keys match")

        sys.exit(0 if fixed else 0)

    except Exception as e:
        print(f"\n❌ ERROR: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
