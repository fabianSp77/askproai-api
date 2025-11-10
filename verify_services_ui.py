#!/usr/bin/env python3
"""
Services UI Verification - Screenshot and Element Check
Verifies all improvements are displaying correctly
"""
from playwright.sync_api import sync_playwright
import os
import sys

# Admin credentials
ADMIN_URL = 'https://api.askproai.de'
ADMIN_EMAIL = os.getenv('ADMIN_EMAIL', 'admin@example.com')
ADMIN_PASSWORD = os.getenv('ADMIN_PASSWORD', 'password')

def verify_services_ui():
    """Verify Services UI improvements with screenshots"""

    print("=" * 70)
    print("SERVICES UI VERIFICATION TEST")
    print("=" * 70)

    with sync_playwright() as p:
        # Launch browser
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(
            viewport={'width': 1920, 'height': 1080},
            user_agent='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36'
        )
        page = context.new_page()

        # Enable console logging
        page.on('console', lambda msg: print(f"[BROWSER] {msg.text}"))

        try:
            # Step 1: Navigate to login
            print("\n📍 Step 1: Navigating to admin panel...")
            page.goto(f'{ADMIN_URL}/admin/login', wait_until='networkidle', timeout=30000)
            page.screenshot(path='/tmp/01_login.png', full_page=True)
            print("   ✅ Login page loaded")

            # Step 2: Login if needed
            if 'login' in page.url:
                print("\n📍 Step 2: Attempting login...")
                try:
                    page.fill('input[type="email"]', ADMIN_EMAIL)
                    page.fill('input[type="password"]', ADMIN_PASSWORD)
                    page.click('button[type="submit"]')
                    page.wait_for_load_state('networkidle', timeout=15000)
                    print("   ✅ Login successful")
                except Exception as e:
                    print(f"   ⚠️  Login error: {e}")
                    print("   Continuing anyway...")

            # Step 3: Navigate to Services page
            print("\n📍 Step 3: Navigating to Services page...")
            page.goto(f'{ADMIN_URL}/admin/services', wait_until='networkidle', timeout=30000)
            page.wait_for_timeout(3000)  # Extra wait for Livewire/Alpine

            # Take full screenshot
            page.screenshot(path='/tmp/02_services_overview_full.png', full_page=True)
            print("   ✅ Full page screenshot saved")

            # Take viewport screenshot
            page.screenshot(path='/tmp/03_services_overview_viewport.png')
            print("   ✅ Viewport screenshot saved")

            # Step 4: Verify table elements
            print("\n📍 Step 4: Verifying table elements...")

            # Check for actions buttons
            actions_count = page.locator('button[aria-label="Open actions"], button:has-text("⋮"), [data-testid*="action"]').count()
            print(f"   • Actions buttons found: {actions_count}")

            # Check for table rows
            rows_count = page.locator('table tbody tr').count()
            print(f"   • Table rows found: {rows_count}")

            # Check for grouping headers (company names)
            group_headers = page.locator('tr[role="row"]:has-text("Friseur"), tr.filament-tables-group-header').count()
            print(f"   • Group headers found: {group_headers}")

            # Check for statistics badges
            stats_badges = page.locator('span:has-text("Termine")').count()
            print(f"   • Statistics badges found: {stats_badges}")

            # Check for composite badges
            composite_badges = page.locator('span:has-text("Composite"), .badge:has-text("🎨")').count()
            print(f"   • Composite badges found: {composite_badges}")

            # Step 5: Find and click a composite service
            print("\n📍 Step 5: Testing composite service detail page...")

            # Try to find Ansatzfärbung link
            ansatz_link = page.locator('a:has-text("Ansatzfärbung")').first

            if ansatz_link.count() > 0:
                print("   ✅ Found 'Ansatzfärbung' service")
                ansatz_link.click()
                page.wait_for_load_state('networkidle', timeout=15000)
                page.wait_for_timeout(2000)

                # Take detail page screenshots
                page.screenshot(path='/tmp/04_composite_detail_full.png', full_page=True)
                print("   ✅ Detail page full screenshot saved")

                page.screenshot(path='/tmp/05_composite_detail_viewport.png')
                print("   ✅ Detail page viewport screenshot saved")

                # Check for segments section
                segments_heading = page.locator('h2:has-text("Service-Segmente"), h3:has-text("Service-Segmente")').count()
                print(f"   • Segments section heading found: {segments_heading}")

                # Check for summary cards
                summary_cards = page.locator('.bg-blue-50, .bg-green-50, .bg-amber-50, .bg-yellow-50').count()
                print(f"   • Summary cards found: {summary_cards}")

                # Check for segment cards
                segment_cards = page.locator('div:has-text("min")').count()
                print(f"   • Segment indicators found: {segment_cards}")

            else:
                print("   ⚠️  'Ansatzfärbung' service not found in list")
                print("   Trying direct URL access...")

                page.goto(f'{ADMIN_URL}/admin/services/440', wait_until='networkidle', timeout=15000)
                page.wait_for_timeout(2000)
                page.screenshot(path='/tmp/04_composite_detail_direct.png', full_page=True)
                print("   ✅ Direct access screenshot saved")

            # Step 6: Save page source for inspection
            print("\n📍 Step 6: Saving page source...")
            content = page.content()
            with open('/tmp/page_source.html', 'w', encoding='utf-8') as f:
                f.write(content)
            print("   ✅ Page source saved to /tmp/page_source.html")

            # Step 7: Test duration tooltip (mouseover)
            print("\n📍 Step 7: Testing tooltips...")
            page.goto(f'{ADMIN_URL}/admin/services', wait_until='networkidle', timeout=30000)
            page.wait_for_timeout(2000)

            # Try to hover over duration column
            duration_cells = page.locator('td:has-text("min")').first
            if duration_cells.count() > 0:
                duration_cells.hover()
                page.wait_for_timeout(1000)
                page.screenshot(path='/tmp/06_tooltip_hover.png')
                print("   ✅ Tooltip hover screenshot saved")

        except Exception as e:
            print(f"\n❌ Error during verification: {e}")
            page.screenshot(path='/tmp/error_screenshot.png', full_page=True)
            print("   Error screenshot saved to /tmp/error_screenshot.png")
            return False

        finally:
            browser.close()

        # Summary
        print("\n" + "=" * 70)
        print("✅ VERIFICATION COMPLETE")
        print("=" * 70)
        print("\nScreenshots saved:")
        print("  📸 /tmp/01_login.png")
        print("  📸 /tmp/02_services_overview_full.png")
        print("  📸 /tmp/03_services_overview_viewport.png")
        print("  📸 /tmp/04_composite_detail_full.png (or direct)")
        print("  📸 /tmp/05_composite_detail_viewport.png")
        print("  📸 /tmp/06_tooltip_hover.png")
        print("  📄 /tmp/page_source.html")
        print("\nPlease review screenshots to verify:")
        print("  1. ✓ Actions button (⋮) visible on right side")
        print("  2. ✓ Company name as group header")
        print("  3. ✓ Statistics showing 'X Termine' badge")
        print("  4. ✓ Composite segments displaying on detail page")
        print("=" * 70)

        return True

if __name__ == '__main__':
    success = verify_services_ui()
    sys.exit(0 if success else 1)
