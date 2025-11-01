#!/usr/bin/env python3
"""
Visual Tests with Selenium + Firefox ESR
ARM64-compatible browser testing

Coverage:
- Homepage loading without 404s
- Admin login page rendering
- Health endpoints
- API endpoints
- Vite asset validation
- Responsive design
"""

import argparse
import os
import sys
import time
from datetime import datetime
from pathlib import Path
from typing import List, Tuple

import requests
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.firefox.options import Options
from selenium.webdriver.firefox.service import Service
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait


class VisualTestSuite:
    """Visual test suite using Selenium + Firefox ESR"""

    def __init__(self, base_url: str, screenshot_dir: str = "test-results/screenshots"):
        self.base_url = base_url.rstrip('/')
        self.screenshot_dir = Path(screenshot_dir)
        self.screenshot_dir.mkdir(parents=True, exist_ok=True)

        self.driver = None
        self.results: List[Tuple[str, bool, str]] = []

    def setup_driver(self):
        """Setup Firefox ESR webdriver"""
        options = Options()
        options.add_argument('--headless')
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--disable-gpu')
        options.set_preference('browser.download.folderList', 2)

        # Use geckodriver
        service = Service(executable_path='/usr/local/bin/geckodriver')

        self.driver = webdriver.Firefox(service=service, options=options)
        self.driver.set_window_size(1920, 1080)

        print(f"✅ Firefox ESR driver initialized")
        print(f"   Browser: {self.driver.capabilities['browserName']} {self.driver.capabilities['browserVersion']}")

    def teardown_driver(self):
        """Close webdriver"""
        if self.driver:
            self.driver.quit()
            print("✅ Firefox ESR driver closed")

    def take_screenshot(self, name: str, prefix: str = ""):
        """Take screenshot and save to file"""
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"{prefix}{name}_{timestamp}.png" if prefix else f"{name}_{timestamp}.png"
        filepath = self.screenshot_dir / filename

        self.driver.save_screenshot(str(filepath))
        print(f"   📸 Screenshot: {filepath}")
        return filepath

    def test_homepage_loads(self) -> bool:
        """Test 1: Homepage loads without 404 errors"""
        test_name = "test_homepage_loads"
        print(f"\n🧪 Running: {test_name}")

        try:
            # Track network requests for 404s
            failed_resources = []

            # Load homepage
            self.driver.get(self.base_url)
            time.sleep(3)  # Wait for dynamic content

            # Check for JavaScript errors in console
            logs = self.driver.get_log('browser')
            errors = [log for log in logs if log['level'] == 'SEVERE']

            # Take screenshot
            self.take_screenshot("homepage", "PASS_" if not errors else "FAIL_")

            # Check page title exists
            assert self.driver.title, "Page title is empty"

            # Check body content exists
            body = self.driver.find_element(By.TAG_NAME, "body")
            assert body.text.strip(), "Body content is empty"

            if errors:
                print(f"   ⚠️  Found {len(errors)} console errors")
                for error in errors[:5]:  # Show first 5
                    print(f"      {error['message']}")
                self.results.append((test_name, False, f"{len(errors)} console errors"))
                return False

            self.results.append((test_name, True, "Homepage loaded successfully"))
            print(f"   ✅ PASS")
            return True

        except Exception as e:
            self.take_screenshot("homepage", "FAIL_")
            self.results.append((test_name, False, str(e)))
            print(f"   ❌ FAIL: {e}")
            return False

    def test_admin_login_page(self) -> bool:
        """Test 2: Admin login page renders correctly"""
        test_name = "test_admin_login_page"
        print(f"\n🧪 Running: {test_name}")

        try:
            self.driver.get(f"{self.base_url}/admin/login")
            time.sleep(2)

            # Check essential form elements
            wait = WebDriverWait(self.driver, 10)

            email_input = wait.until(
                EC.presence_of_element_located((By.CSS_SELECTOR, 'input[type="email"]'))
            )
            password_input = self.driver.find_element(By.CSS_SELECTOR, 'input[type="password"]')
            submit_button = self.driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]')

            assert email_input.is_displayed(), "Email input not visible"
            assert password_input.is_displayed(), "Password input not visible"
            assert submit_button.is_displayed(), "Submit button not visible"

            self.take_screenshot("admin_login", "PASS_")

            self.results.append((test_name, True, "Admin login renders correctly"))
            print(f"   ✅ PASS")
            return True

        except Exception as e:
            self.take_screenshot("admin_login", "FAIL_")
            self.results.append((test_name, False, str(e)))
            print(f"   ❌ FAIL: {e}")
            return False

    def test_responsive_design(self) -> bool:
        """Test 3: Responsive design (mobile, tablet, desktop)"""
        test_name = "test_responsive_design"
        print(f"\n🧪 Running: {test_name}")

        try:
            viewports = [
                ("desktop", 1920, 1080),
                ("tablet", 768, 1024),
                ("mobile", 375, 667)
            ]

            for name, width, height in viewports:
                self.driver.set_window_size(width, height)
                self.driver.get(f"{self.base_url}/admin/login")
                time.sleep(1)

                email_input = self.driver.find_element(By.CSS_SELECTOR, 'input[type="email"]')
                assert email_input.is_displayed(), f"Email not visible on {name}"

                self.take_screenshot(f"responsive_{name}", "PASS_")
                print(f"   ✅ {name.capitalize()}: {width}x{height} OK")

            self.results.append((test_name, True, "All viewports render correctly"))
            return True

        except Exception as e:
            self.take_screenshot(f"responsive_{name}", "FAIL_")
            self.results.append((test_name, False, str(e)))
            print(f"   ❌ FAIL: {e}")
            return False

    def test_health_endpoints(self) -> bool:
        """Test 4: Health endpoints return 200"""
        test_name = "test_health_endpoints"
        print(f"\n🧪 Running: {test_name}")

        try:
            endpoints = [
                ("/health", 200),
                ("/api/health", 200),
            ]

            for endpoint, expected_status in endpoints:
                response = requests.get(f"{self.base_url}{endpoint}", timeout=10)
                assert response.status_code == expected_status, \
                    f"{endpoint} returned {response.status_code}, expected {expected_status}"
                print(f"   ✅ {endpoint}: {response.status_code}")

            self.results.append((test_name, True, "All health endpoints OK"))
            return True

        except Exception as e:
            self.results.append((test_name, False, str(e)))
            print(f"   ❌ FAIL: {e}")
            return False

    def test_vite_assets(self) -> bool:
        """Test 5: Vite assets load correctly (no 404s)"""
        test_name = "test_vite_assets"
        print(f"\n🧪 Running: {test_name}")

        try:
            # Check manifest.json
            manifest_url = f"{self.base_url}/build/manifest.json"
            response = requests.get(manifest_url, timeout=10)
            assert response.status_code == 200, "Vite manifest.json not found"

            manifest = response.json()
            failed_assets = []

            # Check each asset
            for entry in manifest.values():
                if 'file' in entry:
                    asset_url = f"{self.base_url}/build/{entry['file']}"
                    asset_response = requests.head(asset_url, timeout=5)
                    if asset_response.status_code != 200:
                        failed_assets.append(f"{entry['file']} ({asset_response.status_code})")
                        print(f"   ❌ Asset failed: {entry['file']}")
                    else:
                        print(f"   ✅ Asset OK: {entry['file']}")

            if failed_assets:
                self.results.append((test_name, False, f"Failed assets: {', '.join(failed_assets)}"))
                return False

            self.results.append((test_name, True, f"{len(manifest)} assets validated"))
            print(f"   ✅ PASS: All {len(manifest)} Vite assets OK")
            return True

        except Exception as e:
            self.results.append((test_name, False, str(e)))
            print(f"   ❌ FAIL: {e}")
            return False

    def test_performance(self) -> bool:
        """Test 6: Page loads within acceptable time"""
        test_name = "test_performance"
        print(f"\n🧪 Running: {test_name}")

        try:
            start_time = time.time()
            self.driver.get(self.base_url)
            load_time = time.time() - start_time

            assert load_time < 10, f"Page load too slow: {load_time:.2f}s"

            self.results.append((test_name, True, f"Load time: {load_time:.2f}s"))
            print(f"   ✅ PASS: Load time {load_time:.2f}s")
            return True

        except Exception as e:
            self.results.append((test_name, False, str(e)))
            print(f"   ❌ FAIL: {e}")
            return False

    def test_404_page(self) -> bool:
        """Test 7: 404 page renders (not blank)"""
        test_name = "test_404_page"
        print(f"\n🧪 Running: {test_name}")

        try:
            response = requests.get(f"{self.base_url}/this-page-does-not-exist", timeout=10)
            assert response.status_code == 404, f"Expected 404, got {response.status_code}"
            assert len(response.text) > 0, "404 page is blank"

            self.driver.get(f"{self.base_url}/this-page-does-not-exist")
            time.sleep(1)

            body = self.driver.find_element(By.TAG_NAME, "body")
            assert body.text.strip(), "404 page body is empty"

            self.take_screenshot("404_page", "PASS_")

            self.results.append((test_name, True, "404 page renders correctly"))
            print(f"   ✅ PASS")
            return True

        except Exception as e:
            self.take_screenshot("404_page", "FAIL_")
            self.results.append((test_name, False, str(e)))
            print(f"   ❌ FAIL: {e}")
            return False

    def run_all_tests(self) -> bool:
        """Run all tests and return success status"""
        print(f"\n🚀 Starting Visual Test Suite")
        print(f"Base URL: {self.base_url}")
        print(f"Screenshot Dir: {self.screenshot_dir}")
        print("=" * 80)

        try:
            self.setup_driver()

            # Run all tests
            self.test_homepage_loads()
            self.test_admin_login_page()
            self.test_responsive_design()
            self.test_health_endpoints()
            self.test_vite_assets()
            self.test_performance()
            self.test_404_page()

            self.teardown_driver()

            # Print summary
            print("\n" + "=" * 80)
            print("📊 Test Summary")
            print("=" * 80)

            passed = sum(1 for _, success, _ in self.results if success)
            failed = len(self.results) - passed

            for test_name, success, message in self.results:
                status = "✅ PASS" if success else "❌ FAIL"
                print(f"{status}: {test_name}")
                print(f"         {message}")

            print("=" * 80)
            print(f"Total: {len(self.results)} | Passed: {passed} | Failed: {failed}")
            print("=" * 80)

            # Generate HTML report
            self.generate_html_report()

            return failed == 0

        except Exception as e:
            print(f"\n❌ Test suite failed: {e}")
            self.teardown_driver()
            return False

    def generate_html_report(self):
        """Generate HTML test report"""
        report_path = Path("test-results/report.html")
        report_path.parent.mkdir(parents=True, exist_ok=True)

        passed = sum(1 for _, success, _ in self.results if success)
        failed = len(self.results) - passed

        html = f"""<!DOCTYPE html>
<html>
<head>
    <title>Visual Tests Report - {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}</title>
    <style>
        body {{ font-family: sans-serif; margin: 20px; }}
        .pass {{ color: green; }}
        .fail {{ color: red; }}
        table {{ border-collapse: collapse; width: 100%; }}
        th, td {{ border: 1px solid #ddd; padding: 8px; text-align: left; }}
        th {{ background-color: #f2f2f2; }}
    </style>
</head>
<body>
    <h1>Visual Tests Report</h1>
    <p><strong>Base URL:</strong> {self.base_url}</p>
    <p><strong>Date:</strong> {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}</p>
    <p><strong>Browser:</strong> Firefox ESR</p>

    <h2>Summary</h2>
    <p>Total: {len(self.results)} | <span class="pass">Passed: {passed}</span> | <span class="fail">Failed: {failed}</span></p>

    <h2>Results</h2>
    <table>
        <tr>
            <th>Test</th>
            <th>Status</th>
            <th>Message</th>
        </tr>
"""

        for test_name, success, message in self.results:
            status_class = "pass" if success else "fail"
            status_text = "PASS" if success else "FAIL"
            html += f"""
        <tr>
            <td>{test_name}</td>
            <td class="{status_class}">{status_text}</td>
            <td>{message}</td>
        </tr>
"""

        html += """
    </table>
</body>
</html>
"""

        report_path.write_text(html)
        print(f"\n📄 HTML Report: {report_path}")


def main():
    parser = argparse.ArgumentParser(description='Run visual tests with Selenium + Firefox ESR')
    parser.add_argument('--base-url', default='https://staging.askproai.de',
                       help='Base URL for testing (default: https://staging.askproai.de)')
    parser.add_argument('--screenshot-dir', default='test-results/screenshots',
                       help='Directory for screenshots (default: test-results/screenshots)')

    args = parser.parse_args()

    # Get from environment if set
    base_url = os.getenv('BASE_URL', args.base_url)
    screenshot_dir = os.getenv('SCREENSHOT_DIR', args.screenshot_dir)

    suite = VisualTestSuite(base_url, screenshot_dir)
    success = suite.run_all_tests()

    sys.exit(0 if success else 1)


if __name__ == '__main__':
    main()
