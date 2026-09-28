/**
 * Repeater UI for the theme's `repeater` meta fields (see cg_render_repeater() in
 * inc/meta.php). Replaces the raw JSON textarea that used to be the only way to edit
 * _cg_slides. aes/tickets/T018.
 *
 * VANILLA JS, NO jQUERY. CLAUDE.md is explicit: the front end depends on jQuery and that is
 * not changing in the port's scope, but new code is vanilla and assets/js/nav.js is the
 * model. This file is also admin-only, so it never ships to a visitor.
 *
 * THE CONTRACT
 * The visible inputs are unnamed. This file serialises them into the single hidden
 * `cg-repeater-json` input, which is the only thing the save path sees. That is deliberate:
 * cg_save_meta() discards array input, so a repeater posting `cg_meta[key][0][field]` would
 * be silently dropped on save. Keeping the payload a JSON string means the T015/B4
 * writer-reader contract and cg_sanitize_meta_json() are untouched.
 *
 * Every mutation calls sync(). If that ever throws, the hidden input would go stale and the
 * save would silently drop the author's work — so it is serialised defensively and the
 * previous value is kept as a fallback.
 */
(function () {
  "use strict";

  var FIELD_SELECTOR = "[data-field]";

  function rowsOf(repeater) {
    var host = repeater.querySelector(".cg-repeater-rows");
    if (!host) {
      return [];
    }
    // Scoped to the rows host on purpose. An earlier version used
    // repeater.querySelectorAll, which — combined with a move() that inserted the row into
    // the WRONG parent — meant a relocated row was skipped by the serialisation and vanished
    // from the saved payload with no error at all.
    return Array.prototype.slice.call(
      host.querySelectorAll(".cg-repeater-row"),
    );
  }

  function rowData(row) {
    var data = {};
    var inputs = row.querySelectorAll(FIELD_SELECTOR);
    for (var i = 0; i < inputs.length; i++) {
      data[inputs[i].getAttribute("data-field")] = inputs[i].value;
    }
    return data;
  }

  /** Serialise every row into the hidden input. */
  function sync(repeater) {
    var target = repeater.querySelector(".cg-repeater-json");
    if (!target) {
      return;
    }
    var data = rowsOf(repeater).map(rowData);
    var json;
    try {
      json = JSON.stringify(data);
    } catch (e) {
      // Leave the previous value in place rather than blanking the field: a broken
      // save that keeps the last good value beats one that erases the author's slides.
      if (window.console && window.console.error) {
        window.console.error(
          "[capuchinhoverde] repeater could not serialise",
          e,
        );
      }
      return;
    }
    target.value = json;
    if (repeater.dataset.cgDirty !== "true") {
      repeater.dataset.cgDirty = "true";
    }
  }

  function renumber(repeater) {
    var label = repeater.getAttribute("data-cg-item-label") || "Item";
    var rows = rowsOf(repeater);
    rows.forEach(function (row, i) {
      var name = row.querySelector(".cg-repeater-row-label");
      if (name) {
        name.textContent = label + " " + (i + 1);
      }
      row.setAttribute("data-index", String(i));
    });
  }

  function addRow(repeater) {
    var template = repeater.querySelector(".cg-repeater-template");
    var host = rowsHost(repeater);
    if (!template || !host) {
      return;
    }
    // A <template>'s .content is an inert DocumentFragment. Passing its innerHTML to
    // appendChild() instead throws "parameter 1 is not of type 'Node'", which is how the
    // Add button failed to add anything until the first real browser run.
    var fragment = template.content.cloneNode(true);
    var first = fragment.querySelector(".cg-repeater-row");
    host.appendChild(fragment);
    if (!first) {
      return;
    }
    renumber(repeater);
    sync(repeater);
    // Focus the first field of the new row so the author can type straight away.
    var field = first.querySelector(FIELD_SELECTOR);
    if (field) {
      field.focus();
    }
  }

  function removeRow(repeater, row) {
    var host = rowsHost(repeater);
    if (!host) {
      return;
    }
    var rows = rowsOf(repeater);
    if (rows.length <= 1) {
      // Keep at least one row: an empty repeater with no Add button in reach is a
      // worse trap than a blank row.
      return;
    }
    host.removeChild(row);
    renumber(repeater);
    sync(repeater);
  }

  function rowsHost(repeater) {
    return repeater.querySelector(".cg-repeater-rows");
  }

  function move(repeater, row, delta) {
    var host = rowsHost(repeater);
    if (!host) {
      return;
    }
    if (delta < 0 && row.previousElementSibling) {
      host.insertBefore(row, row.previousElementSibling);
    } else if (delta > 0 && row.nextElementSibling) {
      host.insertBefore(row.nextElementSibling, row);
    } else {
      return;
    }
    renumber(repeater);
    sync(repeater);
  }

  function mediaFor(input) {
    if (!window.wp || !window.wp.media) {
      return;
    }
    var frame = window.wp.media({
      title: input.value ? "Replace image" : "Choose image",
      library: { type: "image" },
      multiple: false,
      button: { text: "Use this image" },
    });
    frame.on("select", function () {
      var attachment = frame.state().get("selection").first().toJSON();
      input.value = attachment.url;
      var repeater = input.closest(".cg-repeater");
      if (repeater) {
        sync(repeater);
      }
    });
    frame.open();
  }

  function init() {
    var repeaters = document.querySelectorAll("[data-cg-repeater]");
    for (var i = 0; i < repeaters.length; i++) {
      bind(repeaters[i]);
    }
  }

  function bind(repeater) {
    if (repeater.dataset.cgBound === "true") {
      return;
    }
    repeater.dataset.cgBound = "true";

    repeater.addEventListener("click", function (event) {
      var target = event.target;
      var row = target.closest ? target.closest(".cg-repeater-row") : null;

      if (target.closest(".cg-repeater-add")) {
        event.preventDefault();
        addRow(repeater);
        return;
      }
      if (target.closest(".cg-repeater-media")) {
        event.preventDefault();
        var input = target.parentNode
          ? target.parentNode.querySelector(FIELD_SELECTOR)
          : null;
        if (input) {
          mediaFor(input);
        }
        return;
      }
      if (target.closest(".cg-repeater-remove")) {
        event.preventDefault();
        if (row) {
          removeRow(repeater, row);
        }
        return;
      }
      if (target.closest(".cg-repeater-move-up")) {
        event.preventDefault();
        if (row) {
          move(repeater, row, -1);
        }
        return;
      }
      if (target.closest(".cg-repeater-move-down")) {
        event.preventDefault();
        if (row) {
          move(repeater, row, 1);
        }
      }
    });

    // Any edit to a field must reach the hidden input before the form is submitted.
    repeater.addEventListener("input", function (event) {
      if (event.target.matches && event.target.matches(FIELD_SELECTOR)) {
        sync(repeater);
      }
    });

    // The block editor moves DOM around; a late sync on submit is the safety net that
    // makes sure a row added by drag/insert is not lost.
    var form = repeater.closest("form");
    if (form) {
      form.addEventListener("submit", function () {
        sync(repeater);
      });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
