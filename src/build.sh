#!/usr/bin/env bash
# build.sh — Build Opencart4_Nimbbl[-(prerelease)].ocmod.zip for distribution
#
# Usage:
#   ./src/build.sh [VERSION]
#
# VERSION examples:
#   4.0.0            → public/opencart4/v4.0.0/Opencart4_Nimbbl.ocmod.zip
#   4.1.0-alpha.1    → public/opencart4/v4.1.0-alpha.1/Opencart4_Nimbbl-alpha.1.ocmod.zip
#   4.1.0-beta.2     → public/opencart4/v4.1.0-beta.2/Opencart4_Nimbbl-beta.2.ocmod.zip
#   4.1.0-rc.1       → public/opencart4/v4.1.0-rc.1/Opencart4_Nimbbl-rc.1.ocmod.zip
#
# If VERSION is omitted it reads from install.json.
#
# Requirements: composer, zip, jq (optional — used to parse install.json)
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_DIR="$SCRIPT_DIR/opencart4"

# ── Version ──────────────────────────────────────────────────────────────────
if [[ -n "${1:-}" ]]; then
  VERSION="$1"
elif command -v jq &>/dev/null; then
  VERSION="$(jq -r .version "$SRC_DIR/install.json")"
else
  VERSION="$(grep -o '"version": *"[^"]*"' "$SRC_DIR/install.json" | head -1 | sed 's/.*"\([^"]*\)".*/\1/')"
fi

if [[ -z "$VERSION" ]]; then
  echo "ERROR: could not determine version" >&2
  exit 1
fi

# ── Resolve zip name (stable vs pre-release) ─────────────────────────────────
# VERSION=4.1.0          → ZIP_NAME=Opencart4_Nimbbl.ocmod.zip
# VERSION=4.1.0-alpha.1  → ZIP_NAME=Opencart4_Nimbbl-alpha.1.ocmod.zip
PRERELEASE="${VERSION#*-}"
if [[ "$PRERELEASE" == "$VERSION" ]]; then
  # No pre-release suffix
  ZIP_NAME="Opencart4_Nimbbl.ocmod.zip"
else
  ZIP_NAME="Opencart4_Nimbbl-${PRERELEASE}.ocmod.zip"
fi

PUBLIC_DIR="$SCRIPT_DIR/../public/opencart4/v${VERSION}"
ZIP_PATH="$PUBLIC_DIR/$ZIP_NAME"

echo "==> Nimbbl OpenCart 4 Plugin — v${VERSION}"
echo "    src    : $SRC_DIR"
echo "    output : $ZIP_PATH"

mkdir -p "$PUBLIC_DIR"

# ── Composer install (optional) ──────────────────────────────────────────────
# Runs only if the nimbbl SDK is NOT already bundled in system/library/nimbbl-sdk/.
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
# Copy install.json and stamp the actual build version into it
sed "s/\"version\": *\"[^\"]*\"/\"version\": \"${VERSION}\"/" \
  "$SRC_DIR/install.json" > "$TMP_DIR/install.json"

# Copy upload/ — excludes dev-only files
rsync -a \
  --exclude='.DS_Store' \
  --exclude='*.orig' \
  "$SRC_DIR/upload/" "$TMP_DIR/upload/"

echo "==> Creating $ZIP_PATH..."
(cd "$TMP_DIR" && zip -r "$ZIP_PATH" . -x "*.DS_Store")

echo "==> Done: $ZIP_PATH ($(du -sh "$ZIP_PATH" | cut -f1))"
