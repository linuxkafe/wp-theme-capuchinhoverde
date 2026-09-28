#!/usr/bin/env bash
# AES Human Validation Script — sprint-02-03 port, review round 1
#
# WHO MUST RUN THIS: someone who is NOT the author of the candidate. The author is the agent
# that wrote T007/T010b/T011/T012/T013. Per PEER_REVIEW.md §7, a review whose human script is
# not executed by an independent person CANNOT verdict ACCEPT.
#
# HOW TO USE: run it, paste the whole output into
# aes/peer-reviews/sprint-02-03-port/human-validation-output.txt, and note your name/date at
# the top. Expect it to FAIL. That is the point: these are the 4 BLOCKERs.
#
# Time: about 10 minutes.

set -uo pipefail
cd "$(dirname "$0")/../../.."
fails=0
ok(){ printf '  PASS  %s\n' "$1"; }
no(){ printf '  FAIL  %s\n' "$1"; fails=$((fails+1)); }

echo "=== AES human validation — Capuchinho Verde sprint-02-03 ==="
echo "executed by: ______________________   date: ____________"
echo

echo "[B1] The gate must not report success when it ran nothing"
out=$(make check WP_URL=http://localhost:9999 2>&1)
echo "$out" | grep -q "check: OK" && no "B1: 'check: OK' printed with 0 tests executed" \
                                     || ok "B1: gate degraded honestly"
echo "$out" | grep -q "test: SKIP" && echo "  note: E2E was skipped, as intended for this probe"
echo

echo "[B2] The colour-scheme selector must actually work"
echo "  1. In wp-admin: Appearance > Customize > Capuchinho Verde > Features"
echo "  2. Tick 'Enable the skin selector', save"
echo "  3. Open the front page and click the 'Choose the color scheme' handle, then a swatch"
echo "  4. Tell the reviewer which happened:"
echo "     (a) a swatch applies and the page recolours  -> PASS"
echo "     (b) nothing happens                          -> BLOCKER B2 confirmed"
echo "  Do this in a browser with the console open: a '(...).live is not a function' error is B2."
echo

echo "[B3] The Slider editor must not be a fatal error"
slider=$(docker compose run --rm -T cli post list --post_type=cg_slider --posts_per_page=1 --field=ID --format=ids 2>/dev/null | tail -1 | tr -d '[:space:]')
if [ -n "$slider" ]; then
  out=$(docker compose run --rm -T cli eval "
    \$v = get_post_meta($slider,'_cg_slides',true);
    esc_textarea(is_array(\$v) ? wp_json_encode(\$v, JSON_PRETTY_PRINT) : \$v);
    echo 'RENDERED_OK';
  " 2>&1)
  echo "$out" | grep -q RENDERED_OK && ok "B3: slides field renders without a fatal" \
                                       || no "B3: fatal rendering the slides field for slider $slider"
  # And in the browser: edit the "Home Slider" post and save.
  echo "  Also confirm by hand: edit the Home Slider post in wp-admin and save. A"
  echo "  'There has been a critical error' banner means B3 is confirmed."
else
  no "B3: could not find a cg_slider post to test"
fi
echo

echo "[B4] Slides saved through the UI must be readable by the front end"
echo "  1. In wp-admin, open the Home Slider post, paste into the Slides field:"
echo '     [{"image":"","title":"VALIDATION","description":"","url":""}]'
echo "  2. Save, then load the front page"
echo "  3. Tell the reviewer which happened:"
echo "     (a) 'VALIDATION' appears in the slider        -> PASS"
echo "     (b) the slider is empty / says 'No slider'   -> BLOCKER B4 confirmed"
echo

echo "[M1] docs/PARITY.md must match the code"
declared=$(grep -oE 'E2E: \*\*[0-9]+ assertions' docs/PARITY.md | grep -oE '[0-9]+')
actual=$(npx playwright test --list 2>/dev/null | tail -1 | grep -oE '[0-9]+')
[ "$declared" = "$actual" ] && ok "M1: PARITY.md test count ($declared) matches the suite ($actual)" \
                          || no "M1: PARITY.md claims $declared tests; the suite has $actual"
echo "  Also read docs/PARITY.md sections 0, 4 and 5. Do they describe a port that delivered"
echo "  6 shortcodes and 11 partials, or 0 and 0?"
echo

echo "[M2] style.css must not claim zero jQuery"
grep -q "Zero jQuery" style.css && no "M2: style.css still claims 'Zero jQuery dependency in core'" \
                              || ok "M2: style.css header no longer claims zero jQuery"
echo "  Cross-check: functions.php enqueues jquery unconditionally. Does the header match?"
echo

echo "[M6] The seed must be idempotent"
docker compose run --rm -T cli menu list --format=ids 2>/dev/null | tail -1 >/dev/null
bash scripts/seed.sh >/dev/null 2>&1
items=$(docker compose run --rm -T cli menu item list --format=count 2>/dev/null | tail -1 | tr -d '[:space:]')
bash scripts/seed.sh >/dev/null 2>&1
items2=$(docker compose run --rm -T cli menu item list --format=count 2>/dev/null | tail -1 | tr -d '[:space:]')
[ "$items" = "$items2" ] && ok "M6: nav item count stable across seeds ($items)" \
                          || no "M6: nav items grew from $items to $items2 after a second seed"
echo

echo "[M8] The 'Enable the contact form' control must do something"
n=$(curl -s http://localhost:8083/ | grep -c 'class="cg-contact-form"')
echo "  form on the home page right now: $n"
echo "  Untick 'Enable the contact form' in the Customizer, reload the home page, and re-check."
echo "  If the form is still there, M8 is confirmed."
echo

echo "=== summary: $fails failing check(s) ==="
echo "Expected result: MULTIPLE FAILURES. These are verified BLOCKER/MAJOR findings."
echo "Paste this whole output into aes/peer-reviews/sprint-02-03-port/human-validation-output.txt"
