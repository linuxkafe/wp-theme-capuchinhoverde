/**
 * [ale_toggle] progressive enhancement.
 *
 * Vanilla JS — the theme's new code must not add to the vendored jQuery layer (CLAUDE.md).
 *
 * Accessibility contract: the server renders the panel with `hidden` unless state="open",
 * so the content is readable with JS disabled. This only adds the click behaviour, and it
 * keeps the button's aria-expanded in sync rather than toggling a class alone.
 */
(function () {
  'use strict';

  function init(root) {
    var toggles = root.querySelectorAll('.ale-toggle');
    Array.prototype.forEach.call(toggles, function (toggle) {
      if (toggle.dataset.cgBound === '1') return;
      toggle.dataset.cgBound = '1';

      var button = toggle.querySelector('.ale-toggle-title');
      var panel = toggle.querySelector('.ale-toggle-inner');
      if (!button || !panel) return;

      button.addEventListener('click', function () {
        var isOpen = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        if (isOpen) {
          panel.setAttribute('hidden', '');
          toggle.classList.remove('is-open');
        } else {
          panel.removeAttribute('hidden');
          toggle.classList.add('is-open');
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { init(document); });
  } else {
    init(document);
  }

  // Block editor previews render content dynamically.
  if (window.wp && window.wp.data && window.wp.data.subscribe) {
    window.wp.data.subscribe(function () {
      window.requestAnimationFrame(function () { init(document); });
    });
  }
})();
