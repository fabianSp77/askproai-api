#!/usr/bin/env python3
"""
Extract exact Mermaid source code from the page
"""

from playwright.sync_api import sync_playwright

def extract_mermaid_source():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        print("🌐 Loading page...")
        page.goto('https://api.askproai.de/docs/friseur1/agent-v50-interactive-complete.html?v=' + str(int(__import__('time').time())))
        page.wait_for_load_state('networkidle')
        page.wait_for_timeout(2000)

        # Extract all <pre class="mermaid"> content BEFORE Mermaid processes it
        mermaid_sources = page.evaluate("""
            () => {
                const mermaidEls = document.querySelectorAll('pre.mermaid');
                return Array.from(mermaidEls).map((el, idx) => ({
                    index: idx,
                    source: el.getAttribute('data-original-text') || el.textContent,
                    processed: el.getAttribute('data-processed') === 'true'
                }));
            }
        """)

        print(f"\n📋 Found {len(mermaid_sources)} Mermaid blocks\n")

        for item in mermaid_sources:
            source = item['source'].strip()

            # Identify diagram type
            if source.startswith('graph LR'):
                name = "Multi-Tenant Architecture"
            elif source.startswith('graph TD'):
                name = "Error Handling Flow"
            elif source.startswith('sequenceDiagram'):
                if 'Customer' in source:
                    name = "Complete Booking Flow"
                else:
                    name = f"Dynamic Function Card #{item['index']}"
            else:
                name = f"Unknown Diagram #{item['index']}"

            print(f"{'='*60}")
            print(f"Diagram {item['index']}: {name}")
            print(f"Processed: {item['processed']}")
            print(f"{'='*60}")

            # Show first 500 chars
            if len(source) > 500:
                print(source[:500] + "...\n")
            else:
                print(source + "\n")

            # Check for common issues
            issues = []
            if '|""' in source or '|""' in source:
                issues.append("⚠️  Double-quoted edge labels found")
            if source.count('\n\n\n') > 0:
                issues.append("⚠️  Triple newlines found")
            if ' < ' in source and 'Retry' in source:
                issues.append("⚠️  Unescaped < character in Retry node")
            if '#lt;' in source:
                issues.append("✅ HTML entity #lt; found (correct)")

            if issues:
                for issue in issues:
                    print(issue)
                print()

        browser.close()

if __name__ == '__main__':
    extract_mermaid_source()
