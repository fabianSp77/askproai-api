#!/bin/bash

# Build and deploy MkDocs documentation to public directory
# This makes it immediately available at https://api.askproai.de/documentation/

echo "🚀 Building and deploying documentation..."

# Install dependencies if not already installed
if ! command -v mkdocs &> /dev/null; then
    echo "📦 Installing MkDocs..."
    pip install mkdocs-material mkdocs-mermaid2-plugin mkdocs-git-revision-date-localized-plugin mkdocs-minify-plugin
fi

# Generate fresh documentation from code
echo "📝 Generating documentation from codebase..."
php artisan docs:generate 2>/dev/null || echo "⚠️  Artisan command not available, skipping..."

# Build MkDocs site
echo "🔨 Building MkDocs site..."
mkdocs build

# Deploy to public directory
echo "📤 Deploying to public directory..."
rm -rf public/documentation
cp -r site public/documentation

# Set permissions
chown -R www-data:www-data public/documentation
chmod -R 755 public/documentation

# Create index redirect in public/docs
cat > public/docs/mkdocs.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>AskProAI Documentation</title>
    <meta http-equiv="refresh" content="0; url=/documentation/">
</head>
<body>
    <p>Redirecting to <a href="/documentation/">MkDocs Documentation</a>...</p>
</body>
</html>
EOF

echo "✅ Documentation deployed successfully!"
echo ""
echo "📚 Access your documentation at:"
echo "   https://api.askproai.de/documentation/"
echo ""
echo "🔗 Also available:"
echo "   - Consistency Dashboard: https://api.askproai.de/docs/consistency-dashboard.html"
echo "   - MCP Complete Guide: https://api.askproai.de/docs/comprehensive-dashboard.html"
echo "   - MkDocs Version: https://api.askproai.de/documentation/"

# Make script executable
chmod +x "$0"