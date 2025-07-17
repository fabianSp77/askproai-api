# Documentation Tools Guide

> 📋 **Purpose**: Essential tools for creating and maintaining documentation  
> 📅 **Last Updated**: 2025-01-10  
> 🛠️ **Tool Categories**: Writing, Validation, Generation, Publishing

## Writing Tools

### Markdown Editors

#### VS Code + Extensions
```bash
# Recommended extensions
code --install-extension yzhang.markdown-all-in-one
code --install-extension davidanson.vscode-markdownlint
code --install-extension bierner.markdown-preview-github-styles
code --install-extension bierner.markdown-mermaid
```

**Features:**
- Live preview
- TOC generation
- Snippet support
- Mermaid diagrams
- Linting

#### Obsidian
- **Use Case**: Knowledge base management
- **Features**: Graph view, backlinks, plugins
- **URL**: https://obsidian.md

#### Typora
- **Use Case**: WYSIWYG markdown editing
- **Features**: Live rendering, export options
- **URL**: https://typora.io

### Grammar & Style

#### Vale
```bash
# Install
brew install vale  # macOS
# or
wget https://github.com/errata-ai/vale/releases/download/v2.20.0/vale_2.20.0_Linux_64-bit.tar.gz

# Configuration (.vale.ini)
StylesPath = styles
MinAlertLevel = suggestion

[*.md]
BasedOnStyles = Microsoft, write-good
```

#### Grammarly CLI
```bash
npm install -g @grammarly/sdk

# Check file
grammarly check docs/guide.md
```

#### Hemingway Editor
- **Use Case**: Readability improvement
- **Features**: Grade level, sentence complexity
- **URL**: https://hemingwayapp.com

## Validation Tools

### Link Checkers

#### markdown-link-check
```bash
# Install
npm install -g markdown-link-check

# Usage
markdown-link-check README.md

# With config
markdown-link-check -c .linkcheck.json docs/**/*.md

# Config file (.linkcheck.json)
{
  "ignorePatterns": [
    {"pattern": "^http://localhost"},
    {"pattern": "^https://private.example.com"}
  ],
  "timeout": "30s",
  "retryOn429": true
}
```

#### linkinator
```bash
# Install
npm install -g linkinator

# Check directory
linkinator docs/ --recurse

# Check deployed site
linkinator https://docs.askproai.de --recurse
```

### Spell Checkers

#### cspell
```bash
# Install
npm install -g cspell

# Usage
cspell "docs/**/*.md"

# Configuration (cspell.json)
{
  "language": "en",
  "words": ["askproai", "laravel", "filament"],
  "ignorePaths": ["node_modules", "vendor"]
}
```

#### aspell
```bash
# Install
apt-get install aspell

# Check file
aspell check docs/guide.md

# Batch check
find docs -name "*.md" -exec aspell check {} \;
```

### Code Validators

#### Code Block Syntax
```bash
# Script to validate code blocks
#!/bin/bash
# validate-code-blocks.sh

find docs -name "*.md" -print0 | while IFS= read -r -d '' file; do
  echo "Checking $file..."
  
  # Extract PHP code blocks
  awk '/```php/{flag=1; next} /```/{flag=0} flag' "$file" > temp.php
  php -l temp.php
  
  # Extract JavaScript blocks
  awk '/```javascript/{flag=1; next} /```/{flag=0} flag' "$file" > temp.js
  node --check temp.js
done
```

## Generation Tools

### Documentation Generators

#### API Documentation

**Laravel API Doc Generator**
```bash
# Install
composer require --dev mpociot/laravel-apidoc-generator

# Generate
php artisan apidoc:generate

# Configuration
return [
    'output' => 'public/docs/api',
    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/*'],
            ],
        ],
    ],
];
```

**Swagger/OpenAPI**
```php
// Using Laravel Swagger
composer require darkaonline/l5-swagger

// Generate
php artisan l5-swagger:generate
```

#### Code Documentation

**PHPDocumentor**
```bash
# Install
composer require --dev phpdocumentor/phpdocumentor

# Generate
vendor/bin/phpdoc -d app -t docs/api/php
```

