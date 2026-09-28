/**
 * Compatibility shim for the vendored Cafeteria legacy layer.
 *
 * WHY THIS EXISTS
 * assets/js/legacy/scripts.js binds its colour-scheme selector with jQuery's `.live()`
 * (lines 24, 28, 33). `.live()` was deprecated in jQuery 1.7 and REMOVED in jQuery 3.0.
 * WordPress serves jquery.min.js?ver=3.7.1, where the method does not exist, and
 * jquery-migrate 3.x does not restore it. Every click on `.colorselector .openbut` and
 * `.icbox` therefore threw "$(...).live is not a function" and bound nothing, making the
 * `skinselector` feature dead code. Peer review finding B2, tracked in aes/tickets/T015.
 *
 * WHY IT LIVES HERE AND NOT IN THE VENDORED FILE
 * CLAUDE.md forbids hand-editing assets/js/legacy/ — those files are ported from upstream
 * Cafeteria and edits are lost on the next parity sync. The sanctioned carve-out covers
 * only files that hardcode a relocated path; a removed jQuery API is not that. So the
 * missing method is restored from a core, non-legacy file, and no vendored byte moves.
 *
 * This is a shim, not new jQuery usage: the legacy layer is already jQuery-bound
 * (CLAUDE.md's jQuery section), and this adds no new jQuery *call sites*. New code in this
 * theme stays vanilla — assets/js/nav.js is the model.
 *
 * DELIBERATELY MINIMAL
 * Only the 2-argument delegated form `.live(types, handler)` is supported, because that is
 * all the vendored file uses. `.live(types, selector, data, handler)` delegation and the
 * removed `.die()`/`.undelegate()` family are deliberately not shimmed: nothing in this
 * theme calls them, and resurrecting the whole 1.x API would be a far larger surface than
 * the bug requires.
 */
(function ($) {
  "use strict";

  if (!$ || !$.fn || typeof $.fn.live === "function") {
    return;
  }

  $.fn.live = function (types, selector, data, handler) {
    // `.live(types, handler)` — the only form the legacy layer uses.
    if (typeof selector === "function") {
      return this.on(types, selector);
    }
    return this.on(types, selector, data, handler);
  };
})(window.jQuery);
