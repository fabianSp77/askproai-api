#!/bin/bash
SIZE=$(wc -c < CLAUDE.md 2>/dev/null || echo 0)
LIMIT=40000

echo "📏 CLAUDE.md Size Check"
echo "Current: $SIZE chars"
echo "Limit: $LIMIT chars"

if [ $SIZE -gt $LIMIT ]; then
    echo "⚠️  OVER LIMIT by $(($SIZE - $LIMIT)) chars"
    exit 1
else
    echo "✅ OK - $(($LIMIT - $SIZE)) chars remaining"
fi
