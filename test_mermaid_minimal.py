#!/usr/bin/env python3
"""
Test minimal Mermaid examples
"""

from playwright.sync_api import sync_playwright

def test_mermaid_minimal():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Capture console
        console_errors = []
        def handle_console(msg):
            if msg.type == 'error' and 'translate' in msg.text:
                console_errors.append(msg.text)
        page.on('console', handle_console)

        print("🌐 Testing minimal Mermaid examples...")
        page.goto('https://api.askproai.de/mermaid_test.html')
        page.wait_for_load_state('networkidle')
        page.wait_for_timeout(3000)

        print(f"\n📊 Console Errors: {len(console_errors)}")

        if console_errors:
            print("\n❌ Minimal test FAILED - syntax issues confirmed:")
            for error in console_errors[:5]:
                print(f"  - {error}")
        else:
            print("\n✅ Minimal test PASSED - all diagrams render correctly!")

        # Take screenshot
        page.screenshot(path='/tmp/mermaid_test_minimal.png', full_page=True)
        print("\n📸 Screenshot saved: /tmp/mermaid_test_minimal.png")

        browser.close()

        return len(console_errors) == 0

if __name__ == '__main__':
    success = test_mermaid_minimal()
    exit(0 if success else 1)
