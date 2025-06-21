<?php

namespace App\Services\KnowledgeBase;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;

class MarkdownEnhancer
{
    /**
     * Enhance HTML with interactive features
     */
    public function enhance(string $html): string
    {
        // Add copy button to code blocks
        $html = $this->addCodeCopyButtons($html);
        
        // Convert mermaid blocks to diagrams
        $html = $this->convertMermaidDiagrams($html);
        
        // Add executable code blocks
        $html = $this->addExecutableCodeBlocks($html);
        
        // Add collapsible sections
        $html = $this->addCollapsibleSections($html);
        
        // Add tabbed content
        $html = $this->addTabbedContent($html);
        
        // Enhance tables
        $html = $this->enhanceTables($html);
        
        // Add anchor links to headers
        $html = $this->addHeaderAnchors($html);
        
        // Add info boxes
        $html = $this->addInfoBoxes($html);
        
        return $html;
    }
    
    /**
     * Add copy buttons to code blocks
     */
    protected function addCodeCopyButtons(string $html): string
    {
        return preg_replace_callback(
            '/<pre><code class="language-(\w+)">(.*?)<\/code><\/pre>/s',
            function ($matches) {
                $language = $matches[1];
                $code = $matches[2];
                $id = 'code-' . Str::random(8);
                
                return <<<HTML
                <div class="code-block-wrapper">
                    <div class="code-block-header">
                        <span class="code-language">{$language}</span>
                        <button class="copy-button" data-target="{$id}" onclick="copyCode('{$id}')">
                            <svg class="copy-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Copy
                        </button>
                    </div>
                    <pre><code id="{$id}" class="language-{$language}">{$code}</code></pre>
                </div>
                HTML;
            },
            $html
        );
    }
    
    /**
     * Convert mermaid code blocks to diagrams
     */
    protected function convertMermaidDiagrams(string $html): string
    {
        return preg_replace_callback(
            '/<pre><code class="language-mermaid">(.*?)<\/code><\/pre>/s',
            function ($matches) {
                $mermaidCode = htmlspecialchars_decode($matches[1]);
                $id = 'mermaid-' . Str::random(8);
                
                return <<<HTML
                <div class="mermaid-wrapper">
                    <div class="mermaid" id="{$id}">
                        {$mermaidCode}
                    </div>
                    <button class="mermaid-fullscreen" onclick="toggleMermaidFullscreen('{$id}')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
                        </svg>
                    </button>
                </div>
                HTML;
            },
            $html
        );
    }
    
    /**
     * Add executable code blocks
     */
    protected function addExecutableCodeBlocks(string $html): string
    {
        return preg_replace_callback(
            '/<pre><code class="language-(bash|shell|curl)">(.*?)<\/code><\/pre>/s',
            function ($matches) {
                $language = $matches[1];
                $code = htmlspecialchars_decode($matches[2]);
                
                // Check if it's an API call
                if (strpos($code, 'curl') !== false || strpos($code, 'http') !== false) {
                    $id = 'exec-' . Str::random(8);
                    
                    return <<<HTML
                    <div class="executable-code-block">
                        <div class="code-block-header">
                            <span class="code-language">{$language}</span>
                            <div class="code-actions">
                                <button class="copy-button" onclick="copyCode('{$id}')">Copy</button>
                                <button class="run-button" onclick="executeCode('{$id}', '{$language}')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                    Run
                                </button>
                            </div>
                        </div>
                        <pre><code id="{$id}" class="language-{$language}">{$code}</code></pre>
                        <div class="execution-result" id="result-{$id}" style="display: none;">
                            <div class="result-header">Output:</div>
                            <pre class="result-content"></pre>
                        </div>
                    </div>
                    HTML;
                }
                
                // Return regular code block
                return $matches[0];
            },
            $html
        );
    }
    
    /**
     * Add collapsible sections
     */
    protected function addCollapsibleSections(string $html): string
    {
        // Convert <details> tags or special markers
        return preg_replace_callback(
            '/<!--\s*collapse:\s*(.+?)\s*-->(.*?)<!--\s*\/collapse\s*-->/s',
            function ($matches) {
                $title = $matches[1];
                $content = $matches[2];
                $id = 'collapse-' . Str::random(8);
                
                return <<<HTML
                <details class="collapsible-section">
                    <summary>{$title}</summary>
                    <div class="collapsible-content">
                        {$content}
                    </div>
                </details>
                HTML;
            },
            $html
        );
    }
    
