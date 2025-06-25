# Retell Agent Version Display Explanation

## Current Behavior

The Retell Ultimate Control Center displays agent names and versions as follows:

### Original Agent Name (from database):
```
Online: Assistent für Fabian Spitzer Rechtliches/V33
```

### How it's displayed in the UI:

1. **Main Agent Name**: `Assistent für Fabian Spitzer Rechtliches`
   - "Online:" prefix is removed
   - Version suffix "/V33" is removed
   - Clean, readable name is displayed

2. **Version Badge**: `V33`
   - Displayed separately in a blue badge
   - Located in the top-right corner of the agent card
   - Clickable dropdown to switch between versions

## Why This Design?

1. **Cleaner UI**: Separating the version from the name makes the interface less cluttered
2. **Version Management**: The version dropdown allows easy switching between different versions
3. **Better Readability**: Long agent names are easier to read without prefixes and suffixes

## The Parsing Logic

```php
// Remove version and clean up name
protected function parseAgentName(string $fullName): string
{
    $name = preg_replace('/\/V\d+$/', '', $fullName);  // Remove /V33
    return trim(str_replace('Online: ', '', $name));   // Remove "Online: "
}

// Extract version number
protected function extractVersion(string $fullName): string
{
    if (preg_match('/\/V(\d+)$/', $fullName, $matches)) {
        return 'V' . $matches[1];  // Returns "V33"
    }
    return 'V1';  // Default if no version found
}
```

## If You Want to See the Full Name

If you prefer to see the complete original name including "Online:" and "/V33", we can:

1. **Option 1**: Add a tooltip showing the full name when hovering over the agent name
2. **Option 2**: Display the full name instead of the parsed version
3. **Option 3**: Add a setting to toggle between clean and full name display

## Current Display Example

```
┌─────────────────────────────────────────┐
│ [Active] [Synced]                  [V33]│ ← Version badge
│                                         │
│ Assistent für Fabian Spitzer            │ ← Clean name
│ Rechtliches                             │
│ Agent ID: agent_9a8...                  │
│                                         │
│ [Metrics and buttons...]                │
└─────────────────────────────────────────┘
```

The version IS being displayed - just in a separate, more elegant way!