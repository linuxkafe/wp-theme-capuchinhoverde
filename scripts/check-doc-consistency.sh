#!/usr/bin/env bash
# Fails when docs/REQUIREMENTS.md makes a version claim that contradicts the
# style.css theme header. Documentation drift is silent otherwise, and a stale
# "PHP 7.4+" line licenses work that will fatal on the declared runtime.
#
# Contract: exit 0 = consistent OR nothing to compare. Exit 1 = contradiction,
# with the specific field named. Never exit 0 on unparseable input — that would
# turn a broken gate into a green one.

set -uo pipefail

REQS="docs/REQUIREMENTS.md"
STYLE="style.css"

[ -f "$REQS" ] || { echo "consistency: SKIP ($REQS not found)"; exit 0; }
[ -f "$STYLE" ] || { echo "consistency: FAIL ($STYLE not found — cannot verify)"; exit 1; }

fail=0

# Extract "Requires PHP: X" from the style.css theme header.
style_php=$(grep -m1 -E '^[[:space:]]*Requires PHP:' "$STYLE" | sed -E 's/.*Requires PHP:[[:space:]]*//' | tr -d '[:space:]')
# Extract the minimum PHP from REQUIREMENTS, tolerating "PHP 7.4+" / "PHP >= 7.4" / "PHP 8.2+".
# -o is required: a line mentioning both WP and PHP ("WordPress 6.0+ e PHP 7.4+") carries two
# versions, and scanning the whole line would pick the wrong one.
reqs_php=$(grep -oiE 'PHP[[:space:]]*(>=)?[[:space:]]*[0-9]+\.[0-9]+' "$REQS" \
	| head -1 | grep -oE '[0-9]+\.[0-9]+')

if [ -z "$style_php" ] || [ -z "$reqs_php" ]; then
	echo "consistency: SKIP (no comparable PHP version claim in $REQS)"
	exit 0
fi

if [ "$style_php" != "$reqs_php" ]; then
	echo "consistency: FAIL — PHP minimum disagrees"
	echo "  $STYLE declares : $style_php"
	echo "  $REQS claims    : $reqs_php"
	echo "  fix: update $REQS to match the code (the code is the source of truth)"
	fail=1
fi

# WordPress minimum: compare "Requires at least" against a "WordPress X.Y+" claim.
style_wp=$(grep -m1 -E '^[[:space:]]*Requires at least:' "$STYLE" | sed -E 's/.*Requires at least:[[:space:]]*//' | tr -d '[:space:]')
reqs_wp=$(grep -oiE 'WordPress[[:space:]]*[0-9]+\.[0-9]+' "$REQS" | head -1 | grep -oE '[0-9]+\.[0-9]+')

if [ -n "$style_wp" ] && [ -n "$reqs_wp" ] && [ "$style_wp" != "$reqs_wp" ]; then
	echo "consistency: FAIL — WordPress minimum disagrees"
	echo "  $STYLE declares : $style_wp"
	echo "  $REQS claims    : $reqs_wp"
	fail=1
fi

if [ $fail -eq 0 ]; then
	echo "consistency: OK (php=$style_php wp=$style_wp)"
fi
exit $fail
