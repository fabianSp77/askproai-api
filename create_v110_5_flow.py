#!/usr/bin/env python3
import json
import sys

# Read V110.4 flow
with open('/var/www/api-gateway/conversation_flow_v110_4_fixed.json', 'r') as f:
    flow = json.load(f)

print("🔧 Creating V110.5 with critical fixes...")

# Update metadata
flow['conversation_flow_id'] = 'friseur1_optimal_v110_5'
flow['version'] = 115

# FIX 1: Change "service" to "service_name" in func_start_booking parameter_mapping
for node in flow['nodes']:
    if node.get('id') == 'func_start_booking':
        param_mapping = node.get('parameter_mapping', {})
        if 'service' in param_mapping:
            print("✅ FIX 1: Changing 'service' → 'service_name' in func_start_booking")
            param_mapping['service_name'] = param_mapping.pop('service')
            print(f"   New mapping: service_name = {param_mapping['service_name']}")

    # FIX 2: Remove function_name from func_confirm_booking parameter_mapping
    if node.get('id') == 'func_confirm_booking':
        param_mapping = node.get('parameter_mapping', {})
        if 'function_name' in param_mapping:
            print("✅ FIX 2: Removing 'function_name' from func_confirm_booking parameter_mapping")
            del param_mapping['function_name']

# FIX 3: Remove function_name from tool definitions parameters.properties
for tool in flow.get('tools', []):
    if tool.get('tool_id') == 'tool-start-booking':
        params = tool.get('parameters', {}).get('properties', {})
        if 'function_name' in params:
            print("✅ FIX 3: Removing 'function_name' from tool-start-booking parameters schema")
            del params['function_name']
            
        # Also update required fields
        required = tool.get('parameters', {}).get('required', [])
        if 'function_name' in required:
            print("   Removing 'function_name' from required fields")
            tool['parameters']['required'] = [f for f in required if f != 'function_name']
        
        # Update service parameter name in schema
        if 'service' in params and 'service_name' not in params:
            print("✅ FIX 4: Renaming 'service' → 'service_name' in tool-start-booking parameters schema")
            params['service_name'] = params.pop('service')
        
        # Update required fields - change service to service_name
        required = tool.get('parameters', {}).get('required', [])
        if 'service' in required:
            print("   Renaming 'service' → 'service_name' in required fields")
            tool['parameters']['required'] = ['service_name' if f == 'service' else f for f in required]

    if tool.get('tool_id') == 'tool-confirm-booking':
        params = tool.get('parameters', {}).get('properties', {})
        if 'function_name' in params:
            print("✅ FIX 5: Removing 'function_name' from tool-confirm-booking parameters schema")
            del params['function_name']
            
        required = tool.get('parameters', {}).get('required', [])
        if 'function_name' in required:
            print("   Removing 'function_name' from required fields")
            tool['parameters']['required'] = [f for f in required if f != 'function_name']

# Save V110.5 flow
output_file = '/var/www/api-gateway/conversation_flow_v110_5_fixed.json'
with open(output_file, 'w') as f:
    json.dump(flow, f, indent=2, ensure_ascii=False)

print(f"\n✅ V110.5 flow saved to: {output_file}")
print("\n📊 Summary of fixes:")
print("   1. func_start_booking parameter_mapping: service → service_name")
print("   2. func_confirm_booking parameter_mapping: function_name removed")
print("   3. tool-start-booking parameters schema: function_name removed")
print("   4. tool-start-booking parameters schema: service → service_name")
print("   5. tool-confirm-booking parameters schema: function_name removed")