    /**
     * Add tabbed content
     */
    protected function addTabbedContent(string $html): string
    {
        return preg_replace_callback(
            '/<!--\s*tabs\s*-->(.*?)<!--\s*\/tabs\s*-->/s',
            function ($matches) {
                $content = $matches[1];
                $tabId = 'tabs-' . Str::random(8);
                
                // Parse individual tabs
                preg_match_all('/<!--\s*tab:\s*(.+?)\s*-->(.*?)(?=<!--\s*tab:|<!--\s*\/tabs)/s', $content, $tabs, PREG_SET_ORDER);
                
                if (empty($tabs)) {
                    return $matches[0];
                }
                
                $tabHeaders = '';
                $tabContents = '';
                
                foreach ($tabs as $index => $tab) {
                    $title = $tab[1];
                    $tabContent = $tab[2];
                    $active = $index === 0 ? 'active' : '';
                    
                    $tabHeaders .= <<<HTML
                    <button class="tab-header {$active}" onclick="showTab('{$tabId}', {$index})">
                        {$title}
                    </button>
                    HTML;
                    
                    $tabContents .= <<<HTML
                    <div class="tab-content {$active}" data-tab-index="{$index}">
                        {$tabContent}
                    </div>
                    HTML;
                }
                
                return <<<HTML
                <div class="tabs-container" id="{$tabId}">
                    <div class="tab-headers">
                        {$tabHeaders}
                    </div>
                    <div class="tab-contents">
                        {$tabContents}
                    </div>
                </div>
                HTML;
            },
            $html
        );
    }
    
    /**
     * Enhance tables with sorting and filtering
     */
    protected function enhanceTables(string $html): string
    {
        return preg_replace_callback(
            '/<table>(.*?)<\/table>/s',
            function ($matches) {
                $tableContent = $matches[1];
                $tableId = 'table-' . Str::random(8);
                
                // Check if table has enough rows to warrant enhancement
                $rowCount = substr_count($tableContent, '<tr>');
                if ($rowCount < 5) {
                    return $matches[0];
                }
                
                return <<<HTML
                <div class="enhanced-table-wrapper">
                    <div class="table-controls">
                        <input type="text" class="table-search" placeholder="Search table..." onkeyup="filterTable('{$tableId}', this.value)">
                    </div>
                    <table id="{$tableId}" class="enhanced-table sortable">
                        {$tableContent}
                    </table>
                </div>
                HTML;
            },
            $html
        );
    }
    
    /**
     * Add anchor links to headers
     */
    protected function addHeaderAnchors(string $html): string
    {
        return preg_replace_callback(
            '/<h([2-6])>(.*?)<\/h\1>/i',
            function ($matches) {
                $level = $matches[1];
                $text = strip_tags($matches[2]);
                $id = Str::slug($text);
                
                return <<<HTML
                <h{$level} id="{$id}">
                    {$matches[2]}
                    <a href="#{$id}" class="header-anchor" aria-label="Permalink">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                    </a>
                </h{$level}>
                HTML;
            },
            $html
        );
    }
    
    /**
     * Add info boxes for notes, warnings, etc.
     */
    protected function addInfoBoxes(string $html): string
    {
        $patterns = [
            '/(?:^|\n)&gt;\s*\[!NOTE\](.*?)(?=\n(?!&gt;)|$)/s' => 'note',
            '/(?:^|\n)&gt;\s*\[!TIP\](.*?)(?=\n(?!&gt;)|$)/s' => 'tip',
            '/(?:^|\n)&gt;\s*\[!IMPORTANT\](.*?)(?=\n(?!&gt;)|$)/s' => 'important',
            '/(?:^|\n)&gt;\s*\[!WARNING\](.*?)(?=\n(?!&gt;)|$)/s' => 'warning',
            '/(?:^|\n)&gt;\s*\[!CAUTION\](.*?)(?=\n(?!&gt;)|$)/s' => 'caution',
        ];
        
        foreach ($patterns as $pattern => $type) {
            $html = preg_replace_callback(
                $pattern,
                function ($matches) use ($type) {
                    $content = trim(preg_replace('/^&gt;\s*/m', '', $matches[1]));
                    
                    $icons = [
                        'note' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
                        'tip' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
                        'important' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
                        'warning' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
                        'caution' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
                    ];
                    
                    $icon = $icons[$type] ?? '';
                    $title = ucfirst($type);
                    
                    return <<<HTML
                    <div class="info-box info-box-{$type}">
                        <div class="info-box-header">
                            {$icon}
                            <span>{$title}</span>
                        </div>
                        <div class="info-box-content">
                            {$content}
                        </div>
                    </div>
                    HTML;
                },
                $html
            );
        }
        
        return $html;
    }
    
