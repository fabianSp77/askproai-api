#!/usr/bin/env python3
"""
Comprehensive Phase 1 Testing Script
Tests all interactive features of agent-v50-interactive-complete.html
"""

from playwright.sync_api import sync_playwright
import time
import json
from pathlib import Path

def test_interactive_documentation():
    """Test all Phase 1 features of the interactive documentation"""

    results = {
        "mermaid_diagrams": {"status": "pending", "details": []},
        "api_authentication": {"status": "pending", "details": []},
        "test_mode_toggle": {"status": "pending", "details": []},
        "interactive_forms": {"status": "pending", "details": []},
        "json_export": {"status": "pending", "details": []},
        "notifications": {"status": "pending", "details": []}
    }

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Get absolute file path
        html_path = Path("/var/www/api-gateway/public/docs/friseur1/agent-v50-interactive-complete.html").absolute()
        page.goto(f"file://{html_path}")
        page.wait_for_load_state('networkidle')

        print("=" * 80)
        print("PHASE 1 COMPREHENSIVE TESTING")
        print("=" * 80)

        # TEST 1: Mermaid Diagrams
        print("\n[TEST 1] Mermaid Diagrams Rendering...")
        try:
            # Navigate to Data Flow section
            page.click('a[href="#data-flow"]')
            time.sleep(2)  # Wait for Mermaid rendering

            # Check for SVG elements (Mermaid renders as SVG)
            svg_elements = page.locator('svg').all()

            if len(svg_elements) >= 3:
                results["mermaid_diagrams"]["status"] = "✅ PASS"
                results["mermaid_diagrams"]["details"].append(f"Found {len(svg_elements)} SVG diagrams")
                print(f"  ✅ Found {len(svg_elements)} Mermaid diagrams rendered as SVG")
            else:
                results["mermaid_diagrams"]["status"] = "❌ FAIL"
                results["mermaid_diagrams"]["details"].append(f"Only {len(svg_elements)} SVG elements found, expected ≥3")
                print(f"  ❌ Only {len(svg_elements)} diagrams found, expected at least 3")

            # Check for specific diagram titles
            diagram_titles = [
                "Complete Booking Flow",
                "Multi-Tenant Architecture",
                "Error Handling Flow"
            ]

            for title in diagram_titles:
                if page.locator(f"text={title}").count() > 0:
                    results["mermaid_diagrams"]["details"].append(f"✓ '{title}' section found")
                    print(f"  ✓ '{title}' section found")
                else:
                    results["mermaid_diagrams"]["details"].append(f"✗ '{title}' section missing")
                    print(f"  ✗ '{title}' section missing")

        except Exception as e:
            results["mermaid_diagrams"]["status"] = "❌ ERROR"
            results["mermaid_diagrams"]["details"].append(str(e))
            print(f"  ❌ ERROR: {e}")

        # TEST 2: API Authentication System
        print("\n[TEST 2] API Authentication System...")
        try:
            # Find token input field
            token_input = page.locator('#api-token')
            if token_input.count() == 0:
                raise Exception("Token input field not found")

            # Test token save
            test_token = "test_bearer_token_12345"
            token_input.fill(test_token)
            token_input.dispatch_event('change')
            time.sleep(0.5)

            # Check localStorage
            stored_token = page.evaluate("() => localStorage.getItem('retell_api_token')")

            if stored_token == test_token:
                results["api_authentication"]["status"] = "✅ PASS"
                results["api_authentication"]["details"].append("Token saved to localStorage")
                print("  ✅ Token successfully saved to localStorage")
            else:
                results["api_authentication"]["status"] = "❌ FAIL"
                results["api_authentication"]["details"].append(f"Token mismatch: expected '{test_token}', got '{stored_token}'")
                print(f"  ❌ Token not saved correctly")

            # Test token persistence (reload page)
            page.reload()
            page.wait_for_load_state('networkidle')
            time.sleep(1)

            reloaded_token = page.evaluate("() => localStorage.getItem('retell_api_token')")
            if reloaded_token == test_token:
                results["api_authentication"]["details"].append("Token persisted after reload")
                print("  ✅ Token persisted after page reload")
            else:
                results["api_authentication"]["details"].append("Token lost after reload")
                print("  ⚠️  Token not persisted after reload")

        except Exception as e:
            results["api_authentication"]["status"] = "❌ ERROR"
            results["api_authentication"]["details"].append(str(e))
            print(f"  ❌ ERROR: {e}")

        # TEST 3: Test Mode Toggle
        print("\n[TEST 3] Test Mode Toggle...")
        try:
            # Find test mode checkbox
            test_mode_checkbox = page.locator('#test-mode')
            test_mode_label = page.locator('#test-mode-label')

            # Initial state should be unchecked (Production)
            is_checked = page.evaluate("() => document.getElementById('test-mode').checked")
            label_text = test_mode_label.inner_text()

            print(f"  Initial state: checkbox={is_checked}, label='{label_text}'")

            # Toggle to Test Mode
            test_mode_checkbox.click()
            time.sleep(0.5)

            # Check state after toggle
            is_checked_after = page.evaluate("() => document.getElementById('test-mode').checked")
            label_text_after = test_mode_label.inner_text()
            stored_mode = page.evaluate("() => localStorage.getItem('retell_test_mode')")

            if is_checked_after and label_text_after == "Test Mode" and stored_mode == "true":
                results["test_mode_toggle"]["status"] = "✅ PASS"
                results["test_mode_toggle"]["details"].append("Toggle activates Test Mode")
                results["test_mode_toggle"]["details"].append(f"Label changed to '{label_text_after}'")
                results["test_mode_toggle"]["details"].append("Setting saved to localStorage")
                print("  ✅ Test Mode activated successfully")
                print(f"  ✅ Label changed to '{label_text_after}'")
                print("  ✅ Setting saved to localStorage")
            else:
                results["test_mode_toggle"]["status"] = "❌ FAIL"
                results["test_mode_toggle"]["details"].append(f"Toggle issues: checked={is_checked_after}, label='{label_text_after}', stored='{stored_mode}'")
                print(f"  ❌ Toggle not working correctly")

            # Toggle back to Production
            test_mode_checkbox.click()
            time.sleep(0.5)
            label_text_final = test_mode_label.inner_text()

            if label_text_final == "Production":
                results["test_mode_toggle"]["details"].append("Toggle back to Production works")
                print("  ✅ Toggle back to Production works")

        except Exception as e:
            results["test_mode_toggle"]["status"] = "❌ ERROR"
            results["test_mode_toggle"]["details"].append(str(e))
            print(f"  ❌ ERROR: {e}")

        # TEST 4: Interactive Testing Forms
        print("\n[TEST 4] Interactive Testing Forms...")
        try:
            # Navigate to Interactive Testing section
            page.click('a[href="#interactive-testing"]')
            time.sleep(1)

            # Count function cards
            function_cards = page.locator('.function-card').all()
            print(f"  Found {len(function_cards)} function cards")

            # Test one form (collect_appointment_info)
            collect_form = page.locator('#collect_appointment_info-form')

            if collect_form.count() > 0:
                results["interactive_forms"]["status"] = "✅ PASS"
                results["interactive_forms"]["details"].append(f"Found {len(function_cards)} function cards")
                results["interactive_forms"]["details"].append("collect_appointment_info form exists")
                print(f"  ✅ {len(function_cards)} function cards with forms")
                print("  ✅ Test form (collect_appointment_info) found")

                # Check for form fields
                call_id_input = page.locator('input[name="call_id"]').first
                if call_id_input.count() > 0:
                    results["interactive_forms"]["details"].append("Form fields present")
                    print("  ✅ Form fields present (call_id input found)")

                # Check for submit button
                submit_button = page.locator('button[type="submit"]').first
                if submit_button.count() > 0:
                    results["interactive_forms"]["details"].append("Submit button present")
                    print("  ✅ Submit button present")
            else:
                results["interactive_forms"]["status"] = "❌ FAIL"
                results["interactive_forms"]["details"].append("Test form not found")
                print("  ❌ Test form not found")

        except Exception as e:
            results["interactive_forms"]["status"] = "❌ ERROR"
            results["interactive_forms"]["details"].append(str(e))
            print(f"  ❌ ERROR: {e}")

        # TEST 5: JSON Export
        print("\n[TEST 5] JSON Export Functionality...")
        try:
            # Navigate back to overview
            page.click('a[href="#overview"]')
            time.sleep(1)

            # Look for export button
            export_button = page.locator('text=Export JSON')

            if export_button.count() > 0:
                results["json_export"]["status"] = "✅ PASS"
                results["json_export"]["details"].append("Export JSON button found")
                print("  ✅ Export JSON button found")

                # Check if exportDocumentation function exists
                has_export_function = page.evaluate("""
                    () => typeof window.exportDocumentation === 'function'
                """)

                if has_export_function:
                    results["json_export"]["details"].append("exportDocumentation() function defined")
                    print("  ✅ exportDocumentation() function defined")
                else:
                    results["json_export"]["details"].append("exportDocumentation() function missing")
                    print("  ⚠️  exportDocumentation() function not found")
            else:
                results["json_export"]["status"] = "❌ FAIL"
                results["json_export"]["details"].append("Export button not found")
                print("  ❌ Export JSON button not found")

        except Exception as e:
            results["json_export"]["status"] = "❌ ERROR"
            results["json_export"]["details"].append(str(e))
            print(f"  ❌ ERROR: {e}")

        # TEST 6: Notification System
        print("\n[TEST 6] Notification System...")
        try:
            # Check if showNotification function exists
            has_notification_function = page.evaluate("""
                () => typeof window.showNotification === 'function'
            """)

            if has_notification_function:
                results["notifications"]["status"] = "✅ PASS"
                results["notifications"]["details"].append("showNotification() function defined")
                print("  ✅ showNotification() function defined")

                # Trigger a notification by toggling test mode
                page.locator('#test-mode').click()
                time.sleep(0.5)

                # Check for alert element (notification)
                alert = page.locator('.alert').first
                if alert.count() > 0:
                    results["notifications"]["details"].append("Notification triggered and visible")
                    print("  ✅ Notification triggered by Test Mode toggle")
                else:
                    results["notifications"]["details"].append("No visible notification")
                    print("  ⚠️  Notification not visible (may have already faded)")
            else:
                results["notifications"]["status"] = "❌ FAIL"
                results["notifications"]["details"].append("showNotification() function missing")
                print("  ❌ showNotification() function not found")

        except Exception as e:
            results["notifications"]["status"] = "❌ ERROR"
            results["notifications"]["details"].append(str(e))
            print(f"  ❌ ERROR: {e}")

        # Take final screenshot
        screenshot_path = "/tmp/interactive_docs_test.png"
        page.screenshot(path=screenshot_path, full_page=True)
        print(f"\n📸 Screenshot saved: {screenshot_path}")

        browser.close()

    # Print Summary
    print("\n" + "=" * 80)
    print("PHASE 1 TEST SUMMARY")
    print("=" * 80)

    total_tests = len(results)
    passed = sum(1 for r in results.values() if r["status"].startswith("✅"))
    failed = sum(1 for r in results.values() if r["status"].startswith("❌"))

    for test_name, result in results.items():
        print(f"\n{test_name.upper().replace('_', ' ')}: {result['status']}")
        for detail in result['details']:
            print(f"  {detail}")

    print("\n" + "=" * 80)
    print(f"RESULTS: {passed}/{total_tests} tests passed")
    print("=" * 80)

    # Save results to JSON
    results_file = "/var/www/api-gateway/PHASE_1_TEST_RESULTS.json"
    with open(results_file, 'w') as f:
        json.dump(results, f, indent=2)
    print(f"\n📄 Detailed results saved: {results_file}")

    return passed == total_tests

if __name__ == "__main__":
    success = test_interactive_documentation()
    exit(0 if success else 1)
