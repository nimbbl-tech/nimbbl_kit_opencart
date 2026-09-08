#!/usr/bin/env bash
# build.sh — Build nimbbl-opencart4-{VERSION}.zip for distribution
#
# Usage:
#   ./src/build.sh [VERSION]
#
# If VERSION is omitted it reads from install.json.
# Output: dist/nimbbl-opencart4-{VERSION}.zip
#
# Requirements: composer, zip, jq (optional — used to parse install.json)
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_DIR="$SCRIPT_DIR/opencart4"
DIST_DIR="$SCRIPT_DIR/../dist"

# ── Version ──────────────────────────────────────────────────────────────────
if [[ -n "${1:-}" ]]; then
  VERSION="$1"
elif command -v jq &>/dev/null; then
  VERSION="$(jq -r .version "$SRC_DIR/install.json")"
else
  VERSION="$(grep -o '"version": *"[^"]*"' "$SRC_DIR/install.json" | head -1 | sed 's/.*"\([0-9][^"]*\)".*/\1/')"
fi

if [[ -z "$VERSION" ]]; then
  echo "ERROR: could not determine version" >&2
  exit 1
fi

ZIP_NAME="nimbbl-opencart4-${VERSION}.zip"
ZIP_PATH="$DIST_DIR/$ZIP_NAME"

echo "==> Nimbbl OpenCart 4 Plugin — v${VERSION}"
echo "    src  : $SRC_DIR"
echo "    dist : $DIST_DIR"
echo "    zip  : $ZIP_NAME"

mkdir -p "$DIST_DIR"

# ── Composer install (optional) ──────────────────────────────────────────────
# Runs only if the nimbbl SDK is NOT already bundled in system/library/nimbbl-sdk/.
# If the SDK is bundled (repo ships it directly), this step is skipped.
BUNDLED_AUTOLOAD="$SRC_DIR/upload/extension/nimbbl/system/library/nimbbl-sdk/autoload.php"
if [[ ! -f "$BUNDLED_AUTOLOAD" ]]; then
  echo "==> Installing Composer dependencies..."
  (cd "$SRC_DIR" && composer install --no-dev --optimize-autoloader --no-interaction)
else
  echo "==> Bundled SDK found — skipping Composer install"
fi

# ── Assemble zip ─────────────────────────────────────────────────────────────
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

mkdir -p "$TMP_DIR/upload"
cp "$SRC_DIR/install.json" "$TMP_DIR/"

# Copy upload/ — excludes dev-only files
rsync -a \
  --exclude='.DS_Store' \
  --exclude='*.orig' \
  "$SRC_DIR/upload/" "$TMP_DIR/upload/"

echo "==> Creating $ZIP_PATH..."
(cd "$TMP_DIR" && zip -r "$ZIP_PATH" . -x "*.DS_Store")

echo "==> Done: $ZIP_PATH ($(du -sh "$ZIP_PATH" | cut -f1))"