**JSDoc**
```bash
# Install
npm install -g jsdoc

# Generate
jsdoc -c jsdoc.json -r resources/js -d docs/api/js
```

### Diagram Tools

#### Mermaid CLI
```bash
# Install
npm install -g @mermaid-js/mermaid-cli

# Convert to image
mmdc -i diagram.mmd -o diagram.png

# Batch convert
find docs -name "*.mmd" -exec mmdc -i {} -o {}.png \;
```

#### PlantUML
```bash
# Install
brew install plantuml  # macOS

# Generate diagram
plantuml sequence.puml

# Watch mode
plantuml -gui
```

#### Draw.io CLI
```bash
# Export diagrams
drawio -x -f png -o output.png diagram.drawio

# Batch export
for file in docs/diagrams/*.drawio; do
  drawio -x -f png -o "${file%.drawio}.png" "$file"
done
```

### Screenshot Tools

#### Playwright
```javascript
// capture-screenshots.js
const { chromium } = require('playwright');

async function captureScreenshots() {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  // Login page
  await page.goto('http://localhost:8000/login');
  await page.screenshot({ path: 'docs/images/login.png' });
  
  // Dashboard
  await page.fill('#email', 'demo@example.com');
  await page.fill('#password', 'password');
  await page.click('button[type="submit"]');
  await page.waitForNavigation();
  await page.screenshot({ path: 'docs/images/dashboard.png' });
  
  await browser.close();
}

captureScreenshots();
```

## Publishing Tools

### Static Site Generators

#### MkDocs
```bash
# Install
pip install mkdocs mkdocs-material

# Initialize
mkdocs new docs-site
cd docs-site

# Configuration (mkdocs.yml)
site_name: AskProAI Documentation
theme:
  name: material
  features:
    - navigation.tabs
    - search.highlight
  palette:
    primary: blue
    accent: indigo

nav:
  - Home: index.md
  - Getting Started:
    - Installation: getting-started/installation.md
    - Configuration: getting-started/configuration.md
  - API Reference:
    - Authentication: api/auth.md
    - Endpoints: api/endpoints.md

# Build
mkdocs build

# Serve locally
mkdocs serve
```

#### Docusaurus
```bash
# Create project
npx create-docusaurus@latest docs-site classic

# Start development
npm start

# Build
npm run build
```

#### VuePress
```bash
# Install
npm install -g vuepress

# Create structure
mkdir docs
echo '# Hello VuePress' > docs/README.md

# Config (.vuepress/config.js)
module.exports = {
  title: 'AskProAI Docs',
  description: 'Documentation',
  themeConfig: {
    nav: [
      { text: 'Guide', link: '/guide/' },
      { text: 'API', link: '/api/' }
    ],
    sidebar: 'auto'
  }
}
```

### Deployment Tools

#### GitHub Pages
```yaml
# .github/workflows/deploy-docs.yml
name: Deploy Documentation

on:
  push:
    branches: [main]
    paths:
      - 'docs/**'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Python
        uses: actions/setup-python@v4
        with:
          python-version: '3.x'
          
      - name: Install dependencies
        run: |
          pip install mkdocs mkdocs-material
          
      - name: Build docs
        run: mkdocs build
        
      - name: Deploy to GitHub Pages
        uses: peaceiris/actions-gh-pages@v3
        with:
          github_token: ${{ secrets.GITHUB_TOKEN }}
          publish_dir: ./site
```

#### Netlify
```toml
# netlify.toml
[build]
  command = "mkdocs build"
  publish = "site/"

[build.environment]
  PYTHON_VERSION = "3.8"
```

## Search & Analytics

### Documentation Search

#### Algolia DocSearch
```javascript
// docusaurus.config.js
module.exports = {
  themeConfig: {
    algolia: {
      appId: 'YOUR_APP_ID',
      apiKey: 'YOUR_SEARCH_API_KEY',
      indexName: 'askproai_docs',
    },
  },
};
```

