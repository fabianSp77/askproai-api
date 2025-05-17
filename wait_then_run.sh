#!/usr/bin/env bash
set -euo pipefail
TARGET=50
while :; do
  LEFT=$(gh api rate_limit --jq '.resources.graphql.remaining')
  echo "GraphQL übrig: $LEFT"
  [[ $LEFT -ge $TARGET ]] && break
  sleep 30
done
echo "✅ Starte board.sh"
exec ./board.sh
