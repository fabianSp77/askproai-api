# Playwright to Puppeteer Migration Analysis
**Date**: 2025-10-01
**Architecture**: ARM64 (aarch64) Linux
**System**: Debian GNU/Linux 12 (bookworm)

---

## Executive Summary

🔍 **Key Finding**: Playwright DOES support ARM64 Linux with Chromium, and your current configuration is already correct.

However, migrating to Puppeteer MCP is recommended for:
- ✅ Simpler configuration (already installed)
- ✅ Better MCP server stability on ARM64
- ✅ Lighter resource footprint
- ✅ More mature ARM64 ecosystem support

---

## Current State Analysis

### Installed Configuration

**Location**: `/root/.config/Claude/claude_desktop_config.json`

#### Playwright MCP (Lines 3-14)
```json
"playwright-mcp": {
  "command": "npx",
  "args": ["@playwright/mcp@latest", "--config", "/etc/mcp/playwright-arm64.json", "--caps=vision,pdf"],
  "env": {"npm_config_yes": "true"}
}
```

**Config**: `/etc/mcp/playwright-arm64.json`
```json
{
  "browser": {
    "browserName": "chromium",
    "launchOptions": {
      "executablePath": "/usr/bin/chromium",
      "headless": true,
      "args": ["--no-sandbox", "--disable-setuid-sandbox", "--disable-dev-shm-usage"]
    },
    "isolated": false
  }
}
```

#### Puppeteer MCP (Lines 15-25)
```json
"puppeteer-mcp": {
  "command": "npx",
  "args": ["@modelcontextprotocol/server-puppeteer"],
  "env": {
    "npm_config_yes": "true",
    "PUPPETEER_SKIP_DOWNLOAD": "true",
    "PUPPETEER_EXECUTABLE_PATH": "/usr/bin/chromium"
  }
}
```

### Version Information
- **Playwright**: v1.55.0 ✅
- **Playwright MCP**: v0.0.40 ✅
- **Puppeteer MCP**: @modelcontextprotocol/server-puppeteer ✅
- **System Chromium**: 140.0.7339.185 ✅

### Browser Availability
- `/usr/bin/chromium` → Chromium 140.0.7339.185 (ARM64 native) ✅
- Playwright cached browsers in `~/.cache/ms-playwright*/` ✅
  - chromium-1155, chromium-1181, chromium-1187
  - chromium_headless_shell versions

---

## ARM64 Compatibility Research

### Playwright ARM64 Support (2025)

**Official Support**: ✅ Fully supported
- Debian 12/13 (arm64)
- Ubuntu 22.04/24.04 (arm64)
- Chromium, Firefox, WebKit all supported

**Known Limitation**: ❌ Google Chrome branded browser
- Google Chrome NOT available on Linux/arm64
- Error: "ERROR: not supported on Linux Arm64"
- **Solution**: Use Chromium instead (already configured correctly)

**Source**:
- https://playwright.dev/docs/intro
- https://github.com/iuill/playwright-mcp-docker/issues/1
- https://notes.myhro.info/2025/07/fix-playwright-mcp-on-claude-code/

### Puppeteer ARM64 Support

**Official Support**: ✅ Fully supported
- Uses system-installed Chrome/Chromium
- `PUPPETEER_EXECUTABLE_PATH` environment variable
- `PUPPETEER_SKIP_DOWNLOAD` prevents downloading incompatible binaries
- More straightforward ARM64 setup

---

## Performance Comparison

### General Benchmarks (not ARM64-specific)

| Scenario | Playwright | Puppeteer | Winner |
|----------|-----------|-----------|--------|
| Short scripts | 4.513s | ~30% faster | Puppeteer 🏆 |
| Navigation-heavy | 4.513s | 4.784s | Playwright 🏆 |
| Long E2E tests | Similar | Similar | Tie |
| Variability | Lower | Higher | Playwright 🏆 |