#### Local Search
```yaml
# mkdocs.yml
plugins:
  - search:
      lang: en
      separator: '[\s\-\.]+'
```

### Analytics

#### Google Analytics
```html
<!-- In documentation template -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_MEASUREMENT_ID');
</script>
```

#### Plausible
```html
<script defer data-domain="docs.askproai.de" src="https://plausible.io/js/script.js"></script>
```

## Automation Scripts

### Documentation Pipeline
```bash
#!/bin/bash
# docs-pipeline.sh

echo "🔍 Running documentation pipeline..."

# 1. Lint markdown
echo "📝 Linting markdown..."
markdownlint docs/**/*.md

# 2. Check spelling
echo "🔤 Checking spelling..."
cspell "docs/**/*.md"

# 3. Validate links
echo "🔗 Validating links..."
markdown-link-check -c .linkcheck.json docs/**/*.md

# 4. Check code examples
echo "💻 Validating code examples..."
./scripts/validate-examples.sh

# 5. Generate API docs
echo "🚀 Generating API documentation..."
php artisan apidoc:generate

# 6. Build site
echo "🏗️ Building documentation site..."
mkdocs build

# 7. Run tests
echo "🧪 Running documentation tests..."
npm run test:docs

echo "✅ Documentation pipeline complete!"
```

### Pre-commit Hook
```bash
#!/bin/bash
# .git/hooks/pre-commit

# Check if docs are modified
if git diff --cached --name-only | grep -q "^docs/"; then
  echo "📚 Documentation changes detected, running checks..."
  
  # Run linter
  if ! markdownlint $(git diff --cached --name-only | grep "\.md$"); then
    echo "❌ Markdown linting failed"
    exit 1
  fi
  
  # Check spelling
  if ! cspell $(git diff --cached --name-only | grep "\.md$"); then
    echo "❌ Spelling check failed"
    exit 1
  fi
  
  echo "✅ Documentation checks passed"
fi
```

## VS Code Configuration

### Workspace Settings
```json
{
  // .vscode/settings.json
  "editor.formatOnSave": true,
  "editor.rulers": [80, 120],
  "[markdown]": {
    "editor.wordWrap": "wordWrapColumn",
    "editor.wordWrapColumn": 80,
    "editor.quickSuggestions": true
  },
  "markdownlint.config": {
    "MD013": false,
    "MD033": false
  },
  "cSpell.words": [
    "askproai",
    "laravel",
    "filament",
    "livewire"
  ],
  "vale.core.useCLI": true,
  "grammarly.files.include": ["**/*.md"],
  "markdown.preview.breaks": true
}
```

### Recommended Extensions
```json
{
  // .vscode/extensions.json
  "recommendations": [
    "yzhang.markdown-all-in-one",
    "davidanson.vscode-markdownlint",
    "streetsidesoftware.code-spell-checker",
    "errata-ai.vale-server",
    "bierner.markdown-mermaid",
    "bierner.markdown-preview-github-styles",
    "hediet.vscode-drawio",
    "grammarly.grammarly"
  ]
}
```

## Documentation Testing

### Broken Link Testing
```javascript
// test/docs.test.js
const { test } = require('@playwright/test');

test.describe('Documentation', () => {
  test('all internal links work', async ({ page }) => {
    await page.goto('http://localhost:3000');
    
    const links = await page.$$eval('a[href^="/"]', links => 
      links.map(link => link.href)
    );
    
    for (const link of links) {
      const response = await page.goto(link);
      expect(response.status()).toBeLessThan(400);
    }
  });
});
```

### Content Testing
```bash
#!/bin/bash
# test-docs-content.sh

# Check for required sections
for file in docs/api/*.md; do
  if ! grep -q "## Authentication" "$file"; then
    echo "Missing Authentication section in $file"
    exit 1
  fi
done

# Check for examples
for file in docs/guides/*.md; do
  if ! grep -q '```' "$file"; then
    echo "Missing code examples in $file"
    exit 1
  fi
done
```

---

> 🔄 **Auto-Updated**: This documentation is automatically checked for updates. Last verification: 2025-01-10