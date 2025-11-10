#!/usr/bin/env python3
"""
Debug Mermaid Diagram Rendering Issues
Captures screenshots and console logs from the interactive documentation page
"""

from playwright.sync_api import sync_playwright
import json

def debug_mermaid_diagrams():
    with sync_playwright() as p:
        # Launch browser
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Capture console messages
        console_messages = []
        def handle_console(msg):
            console_messages.append({
                'type': msg.type,
                'text': msg.text,
                'location': msg.location
            })
        page.on('console', handle_console)

        # Capture page errors
        page_errors = []
        def handle_error(error):
            page_errors.append(str(error))
        page.on('pageerror', handle_error)

        print("🌐 Navigating to documentation page...")
        page.goto('https://api.askproai.de/docs/friseur1/agent-v50-interactive-complete.html')

        # Wait for page to fully load
        print("⏳ Waiting for page load...")
        page.wait_for_load_state('networkidle')

        # Wait a bit more for Mermaid to attempt rendering
        page.wait_for_timeout(3000)

        # Take full page screenshot
        print("📸 Taking full page screenshot...")
        page.screenshot(path='/tmp/mermaid_full_page.png', full_page=True)

        # Navigate to Data Flow section
        print("📍 Scrolling to Data Flow section...")
        data_flow_heading = page.locator('text=Data Flow Diagrams').first
        if data_flow_heading.is_visible():
            data_flow_heading.scroll_into_view_if_needed()
            page.wait_for_timeout(1000)

        # Screenshot Data Flow section
        print("📸 Taking Data Flow section screenshot...")
        page.screenshot(path='/tmp/mermaid_data_flow_section.png', full_page=False)

        # Find all Mermaid diagram containers
        print("\n🔍 Analyzing Mermaid diagrams...")
        mermaid_containers = page.locator('.mermaid, pre.mermaid').all()
        print(f"Found {len(mermaid_containers)} Mermaid diagram containers")

        # Check for error messages
        error_texts = page.locator('text=/Syntax error|Parse error/i').all()
        if error_texts:
            print(f"\n⚠️  Found {len(error_texts)} error messages:")
            for i, error in enumerate(error_texts):
                print(f"  {i+1}. {error.text_content()}")

        # Get all diagram container details
        diagram_details = []
        for i, container in enumerate(mermaid_containers):
            try:
                text_content = container.text_content()[:200]  # First 200 chars
                is_visible = container.is_visible()
                bounding_box = container.bounding_box()

                detail = {
                    'index': i,
                    'visible': is_visible,
                    'text_preview': text_content,
                    'has_error': 'Syntax error' in text_content or 'Parse error' in text_content,
                    'bounding_box': bounding_box
                }
                diagram_details.append(detail)

                print(f"\nDiagram {i+1}:")
                print(f"  Visible: {is_visible}")
                print(f"  Has Error: {detail['has_error']}")
                print(f"  Text Preview: {text_content[:100]}...")

                # Take individual screenshot if it has an error
                if detail['has_error'] and bounding_box:
                    container.screenshot(path=f'/tmp/mermaid_error_{i+1}.png')
                    print(f"  📸 Saved error screenshot: /tmp/mermaid_error_{i+1}.png")

            except Exception as e:
                print(f"  ❌ Error analyzing diagram {i+1}: {e}")

        # Check the actual HTML structure of diagrams
        print("\n📋 Checking HTML structure...")
        html_structure = page.evaluate("""
            () => {
                const diagrams = document.querySelectorAll('.diagram-container, .mermaid, pre.mermaid');
                return Array.from(diagrams).map((el, idx) => ({
                    index: idx,
                    tagName: el.tagName,
                    className: el.className,
                    innerHTML: el.innerHTML.substring(0, 300),
                    hasError: el.textContent.includes('Syntax error') || el.textContent.includes('Parse error')
                }));
            }
        """)

        for struct in html_structure:
            if struct['hasError']:
                print(f"\n❌ Error in Diagram {struct['index']+1}:")
                print(f"  Tag: <{struct['tagName']}> Class: {struct['className']}")
                print(f"  Content: {struct['innerHTML'][:150]}...")

        # Save console messages to file
        print("\n💾 Saving console messages...")
        with open('/tmp/mermaid_console.json', 'w') as f:
            json.dump(console_messages, f, indent=2)

        # Save page errors to file
        if page_errors:
            print(f"\n⚠️  Found {len(page_errors)} page errors:")
            with open('/tmp/mermaid_page_errors.txt', 'w') as f:
                for error in page_errors:
                    f.write(f"{error}\n\n")
                    print(f"  - {error}")

        # Save diagram details
        with open('/tmp/mermaid_diagram_details.json', 'w') as f:
            json.dump(diagram_details, f, indent=2)

        print("\n✅ Debug complete! Generated files:")
        print("  - /tmp/mermaid_full_page.png")
        print("  - /tmp/mermaid_data_flow_section.png")
        print("  - /tmp/mermaid_console.json")
        print("  - /tmp/mermaid_diagram_details.json")
        if page_errors:
            print("  - /tmp/mermaid_page_errors.txt")

        browser.close()

if __name__ == '__main__':
    debug_mermaid_diagrams()