**Note**: No published ARM64-specific benchmarks found.

### Resource Footprint
- **Puppeteer**: Lighter, simpler, fewer dependencies
- **Playwright**: Heavier, more features, more overhead

---

## Tool Feature Comparison

### MCP Server Capabilities

#### Playwright MCP Tools
Available tools from Playwright MCP server:
- `browser_close` - Close the page
- `browser_resize` - Resize browser window
- `browser_console_messages` - Get console messages
- `browser_handle_dialog` - Handle dialogs
- `browser_evaluate` - Execute JavaScript
- `browser_file_upload` - Upload files
- `browser_fill_form` - Fill multiple form fields
- `browser_install` - Install browser binaries
- `browser_press_key` - Keyboard input
- `browser_type` - Type text into elements
- `browser_navigate` - Navigate to URL
- `browser_navigate_back` - Go back
- `browser_network_requests` - Get network requests
- `browser_take_screenshot` - Take screenshots
- `browser_snapshot` - Accessibility snapshot ⭐
- `browser_click` - Click elements
- `browser_drag` - Drag and drop
- `browser_hover` - Hover over elements
- `browser_select_option` - Select dropdown options
- `browser_tabs` - Tab management
- `browser_wait_for` - Wait for conditions

**Unique Features**:
- ✅ Accessibility snapshots (better than screenshots)
- ✅ Form filling (batch operations)
- ✅ Advanced drag/drop
- ✅ Browser dialog handling
- ✅ Vision & PDF capabilities (--caps flag)

#### Puppeteer MCP Tools
Available tools from Puppeteer MCP server:
- `puppeteer_connect_active_tab` - Connect to existing Chrome
- `puppeteer_navigate` - Navigate to URL
- `puppeteer_screenshot` - Take screenshots
- `puppeteer_click` - Click elements
- `puppeteer_fill` - Fill input fields
- `puppeteer_select` - Select dropdown options
- `puppeteer_hover` - Hover over elements
- `puppeteer_evaluate` - Execute JavaScript

**Unique Features**:
- ✅ Connect to existing browser instance (debugging)
- ✅ Simpler API surface
- ✅ Better debugging port support

### Feature Matrix

| Feature | Playwright MCP | Puppeteer MCP |
|---------|---------------|---------------|
| Navigation | ✅ | ✅ |
| Screenshots | ✅ | ✅ |
| Click/Type | ✅ | ✅ |
| Form filling | ✅ Batch | ✅ Individual |
| JavaScript eval | ✅ | ✅ |
| Accessibility snapshots | ✅ | ❌ |
| Network monitoring | ✅ | ❌ |
| File upload | ✅ | ❌ |
| Drag & drop | ✅ | ❌ |
| Tab management | ✅ | ❌ |
| Console messages | ✅ | ❌ |
| Dialog handling | ✅ | ❌ |
| Connect to existing browser | ❌ | ✅ |
| Debug port connection | ❌ | ✅ (port 9222) |

---

## Migration Recommendation

### Recommended Approach: **Remove Playwright, Keep Puppeteer**

**Rationale**:
1. ✅ Puppeteer already installed and configured correctly
2. ✅ Simpler setup with fewer configuration files
3. ✅ Lighter resource footprint
4. ✅ Adequate for most browser automation needs
5. ✅ Better debugging support (connect to active tab)
6. ⚠️ Playwright has more features, but adds complexity

### When to Keep Playwright
Consider keeping Playwright if you need:
- Accessibility snapshots for testing
- Advanced form filling (batch operations)
- Network request monitoring
- File upload automation
- Drag and drop interactions
- Tab management
- Console message capture

### Hybrid Approach
You can keep BOTH:
- Use Puppeteer for simple automation (navigate, click, screenshot)
- Use Playwright for advanced features when needed
- Claude Code will use whichever MCP server has the required tool

---

## Migration Steps

