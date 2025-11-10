#!/usr/bin/env python3
"""
Voice AI Agent Documentation Generator

Generates complete HTML documentation from JSON function definitions.
This allows maintaining documentation as code in JSON format and
generating the full interactive HTML documentation automatically.

Usage:
    python scripts/generate-voice-docs.py
    python scripts/generate-voice-docs.py --output public/docs/voice-agent
    python scripts/generate-voice-docs.py --watch  # Watch for changes
"""

import json
import os
import sys
import argparse
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Any


class VoiceDocsGenerator:
    """Generates HTML documentation from JSON function definitions"""

    def __init__(self, functions_dir: Path, output_dir: Path):
        self.functions_dir = functions_dir
        self.output_dir = output_dir
        self.functions = []

    def load_functions(self) -> List[Dict[str, Any]]:
        """Load all function definitions from JSON files"""
        functions = []

        if not self.functions_dir.exists():
            print(f"Warning: Functions directory not found: {self.functions_dir}")
            return functions

        for json_file in self.functions_dir.glob("*.json"):
            try:
                with open(json_file, 'r') as f:
                    function_def = json.load(f)
                    functions.append(function_def)
                    print(f"Loaded: {function_def['name']}")
            except Exception as e:
                print(f"Error loading {json_file}: {e}")

        return functions

    def generate_nav_section(self, functions: List[Dict]) -> str:
        """Generate navigation section for functions"""
        nav_items = []
        for func in sorted(functions, key=lambda x: x['name']):
            nav_items.append(
                f'<a href="#{func["name"]}" class="nav-link" '
                f'onclick="navigate(\'{func["name"]}\')">{func["name"]}</a>'
            )
        return '\n                    '.join(nav_items)

    def generate_feature_matrix_rows(self, functions: List[Dict]) -> str:
        """Generate feature matrix table rows"""
        rows = []

        status_badges = {
            'implemented': 'badge-success',
            'partial': 'badge-warning',
            'specified': 'badge-info',
            'deprecated': 'badge-danger'
        }

        priority_badges = {
            'critical': 'badge-critical',
            'high': 'badge-high',
            'medium': 'badge-medium',
            'low': 'badge-low'
        }

        for func in sorted(functions, key=lambda x: x['name']):
            metadata = func.get('metadata', {})
            testing = func.get('testing', {})

            status = metadata.get('status', 'specified')
            priority = metadata.get('priority', 'medium')

            # Determine test status
            unit_coverage = testing.get('unit', {}).get('coverage', 0)
            if unit_coverage >= 80:
                test_status = '<span class="badge badge-success">Pass</span>'
            elif unit_coverage > 0:
                test_status = '<span class="badge badge-warning">Partial</span>'
            else:
                test_status = '<span class="badge badge-danger">Untested</span>'

            # Determine specification status
            has_params = len(func.get('parameters', {}).get('required', [])) > 0
            has_responses = 'success' in func.get('responses', {})
            has_examples = len(func.get('examples', [])) > 0

            if has_params and has_responses and has_examples:
                spec_status = '<span class="badge badge-success">Complete</span>'
            elif has_params and has_responses:
                spec_status = '<span class="badge badge-warning">Partial</span>'
            else:
                spec_status = '<span class="badge badge-danger">Missing</span>'

            row = f'''
                            <tr>
                                <td><strong>{func["name"]}</strong></td>
                                <td><span class="badge {status_badges.get(status, 'badge-info')}">{status.title()}</span></td>
                                <td><span class="badge {priority_badges.get(priority, 'badge-medium')}">{priority.title()}</span></td>
                                <td>{spec_status}</td>
                                <td>{test_status}</td>
                                <td><a href="#{func['name']}">View Docs</a></td>
                            </tr>
            '''
            rows.append(row)

        return '\n'.join(rows)

    def generate_parameter_table(self, parameters: List[Dict]) -> str:
        """Generate parameter table HTML"""
        if not parameters:
            return '<p>No parameters</p>'

        rows = []
        for param in parameters:
            required = '<span class="badge badge-danger">Yes</span>'
            param_type = param.get('type', 'string')
            param_format = param.get('format', '')
            type_display = f"{param_type}" + (f" ({param_format})" if param_format else "")

            row = f'''
                                    <tr>
                                        <td><code>{param["name"]}</code></td>
                                        <td><span class="param-type">{type_display}</span></td>
                                        <td>{required}</td>
                                        <td>{param.get("description", "")}</td>
                                    </tr>
            '''
            rows.append(row)

        return f'''
                                <table class="param-table">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {''.join(rows)}
                                    </tbody>
                                </table>
        '''

    def generate_form_fields(self, func_name: str, parameters: Dict) -> str:
        """Generate form fields for interactive testing"""
        fields = []

        required = parameters.get('required', [])
        optional = parameters.get('optional', [])

        for param in required:
            field = self._generate_field(param, True)
            fields.append(field)

        for param in optional:
            field = self._generate_field(param, False)
            fields.append(field)

        return '\n'.join(fields)

    def _generate_field(self, param: Dict, is_required: bool) -> str:
        """Generate a single form field"""
        param_name = param['name']
        param_type = param.get('type', 'string')
        description = param.get('description', '')
        example = param.get('example', '')

        required_mark = '<span class="required">*</span>' if is_required else ''
        required_attr = 'required' if is_required else ''

        input_type = 'text'
        if param_type == 'integer' or param_type == 'number':
            input_type = 'number'
        elif param.get('format') == 'email':
            input_type = 'email'
        elif param.get('format') == 'date':
            input_type = 'date'
        elif param.get('format') == 'time':
            input_type = 'time'
        elif param.get('format') == 'phone':
            input_type = 'tel'

        # Handle enums with select
        if param.get('enum'):
            options = ''.join([
                f'<option value="{val}">{val}</option>'
                for val in param['enum']
            ])
            return f'''
                                    <div class="form-group">
                                        <label class="form-label">{param_name.replace('_', ' ').title()} {required_mark}</label>
                                        <select class="form-select" name="{param_name}" {required_attr}>
                                            <option value="">Select...</option>
                                            {options}
                                        </select>
                                        <div class="form-hint">{description}</div>
                                    </div>
            '''

        # Handle text areas for long text
        if param.get('maxLength', 0) > 255:
            return f'''
                                    <div class="form-group">
                                        <label class="form-label">{param_name.replace('_', ' ').title()} {required_mark}</label>
                                        <textarea class="form-textarea" name="{param_name}" {required_attr}
                                                  placeholder="{example}">{example if not is_required else ''}</textarea>
                                        <div class="form-hint">{description}</div>
                                    </div>
            '''

        return f'''
                                    <div class="form-group">
                                        <label class="form-label">{param_name.replace('_', ' ').title()} {required_mark}</label>
                                        <input type="{input_type}" class="form-input" name="{param_name}"
                                               value="{example if not is_required else ''}" {required_attr}
                                               placeholder="{example}">
                                        <div class="form-hint">{description}</div>
                                    </div>
        '''

    def generate_examples_section(self, examples: List[Dict]) -> str:
        """Generate examples section"""
        if not examples:
            return '<p>No examples available</p>'

        cards = []
        for example in examples:
            curl = example.get('request', {}).get('curl', '')
            response = json.dumps(example.get('response', {}).get('body', {}), indent=2)

            card = f'''
                        <div class="card">
                            <h3 class="section-title">{example["name"]}</h3>
                            <p>{example.get("description", "")}</p>
                            <div class="code-block">
                                <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                                <pre>{curl}</pre>
                            </div>
                            <div style="margin-top: 1rem;">
                                <strong>Response ({example.get("response", {}).get("statusCode", 200)}):</strong>
                                <div class="code-block" style="margin-top: 0.5rem;">
                                    <pre>{response}</pre>
                                </div>
                            </div>
                        </div>
            '''
            cards.append(card)

        return '\n'.join(cards)

    def generate_function_section(self, func: Dict) -> str:
        """Generate complete section for a function"""
        name = func['name']
        metadata = func.get('metadata', {})
        endpoint = func.get('endpoint', {})
        parameters = func.get('parameters', {})
        responses = func.get('responses', {})
        examples = func.get('examples', [])
        dataflow = func.get('dataFlow', {})

        # Generate tabs content
        param_table_required = self.generate_parameter_table(
            parameters.get('required', [])
        )
        param_table_optional = self.generate_parameter_table(
            parameters.get('optional', [])
        )
        form_fields = self.generate_form_fields(name, parameters)
        examples_html = self.generate_examples_section(examples)

        # Success response example
        success_response = json.dumps(
            responses.get('success', {}).get('example', {}),
            indent=2
        )

        return f'''
            <!-- {name} Function Section -->
            <section id="{name}" class="section">
                <div class="page-header">
                    <h1 class="page-title">{name}</h1>
                    <p class="page-description">{metadata.get("description", "")}</p>
                </div>

                <div class="tabs">
                    <div class="tab active" onclick="switchTab('{name}-docs')">Documentation</div>
                    <div class="tab" onclick="switchTab('{name}-test')">Interactive Test</div>
                    <div class="tab" onclick="switchTab('{name}-examples')">Examples</div>
                    <div class="tab" onclick="switchTab('{name}-flow')">Data Flow</div>
                </div>

                <!-- Documentation Tab -->
                <div id="{name}-docs" class="tab-content active">
                    <div class="card">
                        <h3 class="section-title">Function Specification</h3>
                        <p style="margin-bottom: 1rem;">{metadata.get("description", "")}</p>

                        <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Endpoint</h4>
                        <div class="code-block">
                            <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                            <pre>{endpoint.get("method", "POST")} {endpoint.get("path", "")}</pre>
                        </div>

                        <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Required Parameters</h4>
                        {param_table_required}

                        <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Optional Parameters</h4>
                        {param_table_optional}

                        <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Success Response</h4>
                        <div class="code-block">
                            <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                            <pre>{success_response}</pre>
                        </div>
                    </div>
                </div>

                <!-- Interactive Test Tab -->
                <div id="{name}-test" class="tab-content">
                    <div class="playground">
                        <div class="playground-section">
                            <h3 class="section-title">Request Builder</h3>
                            <form id="{name}-form" onsubmit="testFunction_{name}(event)">
                                {form_fields}

                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    Test Function Call
                                </button>
                            </form>
                        </div>

                        <div class="playground-section">
                            <h3 class="section-title">Response</h3>
                            <div id="{name}-response" class="response-container">
                                <div class="response-header">
                                    <div class="response-status success">Status: <span id="{name}-status-code">200</span></div>
                                    <button class="btn btn-secondary btn-sm" onclick="copyResponse('{name}-response-body')">
                                        Copy Response
                                    </button>
                                </div>
                                <div class="response-body">
                                    <pre id="{name}-response-body">// Response will appear here after testing</pre>
                                </div>
                            </div>
                            <div style="text-align: center; color: var(--gray-700); padding: 3rem;">
                                Fill out the form and click "Test Function Call" to see the response
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Examples Tab -->
                <div id="{name}-examples" class="tab-content">
                    {examples_html}
                </div>

                <!-- Data Flow Tab -->
                <div id="{name}-flow" class="tab-content">
                    <div class="card">
                        <h3 class="section-title">Sequence Diagram</h3>
                        <div class="diagram-container">
                            <pre class="mermaid">
{dataflow.get('sequence', 'graph TD\\nA[No diagram available]')}
                            </pre>
                        </div>
                    </div>

                    <div class="card">
                        <h3 class="section-title">Architecture Diagram</h3>
                        <div class="diagram-container">
                            <pre class="mermaid">
{dataflow.get('architecture', 'graph TD\\nA[No diagram available]')}
                            </pre>
                        </div>
                    </div>

                    <div class="card">
                        <h3 class="section-title">Error Handling</h3>
                        <div class="diagram-container">
                            <pre class="mermaid">
{dataflow.get('errorHandling', 'graph TD\\nA[No diagram available]')}
                            </pre>
                        </div>
                    </div>
                </div>
            </section>

            <script>
                async function testFunction_{name}(event) {{
                    event.preventDefault();

                    const form = event.target;
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());

                    // Remove empty fields
                    Object.keys(data).forEach(key => {{
                        if (data[key] === '') delete data[key];
                    }});

                    const responseContainer = document.getElementById('{name}-response');
                    const responseBody = document.getElementById('{name}-response-body');
                    const statusCode = document.getElementById('{name}-status-code');

                    // Show loading state
                    responseContainer.classList.add('show');
                    responseBody.innerHTML = '<div class="loading"></div> Testing function call...';

                    try {{
                        const response = await fetch('{endpoint.get("path", "")}', {{
                            method: '{endpoint.get("method", "POST")}',
                            headers: {{
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }},
                            body: JSON.stringify(data)
                        }});

                        const result = await response.json();

                        statusCode.textContent = response.status;
                        statusCode.parentElement.className = 'response-status ' +
                            (response.ok ? 'success' : 'error');

                        responseBody.textContent = JSON.stringify(result, null, 2);

                    }} catch (error) {{
                        statusCode.textContent = 'Error';
                        statusCode.parentElement.className = 'response-status error';
                        responseBody.textContent = JSON.stringify({{
                            success: false,
                            message: 'Network error: ' + error.message
                        }}, null, 2);
                    }}
                }}
            </script>
        '''

    def generate_statistics(self, functions: List[Dict]) -> Dict[str, Any]:
        """Calculate statistics for the overview"""
        total = len(functions)
        implemented = sum(1 for f in functions
                         if f.get('metadata', {}).get('status') == 'implemented')

        total_coverage = sum(
            f.get('testing', {}).get('unit', {}).get('coverage', 0)
            for f in functions
        )
        avg_coverage = total_coverage / total if total > 0 else 0

        return {
            'total': total,
            'implemented': implemented,
            'coverage': f"{avg_coverage:.1f}"
        }

    def generate(self) -> str:
        """Generate complete documentation HTML"""
        self.functions = self.load_functions()

        if not self.functions:
            print("No functions found. Cannot generate documentation.")
            return ""

        # Generate dynamic content
        nav_section = self.generate_nav_section(self.functions)
        feature_matrix = self.generate_feature_matrix_rows(self.functions)
        stats = self.generate_statistics(self.functions)

        # Generate function sections
        function_sections = '\n'.join([
            self.generate_function_section(func)
            for func in self.functions
        ])

        # Read base template
        template_path = self.output_dir / 'index.html'
        if not template_path.exists():
            print(f"Error: Base template not found at {template_path}")
            return ""

        with open(template_path, 'r') as f:
            html = f.read()

        # Replace placeholders (if you want to use templates)
        # For now, we'll just append the function sections before </main>

        # Insert generated content
        html = html.replace(
            '<!-- GENERATED_FUNCTIONS_PLACEHOLDER -->',
            function_sections
        )

        return html

    def save(self, output_file: Path):
        """Save generated documentation to file"""
        html = self.generate()
        if html:
            output_file.parent.mkdir(parents=True, exist_ok=True)
            with open(output_file, 'w') as f:
                f.write(html)
            print(f"Documentation generated: {output_file}")


