#!/usr/bin/env bash
# Fix em-dash-instead-of-quote corruption in StoreController.php
# (Deployed copy only — the local file is already correct.)
#
# Usage:  bash fix_emdash_quotes.sh [/path/to/StoreController.php]
# Default target: app/Controllers/StoreController.php

set -euo pipefail

TARGET="${1:-app/Controllers/StoreController.php}"

if [ ! -f "$TARGET" ]; then
  echo "File not found: $TARGET" >&2
  exit 1
fi

echo "Checking: $TARGET"

# Show the corrupted lines before fixing (UTF-8 em dash = e2 80 94 in UTF-8 bytes)
echo "--- lines containing em-dash before fix ---"
grep -n "—" "$TARGET" || echo "(none found — file may already be fixed)"

# In-place replacements, UTF-8 safe
perl -CSD -i -pe 's/—both/"both"/g' "$TARGET"
perl -CSD -i -pe 's/—approved/"approved"/g' "$TARGET"

echo "--- em-dash occurrences after fix ---"
if grep -n "—" "$TARGET" >/dev/null 2>&1; then
  echo "WARNING: em-dashes still present:" >&2
  grep -n "—" "$TARGET" >&2
else
  echo "No em-dashes remaining."
fi

echo "Done. Review the file, then redeploy."
