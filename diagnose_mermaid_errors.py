#!/usr/bin/env python3
"""
Diagnose which specific Mermaid diagrams are causing errors
"""

from playwright.sync_api import sync_playwright
import time

def diagnose_errors():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Track which diagram causes which error
        error_map = []

        def handle_console(msg):
            if msg.type == 'error' and 'translate' in msg.text:
                # Get stack trace if available
                error_map.append({
                    'text': msg.text,
                    'location': msg.location,
                    'timestamp': time.time()
                })

        page.on('console', handle_console)

        print("🌐 Loading page...")
        page.goto('https://api.askproai.de/docs/friseur1/agent-v50-interactive-complete.html?bust=' + str(int(time.time())))

        # Wait and let Mermaid process
        page.wait_for_load_state('networkidle')
        page.wait_for_timeout(5000)  # Give extra time

        print(f"\n📊 Total Console Errors: {len(error_map)}")

        # Group by location
        locations = {}
        for error in error_map:
            loc = f"Line {error['location']['lineNumber']}"
            if loc not in locations:
                locations[loc] = 0
            locations[loc] += 1

        print(f"\n📍 Errors by source location:")
        for loc, count in sorted(locations.items(), key=lambda x: x[1], reverse=True):
            print(f"  {loc}: {count} errors")

        # Count diagrams with Syntax error text
        syntax_errors = page.locator('text=/Syntax error/i').all()
        print(f"\n⚠️  Visible 'Syntax error' messages: {len(syntax_errors)}")

        # Check if Data Flow tab is even visible
        data_flow_tab = page.locator('[data-tab="data-flow"]')
        if data_flow_tab.count() > 0:
            print("\n🔍 Clicking Data Flow tab...")
            data_flow_tab.click()
            page.wait_for_timeout(2000)

            # Take screenshot of Data Flow section
            page.screenshot(path='/tmp/data_flow_diagnosis.png', full_page=False)
            print("📸 Screenshot saved: /tmp/data_flow_diagnosis.png")

            # Recount errors after tab switch
            syntax_errors_after = page.locator('text=/Syntax error/i').all()
            print(f"\n⚠️  Syntax errors after switching to Data Flow: {len(syntax_errors_after)}")

        # Try to identify which diagrams are problematic
        print("\n🔎 Analyzing diagram states...")
        diagram_info = page.evaluate("""
            () => {
                const diagrams = document.querySelectorAll('.mermaid, pre.mermaid');
                return Array.from(diagrams).map((el, idx) => {
                    const parent = el.closest('[id]');
                    const hasError = el.textContent.includes('Syntax error');
                    const hasSVG = el.querySelector('svg') !== null;
                    return {
                        index: idx,
                        parentId: parent ? parent.id : 'unknown',
                        hasError: hasError,
                        hasSVG: hasSVG,
                        visible: el.offsetParent !== null
                    };
                });
            }
        """)

        problematic = [d for d in diagram_info if d['hasError']]
        print(f"\n❌ Problematic diagrams: {len(problematic)}")
        for d in problematic:
            print(f"  - Diagram #{d['index']} (parent: {d['parentId']}) - Visible: {d['visible']}")

        browser.close()

if __name__ == '__main__':
    diagnose_errors()
