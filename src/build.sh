#!/usr/bin/env bash
# build.sh — Build nimbbl.ocmod.zip for OC4 Extension Installer distribution
#
# OC4 Extension Installer derives the extension code from the zip filename
# (basename without .ocmod.zip) and extracts ALL zip contents under
# extension/{code}/. Therefore:
#   - Zip must be named nimbbl.ocmod.zip  (code = nimbbl)
#   - Files inside must be at raw paths:  admin/, catalog/, system/
#     (no install.json, no upload/ wrapper)
#   - Installer places them at:           extension/nimbbl/admin/  etc.
#
# Output per version:
#   Stable:  public/opencart4/v4.0.0/nimbbl.ocmod.zip
#   Alpha:   public/opencart4/v4.1.0-alpha.1/nimbbl-alpha.1.ocmod.zip
#   Beta:    public/opencart4/v4.1.0-beta.2/nimbbl-beta.2.ocmod.zip
#   RC:      public/opencart4/v4.1.0-rc.1/nimbbl-rc.1.ocmod.zip
#
# Usage:
#   ./src/build.sh [VERSION]
#   VERSION defaults to install.json version if omitted.
#
# Requirements: zip, rsync, jq (optional)
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_DIR="$SCRIPT_DIR/opencart4"
UPLOAD_DIR="$SRC_DIR/upload/extension/nimbbl"

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

# ── Zip filename: stable = nimbbl.ocmod.zip, pre-release = nimbbl-{suffix}.ocmod.zip
PRERELEASE="${VERSION#*-}"
if [[ "$PRERELEASE" == "$VERSION" ]]; then
  ZIP_NAME="nimbbl.ocmod.zip"
else
  ZIP_NAME="nimbbl-${PRERELEASE}.ocmod.zip"
fi

PUBLIC_DIR="$SCRIPT_DIR/../public/opencart4/v${VERSION}"
ZIP_PATH="$PUBLIC_DIR/$ZIP_NAME"

echo "==> Nimbbl OpenCart 4 Plugin — v${VERSION}"
echo "    src    : $UPLOAD_DIR"
echo "    output : $ZIP_PATH"
echo "    note   : OC4 installer extracts to extension/nimbbl/ from filename"

mkdir -p "$PUBLIC_DIR"

# ── Assemble zip (raw paths — no install.json, no upload/ wrapper) ────────────
# Files inside zip: admin/, catalog/, system/
# OC4 Extension Installer prepends code (nimbbl) → extension/nimbbl/admin/ etc.
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

rsync -a \
  --exclude='.DS_Store' \
  --exclude='*.orig' \
  "$UPLOAD_DIR/" "$TMP_DIR/"

echo "==> Creating $ZIP_PATH..."
(cd "$TMP_DIR" && zip -r "$ZIP_PATH" . -x "*.DS_Store")

echo "==> Done: $ZIP_PATH ($(du -sh "$ZIP_PATH" | cut -f1))"
