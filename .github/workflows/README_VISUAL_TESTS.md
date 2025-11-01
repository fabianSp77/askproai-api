# Visual Testing with Selenium + Firefox

**Why Firefox/GeckoDriver instead of Puppeteer/Playwright-Chromium?**

---

## Problem: ARM64 Compatibility

Our GitHub Actions runners use `ubuntu-latest` which runs on **ARM64 architecture**. Chromium-based tools have known issues on ARM64:

### ❌ Puppeteer Issues on ARM64
- Chromium binaries often not available for ARM64
- Requires manual compilation or unofficial builds
- Inconsistent behavior across environments
- Higher maintenance overhead

### ❌ Playwright Chromium Issues on ARM64
- While Playwright supports ARM64 theoretically, Chromium engine has compatibility gaps
- Requires additional dependencies and workarounds
- Flaky tests on ARM64 runners

---

## Solution: Selenium + Firefox (GeckoDriver)

### ✅ Why Firefox/GeckoDriver?

1. **Native ARM64 Support**
   - Firefox ESR has official ARM64 builds
   - GeckoDriver releases include `linux64` builds that work on ARM64
   - No compilation or workarounds needed

2. **Stability**
   - Firefox ESR (Extended Support Release) for production stability
   - Mature GeckoDriver implementation
   - Consistent behavior across architectures

3. **Compliance**
   - Tests real browser behavior (not a headless-only engine)
   - Standards-compliant rendering
   - Supports modern web features

4. **Maintenance**
   - Simple installation via apt + wget
   - Clear versioning via GitHub releases
   - Minimal dependencies

---

## Installation Process

### In CI/CD (`.github/workflows/visual-staging.yml`)

```yaml
- name: Install Selenium + GeckoDriver
  run: |
    # Install Firefox ESR
    sudo apt-get update
    sudo apt-get install -y firefox-esr

    # Download latest GeckoDriver
    GECKO_VERSION=$(curl -s https://api.github.com/repos/mozilla/geckodriver/releases/latest | grep tag_name | cut -d '"' -f 4)
    wget -q "https://github.com/mozilla/geckodriver/releases/download/${GECKO_VERSION}/geckodriver-${GECKO_VERSION}-linux64.tar.gz"
    tar -xzf geckodriver-*.tar.gz
    chmod +x geckodriver
    sudo mv geckodriver /usr/local/bin/

    # Verify
    firefox --version
    geckodriver --version
```

### Configuration

```python
from selenium import webdriver
from selenium.webdriver.firefox.options import Options

options = Options()
options.add_argument('--headless')
options.add_argument('--no-sandbox')
options.add_argument('--disable-dev-shm-usage')
options.add_argument('--window-size=1920,1080')

driver = webdriver.Firefox(options=options)
```

---

## Alternatives Considered

| Tool | ARM64 Support | Verdict |
|------|---------------|---------|
| **Selenium + Firefox** | ✅ Native | ✅ **Chosen** |
| Puppeteer (Chromium) | ⚠️ Limited | ❌ Rejected |
| Playwright (Chromium) | ⚠️ Experimental | ❌ Rejected |
| Playwright (Firefox) | ✅ Good | ⚡ Future option |
| Chrome + ChromeDriver | ❌ Poor | ❌ Rejected |

---

## Future Considerations

### Playwright with Firefox Engine

Playwright supports Firefox engine which also works well on ARM64. If we need more advanced features (like network interception, video recording), we could migrate to:

```yaml
- name: Install Playwright
  run: |
    pip install playwright
    playwright install firefox
    playwright install-deps
```

However, for our current use case (screenshot-based visual regression), **Selenium + Firefox is simpler and sufficient**.

---

## Resources

- [GeckoDriver Releases](https://github.com/mozilla/geckodriver/releases)
- [Selenium Python Docs](https://selenium-python.readthedocs.io/)
- [Firefox ESR](https://www.mozilla.org/en-US/firefox/enterprise/)

---

**Last Updated**: 2025-10-30
**Maintained by**: DevOps Team