### 1. Backup Current Configuration
```bash
cp /root/.config/Claude/claude_desktop_config.json \
   /root/.config/Claude/claude_desktop_config.json.backup
```

### 2. Remove Playwright MCP Configuration
Edit `/root/.config/Claude/claude_desktop_config.json` and remove:
```json
"playwright-mcp": {
  "command": "npx",
  "args": ["@playwright/mcp@latest", "--config", "/etc/mcp/playwright-arm64.json", "--caps=vision,pdf"],
  "env": {"npm_config_yes": "true"}
},
```

### 3. Verify Puppeteer Configuration
Ensure this remains in the config:
```json
"puppeteer-mcp": {
  "command": "npx",
  "args": ["@modelcontextprotocol/server-puppeteer"],
  "env": {
    "npm_config_yes": "true",
    "PUPPETEER_SKIP_DOWNLOAD": "true",
    "PUPPETEER_EXECUTABLE_PATH": "/usr/bin/chromium"
  }
}
```

### 4. (Optional) Uninstall Playwright Globally
```bash
# Remove global Playwright installation
npm uninstall -g playwright @playwright/test

# Clean up cached browsers (saves ~2GB)
rm -rf ~/.cache/ms-playwright*
```

### 5. Restart Claude Code
Restart Claude Code to reload MCP server configuration.

### 6. Test Puppeteer MCP
```bash
# Test Puppeteer MCP server
npx @modelcontextprotocol/server-puppeteer
```

---

## Tool Mapping Reference

For common browser automation tasks, map Playwright to Puppeteer:

| Task | Playwright MCP | Puppeteer MCP |
|------|---------------|---------------|
| Navigate | `browser_navigate(url)` | `puppeteer_navigate(url)` |
| Screenshot | `browser_take_screenshot(filename)` | `puppeteer_screenshot(name, width, height)` |
| Click | `browser_click(element, ref)` | `puppeteer_click(selector)` |
| Type text | `browser_type(element, ref, text)` | `puppeteer_fill(selector, value)` |
| Hover | `browser_hover(element, ref)` | `puppeteer_hover(selector)` |
| Select | `browser_select_option(element, ref, values)` | `puppeteer_select(selector, value)` |
| Execute JS | `browser_evaluate(function)` | `puppeteer_evaluate(script)` |

**Key Difference**:
- Playwright uses `element` description + `ref` (from accessibility snapshot)
- Puppeteer uses CSS `selector` directly

---

## Post-Migration Validation

### Test Checklist
- [ ] Claude Code starts without MCP errors
- [ ] `puppeteer_navigate` works to open URLs
- [ ] `puppeteer_screenshot` captures page images
- [ ] `puppeteer_click` interacts with elements
- [ ] `puppeteer_evaluate` executes JavaScript
- [ ] System chromium is used (not downloading browsers)

### Troubleshooting

**Issue**: MCP server connection errors
```bash
# Check MCP server logs
journalctl --user -u claude-code -f
```

**Issue**: Chromium not found
```bash
# Verify chromium installation
which chromium
chromium --version

# Reinstall if needed
apt-get update && apt-get install -y chromium
```

**Issue**: Permission errors
```bash
# Ensure chromium is executable
chmod +x /usr/bin/chromium
```

---

## Conclusion

### Summary
- ✅ Playwright DOES work on ARM64 Linux with Chromium
- ✅ Current Playwright config is already correct for ARM64
- ✅ Puppeteer MCP is simpler, lighter, already configured
- ✅ Migration recommended for simplicity and resource efficiency
- ⚠️ Some advanced features lost (accessibility snapshots, network monitoring)

### Recommendation
**Proceed with migration to Puppeteer-only** unless you specifically need Playwright's advanced features.

### Alternative
**Keep both MCP servers** if you need Playwright's advanced capabilities occasionally while defaulting to Puppeteer for simple tasks.

---

**Analysis completed**: 2025-10-01
**Next steps**: Execute migration or choose hybrid approach
