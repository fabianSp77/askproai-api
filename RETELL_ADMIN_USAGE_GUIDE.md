# Retell Agent Admin Usage Guide

**For**: Filament Administrators
**Date**: 2025-10-21
**Status**: ✅ LIVE

---

## Quick Start (2 minutes)

### 1. Login to Filament Admin
- Navigate to `/admin` on your AskPro instance
- Login with your admin credentials

### 2. Open Any Branch
- Go to **Branches** section (left sidebar)
- Click on any branch to edit

### 3. Click "Retell Agent" Tab
- Scroll through the tabs at the top
- Look for the microphone icon 🎤
- Click on **"Retell Agent"** tab

### 4. Deploy a Template
- Click the dropdown "Select template..."
- Choose one of 3 templates:
  - 🎯 **Dynamic Service Selection** - Full booking workflow
  - 📚 **Basic Appointment Booking** - Simplified booking
  - ℹ️ **Information Only** - Info retrieval only
- Click blue button **"Aus Template deployen"**
- Wait for success notification ✅

### 5. You're Done!
- New agent configuration is live for this branch
- Previous configuration auto-deactivated
- You can always rollback if needed

---

## Understanding Templates

### 🎯 Dynamic Service Selection (Recommended)

**Best For**: Full appointment booking workflow

**What It Does**:
- Greets customer and asks for service
- Lists available services with prices
- Collects appointment details
- Books appointment with confirmation
- Can cancel and reschedule existing appointments

**Functions Available**:
1. `list_services` - Get all available services
2. `collect_appointment_data` - Book new appointment
3. `cancel_appointment` - Cancel existing appointment
4. `reschedule_appointment` - Change appointment time

**Language**: German (Deutsch)

**Example Workflow**:
```
Agent: "Willkommen! Welchen Service brauchen Sie?"
Customer: "Ich möchte einen Haarschnitt"
Agent: "Perfekt! Wir haben folgende Services..."
[Shows list of services with prices and duration]
```

---

### 📚 Basic Appointment Booking

**Best For**: Simple booking without service listing

**What It Does**:
- Greets customer
- Asks for appointment details directly
- Books appointment
- Provides confirmation

**Functions Available**: Same 4 functions as Dynamic

**Language**: German (Deutsch)

**Use When**:
- You only offer one service
- Customers always know what they want
- You want a simpler workflow

---

### ℹ️ Information Only

**Best For**: General information calls

**What It Does**:
- Answers questions about the business
- Provides opening hours
- Gives general information
- Does NOT book appointments

**Functions Available**:
1. `get_opening_hours` - Return opening hours

**Language**: German (Deutsch)

**Use When**:
- You want info-only calls (no booking)
- You're testing or in maintenance mode
- You need to disable booking temporarily

---

## Step-by-Step: Deploy Template

### Step 1: Navigate to Branch

```
1. Login to Filament admin
2. Click "Branches" in left sidebar
3. Click on any branch name to edit
```

**What You'll See**:
- Branch edit form opens
- Several tabs at the top

---

### Step 2: Click "Retell Agent" Tab

```
Look for tabs across the top:
┌─────────────────────────────────────┐
│ Admin │ Details │ Settings │ Policies │ Retell Agent 🎤 │
└─────────────────────────────────────┘
```

Click on **"Retell Agent 🎤"** tab

---

### Step 3: Review Current Status

**You'll See One of Three States**:

#### State 1: No Branch Selected
```
⚠️ Branch nicht gespeichert
Bitte speichern Sie den Branch zuerst
um die Retell Agent Konfiguration
zu verwalten.
```
**Solution**: Go back and save the branch first

#### State 2: No Configuration Yet
```
📱 Keine Konfiguration
Noch keine Retell Agent Konfiguration
für diesen Branch erstellt.

Wählen Sie eine Template, um zu starten:
[Dropdown: Select template...]
[Blue Button: Aus Template deployen]
```

#### State 3: Active Configuration
```
✅ AKTIV
Dynamic Service Selection (v1)
Bereitgestellt: 2025-10-21 14:30:00

Prompt-Vorschau:
[Shows first 100 chars of prompt]

Funktionen:
• list_services
• collect_appointment_data
• cancel_appointment
• reschedule_appointment

[Dropdown: Select template...]
[Button: Prompt bearbeiten]
[Button: Aus Template deployen]
[Version History Link]
```

---

### Step 4: Select Template

**If State 1 or 2**:

1. Click dropdown "Select template..."
2. Choose one:
   - 🎯 Dynamic Service Selection
   - 📚 Basic Appointment Booking
   - ℹ️ Information Only
3. Selection appears in dropdown

---

### Step 5: Deploy Template

1. Click blue button **"Aus Template deployen"**
2. Wait a few seconds...
3. You'll see one of:

**✅ Success**:
```
✅ Template erfolgreich bereitgestellt!
Neue Version erstellt: v2
Alte Version wurde deaktiviert.
```

**❌ Error**:
```
❌ Bereitstellung fehlgeschlagen
Fehler: [Error description]

Lösungen:
1. Aktualisieren Sie die Seite
2. Versuchen Sie es erneut
3. Kontaktieren Sie den Support
```

---

### Step 6: Verify Deployment

**After Success**:
1. Page refreshes
2. Shows new configuration
3. Shows deployment timestamp
4. Shows prompt preview
5. Shows functions list

**Test It**:
- Make a test call to the agent
- Agent should respond with new configuration
- All functions should work

---

## Version History: Rollback to Previous

### What is Version History?

Every time you deploy a new template, a **version** is created:
- v1, v2, v3, etc.
- Each version stores complete configuration
- Only ONE version is active at a time

### View History

1. In Retell Agent tab, look for: **"Version History"** link
2. Click it
3. You'll see all versions:

```
Version History for this Branch
└─ v3 (ACTIVE) - Dynamic Selection - 2025-10-21 14:30
   Status: Active
   Deployed by: Admin Name
   └─ [Switch to this version]

└─ v2 - Basic Booking - 2025-10-21 13:15
   Status: Inactive
   Deployed by: Admin Name
   └─ [Switch to this version]

└─ v1 - Information Only - 2025-10-21 09:00
   Status: Inactive
   Deployed by: System
   └─ [Switch to this version]
```

### Rollback to Previous Version

**To Go Back**:

1. Find the version you want in history
2. Click **"[Switch to this version]"** button
3. Confirm dialog appears:
   ```
   Zurück zu v2 wechseln?
   Current: Dynamic Selection
   Target: Basic Booking

   [Cancel] [Confirm]
   ```
4. Click **"Confirm"**
5. Done! Old version is now active

**That's It!**
- Previous version instantly active
- Current version becomes inactive
- No service interruption
- All changes recorded

---

## Common Tasks

### Task 1: Change from One Template to Another

1. Click "Retell Agent" tab
2. Click dropdown "Select template..."
3. Choose NEW template
4. Click "Aus Template deployen"
5. Old template auto-deactivated
6. New template now active ✅

**Time**: ~10 seconds

---

### Task 2: Quickly Revert to Previous

1. Click "Retell Agent" tab
2. Look for **"Version History"** link
3. Find previous version
4. Click **"[Switch to this version]"**
5. Confirm dialog
6. Done ✅

**Time**: ~5 seconds

---

### Task 3: View What Template is Currently Active

1. Click "Retell Agent" tab
2. Look at current status section
3. Shows:
   - Template name
   - Version number
   - Deployment date/time
   - Functions available
   - Prompt preview

**Time**: ~2 seconds

---

### Task 4: Check Deployment History

1. Click "Retell Agent" tab
2. Scroll down or look for **"Version History"**
3. View all versions and when deployed
4. Shows who deployed each version

**Information Available**:
- Version number
- Template name
- Deployment date/time
- Deployed by (username)
- Status (active/inactive)

---

## Understanding What Each Part Does

### Template Dropdown

```
┌─────────────────────────────────────┐
│ Select template...        ▼          │
└─────────────────────────────────────┘
```

- **Shows**: All available templates
- **Choose**: Which template to deploy
- **Options**: 3 pre-built templates

---

### Deployment Button

```
┌──────────────────────────┐
│ Aus Template deployen    │
└──────────────────────────┘
```

- **Action**: Creates new version and activates it
- **Speed**: ~2-5 seconds
- **Result**: Old version deactivated, new version active

---

### Prompt Preview

```
📝 Prompt-Vorschau:
Du bist ein hilfreicher Buchungsassistent...
[Shows first 200 characters]
```

- **Shows**: First part of the prompt
- **Purpose**: Quick verification that template loaded
- **Full Text**: Available in edit mode (advanced users)

---

### Functions List

```
🔧 Funktionen:
• list_services
• collect_appointment_data
• cancel_appointment
• reschedule_appointment
```

