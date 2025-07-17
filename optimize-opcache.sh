#!/bin/bash
# Optimize PHP OpCache for production
# Created: 2025-01-15

echo "🔧 Optimizing PHP OpCache..."

# Create optimized config
cat > /tmp/99-opcache-production.ini << 'EOF'
; OpCache Production Settings
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.max_wasted_percentage=10
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.fast_shutdown=1
opcache.enable_file_override=1
opcache.huge_code_pages=1
opcache.file_cache=/tmp/opcache
EOF

# Copy to PHP config
sudo cp /tmp/99-opcache-production.ini /etc/php/8.3/fpm/conf.d/99-opcache-production.ini
sudo cp /tmp/99-opcache-production.ini /etc/php/8.3/cli/conf.d/99-opcache-production.ini

# Create opcache file cache directory
sudo mkdir -p /tmp/opcache
sudo chown www-data:www-data /tmp/opcache

echo "✅ OpCache configuration updated"
echo ""
echo "⚠️  IMPORTANT: Restart PHP-FPM to apply changes:"
echo "sudo systemctl restart php8.3-fpm"
echo ""
echo "Current settings will change to:"
echo "- Memory: 128MB → 256MB"
echo "- Max files: 10,000 → 20,000"
echo "- Timestamp validation: Disabled (faster)"
echo "- File cache: Enabled"