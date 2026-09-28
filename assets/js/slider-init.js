/**
 * Per-slider effect settings.
 *
 * THE PROBLEM
 * assets/js/legacy/InitHome.js initialises the home slider with hard-coded options:
 *
 *     $('.slider').flexslider({ animation: "fade", controlNav: false, … })
 *
 * The source theme exposed `animation` (fade|slide), `slideshow`, `controlNav` and
 * `randomize` per slider. Because that file is vendored and must not be edited (CLAUDE.md),
 * those four settings could not be restored the obvious way.
 *
 * WHY RE-INITIALISING DOES NOT WORK
 * The bundled FlexSlider lives in assets/js/legacy/modules.js. Two properties rule out the
 * obvious fixes:
 *   - it has no `destroy` method (grep -c destroy => 0);
 *   - re-calling `.flexslider({...})` on an already-initialised element is a no-op, guarded
 *     by `else if ($this.data('flexslider') === undefined)` (modules.js:864).
 * So there is no supported way to change the options of a live instance.
 *
 * WHAT THIS DOES INSTEAD
 * Wraps `$.fn.flexslider` from a CORE file and merges each slider's data-* settings into the
 * options object on first construction. This is the same pattern CLAUDE.md sanctions for the
 * jQuery `.live()` shim (T015/B2): the vendored file is left byte-for-byte alone and the
 * missing capability is supplied from assets/js/.
 *
 * The wrapper is installed at parse time and `init-home` is enqueued as a dependency of this
 * file's handle, so the wrap is always in place before the vendored init runs.
 */
(function ($) {
  "use strict";

  if (!$ || !$.fn || !$.fn.flexslider || $.fn.flexslider.__cgWrapped) {
    return;
  }

  var original = $.fn.flexslider;

  /** Read a data-* setting, returning undefined when the attribute is absent. */
  function setting(element, name) {
    var el = $(element);
    if (!el.length || typeof el.data(name) === "undefined") {
      return undefined;
    }
    return el.data(name);
  }

  /**
   * Read a yes/no setting as the STRING "1" or "0", or undefined when it is not set.
   *
   * The string comparison is the whole point. jQuery's .data() coerces a numeric-looking
   * attribute: `data-cg-controlnav="1"` reads back as the NUMBER 1, so a plain
   * `value === "1"` is always false and the setting is silently ignored. That is not
   * hypothetical — controlNav, slideshow and randomize were all dead because of it, while
   * `animation` ("slide") worked, since that value is not numeric. Every yes/no is
   * normalised to a string before it is compared.
   */
  function flag(element, name) {
    var value = setting(element, name);
    if (value === undefined || value === null || value === "") {
      return undefined;
    }
    // jQuery's .data() can hand back a boolean for "true"/"false" as well as a number
    // for "1"/"0", so both are normalised to the same "1"/"0" vocabulary.
    if (value === true) {
      return "1";
    }
    if (value === false) {
      return "0";
    }
    var text = String(value);
    return text === "1" || text === "0" ? text : undefined;
  }

  /**
   * Merge a slider element's settings into the options InitHome passed.
   *
   * Only meaningful values override, so an unset setting leaves the vendored default
   * intact rather than being forced to a falsey value that disables the effect.
   */
  function mergeSettings(element, options) {
    var merged = $.extend({}, options);

    var animation = setting(element, "cgAnimation");
    if (animation === "fade" || animation === "slide") {
      merged.animation = animation;
    }

    var controlnav = flag(element, "cgControlnav");
    if (controlnav !== undefined) {
      merged.controlNav = controlnav === "1";
    }

    var slideshow = flag(element, "cgSlideshow");
    if (slideshow !== undefined) {
      merged.slideshow = slideshow === "1";
    }

    var randomize = flag(element, "cgRandomize");
    if (randomize !== undefined) {
      merged.randomize = randomize === "1";
    }

    return merged;
  }

  function wrapped(options) {
    if (typeof options !== "object" || options === null) {
      // String helper calls (play/pause/next/prev) and numeric indexes pass through.
      return original.apply(this, arguments);
    }

    // InitHome calls this on a set; the settings are per-slider, so the first element
    // in the set is the one whose configuration applies.
    var el = this.length ? this[0] : null;
    if (!el) {
      return original.apply(this, arguments);
    }

    var args = Array.prototype.slice.call(arguments);
    args[0] = mergeSettings(el, options);
    return original.apply(this, args);
  }

  wrapped.__cgWrapped = true;
  wrapped.play = original.play;
  wrapped.pause = original.pause;
  $.fn.flexslider = wrapped;
})(window.jQuery);
