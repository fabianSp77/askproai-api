#!/usr/bin/env python3
"""
Verify Mermaid fixes with cache bypass
"""

from playwright.sync_api import sync_playwright

def verify_mermaid_fix():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context()
        page = context.new_page()

        # Capture console messages
        console_errors = []
        def handle_console(msg):
            if msg.type == 'error' and 'translate' in msg.text:
                console_errors.append(msg.text)
        page.on('console', handle_console)

        print("🌐 Loading page with cache bypass...")
        # Add timestamp to bypass cache
        page.goto('https://api.askproai.de/docs/friseur1/agent-v50-interactive-complete.html?v=' + str(int(__import__('time').time())))

        # Hard reload
        page.reload(wait_until='networkidle')
        page.wait_for_timeout(3000)

        print(f"\n📊 Console Errors: {len(console_errors)}")

        if console_errors:
            print("\n❌ Still have errors:")
            for error in console_errors[:3]:  # Show first 3
                print(f"  - {error}")
        else:
            print("\n✅ NO CONSOLE ERRORS!")

        # Check for visible "Syntax error" text
        syntax_errors = page.locator('text=/Syntax error/i').all()
        print(f"\n📋 Visible 'Syntax error' messages: {len(syntax_errors)}")

        if syntax_errors:
            print("❌ Still showing syntax errors in UI")
        else:
            print("✅ No visible syntax errors!")

        # Take screenshot of Data Flow section
        print("\n📸 Taking screenshot...")
        page.screenshot(path='/tmp/mermaid_verified.png', full_page=False)

        # Scroll to Multi-Tenant Architecture
        multi_tenant = page.locator('text=Multi-Tenant Architecture').first
        if multi_tenant.is_visible():
            multi_tenant.scroll_into_view_if_needed()
            page.wait_for_timeout(1000)
            page.screenshot(path='/tmp/multi_tenant_diagram.png', full_page=False)
            print("✅ Multi-Tenant screenshot saved")

        # Scroll to Error Handling Flow
        error_flow = page.locator('text=Error Handling Flow').first
        if error_flow.is_visible():
            error_flow.scroll_into_view_if_needed()
            page.wait_for_timeout(1000)
            page.screenshot(path='/tmp/error_handling_diagram.png', full_page=False)
            print("✅ Error Handling screenshot saved")

        browser.close()

        # Final verdict
        if len(console_errors) == 0 and len(syntax_errors) == 0:
            print("\n" + "="*50)
            print("🎉 SUCCESS! All Mermaid diagrams are working!")
            print("="*50)
            return True
        else:
            print("\n" + "="*50)
            print("⚠️  Still have issues - may need more investigation")
            print("="*50)
            return False

if __name__ == '__main__':
    verify_mermaid_fix()
