# Filament 3.x Component Method Quick Reference
**Purpose:** Quick lookup for which Filament components support which methods
**Last Updated:** 2025-10-04

---

## Component Categories

### 🟢 Field Components (extend Field class)
**Support:** helperText(), hint(), hintIcon(), hintColor(), description(), label(), required(), disabled(), etc.

| Component | helperText() | hint() | Common Use Case |
|-----------|--------------|--------|-----------------|
| TextInput | ✅ | ✅ | Text input fields |
| Textarea | ✅ | ✅ | Multi-line text |
| Select | ✅ | ✅ | Dropdown selection |
| Radio | ✅ | ✅ | Radio button groups |
| Checkbox | ✅ | ✅ | Checkbox fields |
| Toggle | ✅ | ✅ | Boolean switches |
| DatePicker | ✅ | ✅ | Date selection |
| DateTimePicker | ✅ | ✅ | Date & time selection |
| TimePicker | ✅ | ✅ | Time selection |
| ColorPicker | ✅ | ✅ | Color selection |
| FileUpload | ✅ | ✅ | File uploads |
| RichEditor | ✅ | ✅ | WYSIWYG editor |
| MarkdownEditor | ✅ | ✅ | Markdown editor |
| KeyValue | ✅ | ✅ | Key-value pairs |
| TagsInput | ✅ | ✅ | Tag input |
| CheckboxList | ✅ | ✅ | Multiple checkboxes |
| Hidden | ✅ | ✅ | Hidden fields |
| Placeholder | ✅ | ✅ | Display-only text |

### 🔴 Composite Components (extend Component directly)
**Support:** label(), required(), BUT NOT helperText() or hint()

| Component | helperText() | Alternative | Description |
|-----------|--------------|-------------|-------------|
| MorphToSelect | ❌ | Section->description() | Morphable relationship selector |
| Repeater | ❌ | Section->description() | Repeatable field groups |
| Builder | ❌ | Section->description() | Dynamic block builder |

**Why no helperText()?** These components render multiple child fields internally and don't use the `HasHelperText` trait.

### 🟡 Container Components (layout/grouping)
**Support:** description() for some, but NOT helperText() or hint()

| Component | description() | helperText() | Use Case |
|-----------|---------------|--------------|----------|
| Section | ✅ | ❌ | Section with header/description |
| Fieldset | ✅ | ❌ | Fieldset grouping |
| Grid | ❌ | ❌ | Grid layout |
| Split | ❌ | ❌ | Split layout |
| Tabs | ✅ | ❌ | Tabbed interface |
| Wizard | ✅ | ❌ | Multi-step forms |

---

## Method Availability Reference

### helperText()
- **Trait:** `HasHelperText`
- **Available in:** `Field` class and `Placeholder`
- **Accepts:** `string | Htmlable | Closure | null`
- **Location:** Below the field input
- **Purpose:** Provide context-sensitive help text

**Example:**
```php
TextInput::make('name')
    ->helperText('Your full name, including middle names')
```

### hint()
- **Trait:** `HasHint`
- **Available in:** `Field` class
- **Accepts:** `string | Htmlable | Closure | null`
- **Location:** Top right of field label
- **Purpose:** Quick tips or additional info

**Example:**
```php
TextInput::make('email')
    ->hint('Must be unique')
    ->hintIcon('heroicon-o-information-circle')
    ->hintColor('info')
```

### description()
- **Method:** Available on Section, Fieldset, Tabs, Wizard
- **Accepts:** `string | Htmlable | Closure | null`
- **Location:** Below section/container header
- **Purpose:** Describe section purpose

**Example:**
```php
Section::make('User Details')
    ->description('Enter the user personal information')
    ->schema([...])
```

---

## Common Patterns

### ✅ Adding Help to MorphToSelect
```php
// WRONG - This will error
MorphToSelect::make('configurable')
    ->helperText('Select entity') // ❌ BadMethodCallException

// RIGHT - Use Section description
Section::make('Entity Selection')
    ->description('Select the entity for this configuration')
    ->schema([
        MorphToSelect::make('configurable')
            ->types([...])
    ])

// ALTERNATIVE - Use Placeholder
Section::make('Entity Selection')
    ->schema([
        MorphToSelect::make('configurable')
            ->types([...]),

        Placeholder::make('help')
            ->content('Select the entity type first, then choose the specific record')
    ])
```

### ✅ Adding Help to Repeater
```php
// WRONG
Repeater::make('items')
    ->helperText('Add items here') // ❌ BadMethodCallException

// RIGHT
Section::make('Items')
    ->description('Add and manage your items below')
    ->schema([
        Repeater::make('items')
            ->schema([...])
    ])
```

### ✅ Using hint() for Quick Info
```php
TextInput::make('slug')
    ->helperText('URL-friendly version of the title')
    ->hint('Auto-generated')
    ->hintIcon('heroicon-o-sparkles')
    ->hintColor('success')
```

---

## Debugging Tips

### Check if a component supports helperText()
```bash
# Find component class file
grep -r "class YourComponent" vendor/filament/forms/src/Components

# Check if it extends Field
grep "extends Field" vendor/filament/forms/src/Components/YourComponent.php

# Check if it uses HasHelperText trait
grep "use.*HasHelperText" vendor/filament/forms/src/Components/YourComponent.php
```

### Component extends Field?
- **YES** → helperText() will work ✅
- **NO** → Use Section->description() instead ❌

---

## Error Messages to Watch For

### BadMethodCallException: Method does not exist
```
BadMethodCallException: Method Filament\Forms\Components\MorphToSelect::helperText does not exist.
```
**Solution:** Remove helperText() and use Section->description() instead

### Call to undefined method
```
Call to undefined method Filament\Forms\Components\Repeater::hint()
```
**Solution:** Repeater doesn't support hint(), wrap in Section with description()

---

## Quick Decision Tree

```
Need to add help text to a form component?
│
├─ Is it a standard input field? (TextInput, Select, Toggle, etc.)
│  └─ YES → Use ->helperText('your text') ✅
│
├─ Is it MorphToSelect, Repeater, or Builder?
│  └─ YES → Wrap in Section with ->description('your text') ✅
│
├─ Is it a Section, Fieldset, or Tabs?
│  └─ YES → Use ->description('your text') ✅
│
└─ Is it Grid or Split?
   └─ YES → No built-in help text, add Placeholder component ✅
```

---

## Related Documentation

- [Filament Forms - Getting Started](https://filamentphp.com/docs/3.x/forms/fields/getting-started)
- [Filament Forms - Validation](https://filamentphp.com/docs/3.x/forms/validation)
- [Filament Component API](https://filamentphp.com/api/3.x/Filament/Forms/Components.html)

---

## Version Notes

**Filament 3.x Changes:**
- `helperText()` moved to `HasHelperText` trait (was on Field directly in v2)
- MorphToSelect never supported helperText() (same in v2 and v3)
- `description()` on sections added in v3 (cleaner than v2 approach)

**This guide applies to:**
- Filament v3.0+
- Laravel 10.x/11.x