- **Shows**: What the agent can do
- **Purpose**: Verify correct functions loaded
- **Count**: Should match template (typically 4 or 1)

---

## Troubleshooting

### Issue: "Retell Agent" Tab Not Visible

**Cause**: You don't have admin role

**Solution**:
```
1. Check your user account permissions
2. Ask administrator to grant admin role
3. Logout and login again
```

---

### Issue: Can't Select Template

**Cause**: Branch not saved yet

**Solution**:
1. Click "Save" button on the branch form
2. Wait for success message
3. Then try template selection

---

### Issue: Deploy Button Not Working

**Cause 1**: Network issue
**Solution**: Refresh page and try again

**Cause 2**: Invalid template selection
**Solution**: Make sure you selected a template first

**Cause 3**: Server error
**Solution**: Check logs with IT team

---

### Issue: Can't See Version History

**Cause**: No previous versions exist

**Solution**: Deploy first template to create v1
(Only after v2 is deployed will you have history)

---

### Issue: Deployment Shows Error

**Error Example**:
```
❌ Bereitstellung fehlgeschlagen
Error: Prompt-Validierung fehlgeschlagen
```

**Solutions**:
1. **Refresh Page**: Sometimes transient issue
2. **Try Again**: Network hiccups
3. **Different Template**: Try a different template
4. **Contact Support**: If persists

---

### Issue: Agent Still Using Old Configuration

**Cause**: Cache not cleared yet

**Solution**:
1. The agent caches configuration
2. Wait 1-2 minutes for automatic cache refresh
3. Or: Make test call to refresh cache manually
4. Agent should then use new configuration

---

## Best Practices

### ✅ DO:

- ✅ Test new template before deploying
- ✅ Verify agent behavior after deployment
- ✅ Keep version history clean (old versions)
- ✅ Document what you changed and why
- ✅ Use rollback if issues occur

### ❌ DON'T:

- ❌ Deploy during peak call hours
- ❌ Deploy without testing first
- ❌ Make frequent changes quickly
- ❌ Delete version history manually
- ❌ Use on production without staging test

---

## Support & Help

### Getting Help

| Issue | Contact | Time |
|-------|---------|------|
| **Can't login** | IT Support | 1 hour |
| **Tab not visible** | Admin/IT | 1 hour |
| **Deploy button broken** | Dev Team | 2 hours |
| **Agent not updated** | Dev Team | 2 hours |
| **Version history missing** | Dev Team | 2 hours |

### Emergency Contacts

- **On-Call Admin**: [Contact info]
- **Developer Support**: [Contact info]
- **Technical Support**: [Contact info]

---

## FAQ

### Q: What happens to the old template?
**A**: It's saved in version history and marked as inactive. You can restore it anytime.

### Q: Can I edit the prompt text?
**A**: In advanced mode, yes. For templates, use the "Prompt bearbeiten" button.

### Q: How long does deployment take?
**A**: Usually 2-5 seconds. Give it 10 seconds before refreshing.

### Q: Can multiple branches have different templates?
**A**: Yes! Each branch has independent configuration. Perfect for testing.

### Q: What if I break something?
**A**: Just use Version History to rollback to previous version (takes 5 seconds).

### Q: Can I create custom templates?
**A**: Currently 3 pre-built templates available. Contact dev team for custom ones.

### Q: Is there a test mode?
**A**: Use "Information Only" template for testing without enabling booking.

### Q: How many times can I rollback?
**A**: Unlimited! You can switch between any versions.

---

## Advanced Usage

### For Power Users:

If you see these options, you can:

1. **"Prompt bearbeiten"** - Edit prompt text directly
2. **"View Details"** - See full configuration JSON
3. **"Compare Versions"** - See diff between versions

**Warning**: Advanced features. Use with caution.

---

## Next Steps

1. ✅ Read this guide (you're here!)
2. ✅ Login to Filament admin
3. ✅ Navigate to a branch
4. ✅ Click "Retell Agent" tab
5. ✅ Deploy test template
6. ✅ Verify agent behavior
7. ✅ Train your team

---

## Summary

You can now:
- ✅ Deploy templates with one click
- ✅ View version history
- ✅ Rollback in seconds if needed
- ✅ Manage agent configuration per branch
- ✅ Track all changes and who made them

**Questions?** See Troubleshooting or contact support.

---

**Guide Version**: 1.0
**Last Updated**: 2025-10-21
**Status**: ✅ READY
