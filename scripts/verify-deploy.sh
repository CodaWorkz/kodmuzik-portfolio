#!/usr/bin/env bash
# Compares every public static file in this repo with what the live site serves.
# Run from the repo root after uploading:  bash scripts/verify-deploy.sh
# PHP files and .htaccess cannot be compared this way (the server runs them).

set -u
BASE="${1:-https://www.kodmuzik.com}"
cd "$(dirname "$0")/.." || exit 1

hash_cmd() {
  if command -v sha256sum >/dev/null 2>&1; then sha256sum | cut -c1-16
  else shasum -a 256 | cut -c1-16; fi
}

fail=0
total=0
tmp="$(mktemp)"
trap 'rm -f "$tmp"' EXIT

while IFS= read -r f; do
  case "$f" in
    */index.html) url="/${f%index.html}" ;;
    index.html)   url="/" ;;
    *)            url="/$f" ;;
  esac
  total=$((total + 1))
  code="$(curl -s -o "$tmp" -w '%{http_code}' -H 'Cache-Control: no-cache' "$BASE$url?verify=$(date +%s)")"
  local_hash="$(hash_cmd < "$f")"
  live_hash="$(hash_cmd < "$tmp")"
  if [ "$code" != "200" ]; then
    echo "MISSING  $f  (HTTP $code)"; fail=$((fail + 1))
  elif [ "$local_hash" != "$live_hash" ]; then
    echo "DIFF     $f"; fail=$((fail + 1))
  fi
done < <(git ls-files | grep -E '\.(html|css|js|txt|xml|svg)$' \
         | grep -vE '^(admin|db|scripts|design-mockups)/')

echo "Checked $total files, $fail problem(s)."
[ "$fail" -eq 0 ]
