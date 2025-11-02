#!/bin/bash
# Show Docs Credentials Helper
# This script helps retrieve the current htpasswd credentials

set -e

echo "======================================"
echo "📋 Docs Credentials Helper"
echo "======================================"
echo ""

HTPASSWD_FILE="/etc/nginx/.htpasswd-staging"

# Check if file exists
if [ ! -f "$HTPASSWD_FILE" ]; then
    echo "❌ htpasswd file not found: $HTPASSWD_FILE"
    exit 1
fi

echo "📁 Found: $HTPASSWD_FILE"
echo ""

# Check if we can read it
if [ ! -r "$HTPASSWD_FILE" ]; then
    echo "⚠️  File exists but cannot be read with current user"
    echo "   Trying with sudo..."
    echo ""

    if sudo test -r "$HTPASSWD_FILE"; then
        echo "✅ sudo access available"
        echo ""
        echo "👤 Credentials:"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

        # Read and parse htpasswd file
        sudo cat "$HTPASSWD_FILE" | while IFS=: read -r username hash; do
            echo "Username: $username"
            echo "Password: (hashed with bcrypt/apr1)"
            echo ""
            echo "💡 Hash: ${hash:0:20}..."
            echo ""
            echo "⚠️  Note: Password is hashed and cannot be retrieved."
            echo "   You can only:"
            echo "   1) Try common passwords"
            echo "   2) Reset password with: sudo htpasswd -b $HTPASSWD_FILE $username NEW_PASSWORD"
            echo ""
        done

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    else
        echo "❌ Cannot access file even with sudo"
        echo "   You need root access to read this file"
        exit 1
    fi
else
    echo "✅ File is readable"
    echo ""
    echo "👤 Credentials:"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

    cat "$HTPASSWD_FILE" | while IFS=: read -r username hash; do
        echo "Username: $username"
        echo "Password: (hashed)"
        echo ""
        echo "Hash: ${hash:0:20}..."
        echo ""
    done

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
fi

echo ""
echo "🔧 To reset password:"
echo "   sudo htpasswd -b $HTPASSWD_FILE <username> <new_password>"
echo ""
echo "🔧 To create new user:"
echo "   sudo htpasswd -b $HTPASSWD_FILE <username> <password>"
echo ""
echo "🔄 After changes, reload NGINX:"
echo "   sudo systemctl reload nginx"
echo ""