def main():
    parser = argparse.ArgumentParser(
        description='Generate Voice AI Agent documentation from JSON definitions'
    )
    parser.add_argument(
        '--functions-dir',
        type=Path,
        default=Path('public/docs/voice-agent/functions'),
        help='Directory containing function JSON definitions'
    )
    parser.add_argument(
        '--output',
        type=Path,
        default=Path('public/docs/voice-agent'),
        help='Output directory for generated documentation'
    )
    parser.add_argument(
        '--watch',
        action='store_true',
        help='Watch for changes and regenerate'
    )

    args = parser.parse_args()

    # Get project root
    script_dir = Path(__file__).parent
    project_root = script_dir.parent

    functions_dir = project_root / args.functions_dir
    output_dir = project_root / args.output

    print(f"Functions directory: {functions_dir}")
    print(f"Output directory: {output_dir}")

    generator = VoiceDocsGenerator(functions_dir, output_dir)

    if args.watch:
        print("Watching for changes... (Press Ctrl+C to stop)")
        try:
            import time
            from watchdog.observers import Observer
            from watchdog.events import FileSystemEventHandler

            class ChangeHandler(FileSystemEventHandler):
                def on_modified(self, event):
                    if event.src_path.endswith('.json'):
                        print(f"\nDetected change in {event.src_path}")
                        generator.save(output_dir / 'index.html')

            event_handler = ChangeHandler()
            observer = Observer()
            observer.schedule(event_handler, str(functions_dir), recursive=False)
            observer.start()

            try:
                while True:
                    time.sleep(1)
            except KeyboardInterrupt:
                observer.stop()
            observer.join()

        except ImportError:
            print("Error: watchdog package not installed.")
            print("Install with: pip install watchdog")
            sys.exit(1)
    else:
        generator.save(output_dir / 'index-generated.html')
        print("\nDocumentation generation complete!")
        print(f"View at: {output_dir / 'index-generated.html'}")


if __name__ == '__main__':
    main()
