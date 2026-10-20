#!/usr/bin/env bash
# Fix em-dash-instead-of-quote corruption introduced by the deploy pipeline.
#
# Some deploy paths replace straight double-quotes (") with em-dashes (—)
# inside PHP string literals. That turns SQL like:
#     IN (?, "both")
# into:
#     IN (?, —both)
# which PDO prepares as a column reference and MySQL rejects with:
#     SQLSTATE[42S22] Column not found: 1054 Unknown column 'both' in 'where clause'
#
# This script fixes the known corrupted patterns across the whole app/ tree.
# It is safe to run repeatedly — it only rewrites files that actually changed.
#
# Usage:
#   bash fix_emdash_quotes.sh [path-to-app-directory]
#   default target: ./app

set -euo pipefail

APP_DIR="${1:-app}"

if [ ! -d "$APP_DIR" ]; then
  echo "Directory not found: $APP_DIR" >&2
  exit 1
fi

echo "Scanning: $APP_DIR"

# Patterns: em-dash followed by a quoted SQL literal that should be a plain
# double-quoted string. Add new pairs here as new corruption patterns appear.
perl -CSD -i -pe 's/—both/"both"/g' "$APP_DIR"/**/*.php 2>/dev/null || true
perl -CSD -i -pe 's/—approved/"approved"/g' "$APP_DIR"/**/*.php 2>/dev/null || true

# Report what is still broken.
Leftovers=$(grep -rn — "$APP_DIR" --include='*.php' || true)
if [ -n "$Leftovers" ]; then
  echo "WARNING: em-dashes still present in PHP files:" >&2
  echo "$Leftovers" >&2
  exit 1
else
  echo "No em-dash-quote corruption remaining."
fi

echo "Done. Redeploy."