    /**
     * Get JavaScript for interactive features
     */
    public static function getJavaScript(): string
    {
        return <<<'JS'
        // Copy code to clipboard
        function copyCode(id) {
            const code = document.getElementById(id).textContent;
            navigator.clipboard.writeText(code).then(() => {
                const button = document.querySelector(`[data-target="${id}"]`);
                const originalText = button.innerHTML;
                button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Copied!';
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            });
        }
        
        // Execute code block
        async function executeCode(id, language) {
            const code = document.getElementById(id).textContent;
            const resultDiv = document.getElementById(`result-${id}`);
            const resultContent = resultDiv.querySelector('.result-content');
            
            resultDiv.style.display = 'block';
            resultContent.innerHTML = '<div class="loading">Executing...</div>';
            
            try {
                const response = await fetch('/api/knowledge/execute-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ code, language })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultContent.textContent = data.output;
                } else {
                    resultContent.innerHTML = `<span class="error">Error: ${data.error}</span>`;
                }
            } catch (error) {
                resultContent.innerHTML = `<span class="error">Failed to execute: ${error.message}</span>`;
            }
        }
        
        // Toggle Mermaid diagram fullscreen
        function toggleMermaidFullscreen(id) {
            const diagram = document.getElementById(id);
            if (diagram.classList.contains('fullscreen')) {
                diagram.classList.remove('fullscreen');
            } else {
                diagram.classList.add('fullscreen');
            }
        }
        
        // Show tab
        function showTab(containerId, tabIndex) {
            const container = document.getElementById(containerId);
            const headers = container.querySelectorAll('.tab-header');
            const contents = container.querySelectorAll('.tab-content');
            
            headers.forEach((header, index) => {
                header.classList.toggle('active', index === tabIndex);
            });
            
            contents.forEach((content, index) => {
                content.classList.toggle('active', index === tabIndex);
            });
        }
        
        // Filter table
        function filterTable(tableId, searchValue) {
            const table = document.getElementById(tableId);
            const rows = table.querySelectorAll('tbody tr');
            const search = searchValue.toLowerCase();
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        }
        
        // Initialize Mermaid diagrams
        if (typeof mermaid !== 'undefined') {
            mermaid.initialize({ startOnLoad: true });
        }
        
        // Initialize sortable tables
        document.querySelectorAll('table.sortable').forEach(table => {
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => sortTable(table, index));
            });
        });
        
        // Sort table
        function sortTable(table, columnIndex) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent;
                const bText = b.cells[columnIndex].textContent;
                
                // Try to parse as numbers
                const aNum = parseFloat(aText);
                const bNum = parseFloat(bText);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return aNum - bNum;
                }
                
                return aText.localeCompare(bText);
            });
            
            // Toggle sort direction
            if (table.dataset.sortColumn === columnIndex.toString() && table.dataset.sortDirection === 'asc') {
                rows.reverse();
                table.dataset.sortDirection = 'desc';
            } else {
                table.dataset.sortDirection = 'asc';
            }
            
            table.dataset.sortColumn = columnIndex;
            
            // Re-append rows
            rows.forEach(row => tbody.appendChild(row));
        }
        JS;
    }
    
    /**
     * Get CSS for enhanced markdown
     */
    public static function getCSS(): string
    {
        return <<<'CSS'
        /* Code block wrapper */
        .code-block-wrapper {
            position: relative;
            margin: 1rem 0;
            border-radius: 0.5rem;
            overflow: hidden;
            background: #1e293b;
        }
        
        .code-block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 1rem;
            background: #0f172a;
            border-bottom: 1px solid #334155;
        }
        
        .code-language {
            font-size: 0.875rem;
            color: #94a3b8;
            font-weight: 500;
        }
        
        .copy-button, .run-button {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            background: #334155;
            color: #e2e8f0;
            border: none;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .copy-button:hover, .run-button:hover {
            background: #475569;
        }
        
        /* Mermaid diagrams */
        .mermaid-wrapper {
            position: relative;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
        }
        
        .mermaid-fullscreen {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            padding: 0.25rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.25rem;
            cursor: pointer;
        }
        
        .mermaid.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: white;
            z-index: 9999;
            padding: 2rem;
            overflow: auto;
        }
        
        /* Executable code blocks */
        .executable-code-block {
            margin: 1rem 0;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .code-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .execution-result {
            background: #f1f5f9;
            border-top: 1px solid #e2e8f0;
            padding: 1rem;
        }
        
        .result-header {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #475569;
        }
        
        .result-content {
            background: white;
            padding: 0.75rem;
            border-radius: 0.25rem;
            font-family: monospace;
            font-size: 0.875rem;
            overflow-x: auto;
        }
        
        .result-content .error {
            color: #dc2626;
        }
        
        .loading {
            color: #6b7280;
            font-style: italic;
        }
        
        /* Collapsible sections */
        .collapsible-section {
            margin: 1rem 0;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .collapsible-section summary {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            cursor: pointer;
            font-weight: 600;
            user-select: none;
        }
        
        .collapsible-section summary:hover {
            background: #f1f5f9;
        }
        
        .collapsible-content {
            padding: 1rem;
        }
        
        /* Tabs */
        .tabs-container {
            margin: 1rem 0;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .tab-headers {
            display: flex;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .tab-header {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            font-weight: 500;
            cursor: pointer;
            position: relative;
            color: #64748b;
            transition: all 0.2s;
        }
        
        .tab-header:hover {
            color: #334155;
        }
        
        .tab-header.active {
            color: #0ea5e9;
        }
        
        .tab-header.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: #0ea5e9;
        }
        
        .tab-contents {
            padding: 1rem;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Enhanced tables */
        .enhanced-table-wrapper {
            margin: 1rem 0;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .table-controls {
            padding: 0.75rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table-search {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
        
        .enhanced-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .enhanced-table th {
            background: #f1f5f9;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .enhanced-table.sortable th {
            cursor: pointer;
            user-select: none;
        }
        
        .enhanced-table.sortable th:hover {
            background: #e2e8f0;
        }
        
        .enhanced-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .enhanced-table tr:hover {
            background: #f8fafc;
        }
        
        /* Header anchors */
        h2, h3, h4, h5, h6 {
            position: relative;
        }
        
        .header-anchor {
            position: absolute;
            left: -1.5rem;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: opacity 0.2s;
            color: #94a3b8;
        }
        
        h2:hover .header-anchor,
        h3:hover .header-anchor,
        h4:hover .header-anchor,
        h5:hover .header-anchor,
        h6:hover .header-anchor {
            opacity: 1;
        }
        
        /* Info boxes */
        .info-box {
            margin: 1rem 0;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .info-box-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            font-weight: 600;
        }
        
        .info-box-content {
            padding: 0 1rem 0.75rem 2.5rem;
        }
        
        .info-box-note {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .info-box-note .info-box-header {
            background: #bfdbfe;
        }
        
        .info-box-tip {
            background: #d1fae5;
            color: #065f46;
        }
        
        .info-box-tip .info-box-header {
            background: #a7f3d0;
        }
        
        .info-box-important {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .info-box-important .info-box-header {
            background: #c7d2fe;
        }
        
        .info-box-warning {
            background: #fed7aa;
            color: #92400e;
        }
        
        .info-box-warning .info-box-header {
            background: #fdba74;
        }
        
        .info-box-caution {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .info-box-caution .info-box-header {
            background: #fecaca;
        }
        CSS;
    }
}