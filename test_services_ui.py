#!/usr/bin/env python3
"""
Test Services UI - Screenshot and verify implementation
"""
from playwright.sync_api import sync_playwright
import os

# Admin credentials from environment or defaults
ADMIN_URL = os.getenv('APP_URL', 'https://api.askproai.de')
ADMIN_EMAIL = os.getenv('ADMIN_EMAIL', 'admin@example.com')
ADMIN_PASSWORD = os.getenv('ADMIN_PASSWORD', 'password')

def test_services_ui():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(
            viewport={'width': 1920, 'height': 1080},
            user_agent='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36'
        )
        page = context.new_page()

        print("📍 Step 1: Navigating to login page...")
        page.goto(f'{ADMIN_URL}/admin/login')
        page.wait_for_load_state('networkidle')
        page.screenshot(path='/tmp/01_login_page.png', full_page=True)
        print("✅ Login page screenshot saved")

        # Check if already logged in or needs login
        if 'login' in page.url:
            print("📍 Step 2: Attempting login...")
            try:
                # Try to find email field
                page.fill('input[type="email"]', ADMIN_EMAIL)
                page.fill('input[type="password"]', ADMIN_PASSWORD)
                page.click('button[type="submit"]')
                page.wait_for_load_state('networkidle', timeout=10000)
                print("✅ Login submitted")
            except Exception as e:
                print(f"⚠️  Login form not found or failed: {e}")
                print("   Proceeding to check if already authenticated...")

        print("📍 Step 3: Navigating to Services page...")
        page.goto(f'{ADMIN_URL}/admin/services')
        page.wait_for_load_state('networkidle')
        page.wait_for_timeout(2000)  # Extra wait for Livewire/Alpine

        # Take full page screenshot
        page.screenshot(path='/tmp/02_services_table_full.png', full_page=True)
        print("✅ Services table full screenshot saved")

        # Take viewport screenshot (what user sees immediately)
        page.screenshot(path='/tmp/03_services_table_viewport.png')
        print("✅ Services table viewport screenshot saved")

        print("📍 Step 4: Inspecting table structure...")

        # Check for actions column/button
        actions_buttons = page.locator('button[aria-label="Aktionen"], button:has-text("⋮"), svg.heroicon-o-ellipsis-vertical').count()
        print(f"   Actions buttons found: {actions_buttons}")

        # Check for table rows
        table_rows = page.locator('table tbody tr').count()
        print(f"   Table rows found: {table_rows}")

        # Get first few service names
        service_names = page.locator('table tbody tr td:first-child').all_text_contents()[:3]
        print(f"   First 3 services: {service_names}")

        print("\n📍 Step 5: Looking for composite service...")

        # Try to find a composite service (Service ID 440 - Ansatzfärbung)
        composite_links = page.locator('a:has-text("Ansatzfärbung")').first

        if composite_links.count() > 0:
            print("✅ Found composite service: Ansatzfärbung")
            print("📍 Step 6: Clicking to view details...")

            # Click on the service name to go to detail page
            composite_links.click()
            page.wait_for_load_state('networkidle')
            page.wait_for_timeout(2000)

            # Take screenshots of detail page
            page.screenshot(path='/tmp/04_service_detail_full.png', full_page=True)
            print("✅ Service detail full screenshot saved")

            page.screenshot(path='/tmp/05_service_detail_viewport.png')
            print("✅ Service detail viewport screenshot saved")

            # Check for segments section
            segments_section = page.locator('h3:has-text("Service-Segmente"), h2:has-text("Service-Segmente")').count()
            print(f"   Segments section found: {segments_section}")

            # Check for segment cards
            segment_cards = page.locator('.bg-white.dark\\:bg-gray-800').count()
            print(f"   Segment cards found: {segment_cards}")

            # Check for summary cards (Gesamtdauer, Aktive Behandlung, Pausen)
            summary_cards = page.locator('.bg-blue-50, .bg-green-50, .bg-amber-50').count()
            print(f"   Summary cards found: {summary_cards}")

        else:
            print("⚠️  Composite service 'Ansatzfärbung' not found")
            print("   Trying to access directly by URL...")
            page.goto(f'{ADMIN_URL}/admin/services/440')
            page.wait_for_load_state('networkidle')
            page.wait_for_timeout(2000)
            page.screenshot(path='/tmp/04_service_detail_direct.png', full_page=True)
            print("✅ Direct access screenshot saved")

        print("\n📍 Step 7: Generating HTML inspection report...")

        # Get page content for inspection
        content = page.content()
        with open('/tmp/page_source.html', 'w', encoding='utf-8') as f:
            f.write(content)
        print("✅ Page source saved to /tmp/page_source.html")

        browser.close()

        print("\n" + "="*60)
        print("✅ UI TEST COMPLETE")
        print("="*60)
        print("\nScreenshots saved:")
        print("  - /tmp/01_login_page.png")
        print("  - /tmp/02_services_table_full.png")
        print("  - /tmp/03_services_table_viewport.png")
        print("  - /tmp/04_service_detail_full.png")
        print("  - /tmp/05_service_detail_viewport.png")
        print("  - /tmp/page_source.html")

if __name__ == '__main__':
    test_services_ui()
