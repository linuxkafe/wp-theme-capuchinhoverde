# Colour scheme stylesheets

Ported from `cafeteria/css/colors/`. All six schemes are **light** accent variants of the
Cafeteria identity (`#f7f3f3` / `#f8f2f2` / `#ffffff` backgrounds); only the accent hue
differs. `scheme1.css` is empty in the source, which is why the base stylesheet already
carries the default palette.

## Why the path is `assets/css/legacy/css/colors/` and not `assets/css/legacy/colors/`

`assets/js/legacy/scripts.js` is vendored Cafeteria code and `CLAUDE.md` forbids hand-editing
`assets/{css,js}/legacy/`. That script builds the scheme URL by string concatenation:

```js
$('head').append('<link ... href=\'' + curlink + '/css/colors/' + curcolor + '.css?ver=1.0\' ...')
```

`/css/colors/` is hardcoded in the vendored file. Rather than edit a file the contract
forbids touching (an edit that would also be lost on the next parity sync), the files are
placed at the path the existing script already resolves, and `data-link` in
`partials/colorselector.php` is set to `get_template_directory_uri() . '/assets/css/legacy'`.

**The proper fix is to stop hardcoding the path** — pass the scheme directory through the
localized `ale` object (`wp_localize_script` already injects `ale.template_dir`) and change
one line in the vendored script. That needs an owner decision to amend the "never edit
legacy assets" rule for path adaptations. Tracked as a follow-up in
`aes/tickets/T010b-partials.md`.
