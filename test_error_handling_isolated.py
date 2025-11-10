#!/usr/bin/env python3
from playwright.sync_api import sync_playwright

def test_error_handling():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        errors = []
        page.on('console', lambda msg: errors.append(msg.text) if msg.type == 'error' and 'translate' in msg.text else None)

        page.goto('https://api.askproai.de/test_error_handling.html')
        page.wait_for_load_state('networkidle')
        page.wait_for_timeout(3500)

        print(f"Errors: {len(errors)}")
        if errors:
            print("❌ FAILED")
            for e in errors[:3]:
                print(f"  {e}")
        else:
            print("✅ PASSED")

        page.screenshot(path='/tmp/error_handling_isolated.png')
        browser.close()

        return len(errors) == 0

if __name__ == '__main__':
    test_error_handling()
