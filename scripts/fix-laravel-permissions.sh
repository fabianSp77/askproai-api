#!/bin/bash

# Laravel Permission Fix Script
# Ensures all cache directories have correct ownership and permissions

echo "$(date): Fixing Laravel permissions..."

# Set the Laravel directory
LARAVEL_DIR="/var/www/api-gateway"

# Clear all caches first AS www-data
echo "Clearing Laravel caches..."
cd $LARAVEL_DIR
sudo -u www-data php artisan cache:clear 2>/dev/null || true
sudo -u www-data php artisan config:clear 2>/dev/null || true
sudo -u www-data php artisan route:clear 2>/dev/null || true
sudo -u www-data php artisan view:clear 2>/dev/null || true

# Remove compiled views to force regeneration
echo "Removing compiled views..."
rm -rf $LARAVEL_DIR/storage/framework/views/*.php
rm -rf $LARAVEL_DIR/storage/framework/cache/data/*
rm -rf $LARAVEL_DIR/bootstrap/cache/*.php

# Fix ownership for all storage directories
echo "Fixing ownership..."
chown -R www-data:www-data $LARAVEL_DIR/storage
chown -R www-data:www-data $LARAVEL_DIR/bootstrap/cache

# Set proper permissions
echo "Setting permissions..."
chmod -R 775 $LARAVEL_DIR/storage
chmod -R 775 $LARAVEL_DIR/bootstrap/cache

# Ensure directories exist with correct permissions
echo "Ensuring directory structure..."
mkdir -p $LARAVEL_DIR/storage/framework/{sessions,views,cache/data}
mkdir -p $LARAVEL_DIR/storage/logs
chown -R www-data:www-data $LARAVEL_DIR/storage/framework
chmod -R 775 $LARAVEL_DIR/storage/framework

# Set setgid bit to ensure new files inherit group
find $LARAVEL_DIR/storage -type d -exec chmod g+s {} \;
find $LARAVEL_DIR/bootstrap/cache -type d -exec chmod g+s {} \;

# Pre-compile views as www-data
echo "Pre-compiling views..."
cd $LARAVEL_DIR
sudo -u www-data php artisan view:cache 2>/dev/null || true

echo "$(date): Laravel permissions fixed successfully!"