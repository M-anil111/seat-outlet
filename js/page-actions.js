/*
 * Behaviour for markup that used to carry inline on* attributes. Moving it here keeps the Content-Security-Policy free of
 * 'unsafe-inline' for scripts: the markup says what it wants (data-confirm, data-auto-submit, data-scroll-top, data-lazy-css) and this
 * file does the work. Loaded with defer, so it runs after the page is parsed.
 */
(function () {
  'use strict';

  // <form data-confirm="Question?"> asks before it submits; cancelling keeps the form as it is.
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || !form.matches || !form.matches('form[data-confirm]')) return;
    if (!window.confirm(form.getAttribute('data-confirm') || '')) event.preventDefault();
  });

  // <select data-auto-submit> submits its form when the choice changes.
  document.addEventListener('change', function (event) {
    var el = event.target;
    if (el && el.matches && el.matches('[data-auto-submit]') && el.form) el.form.submit();
  });

  // <a href="#top" data-scroll-top> scrolls to the top of the page.
  document.addEventListener('click', function (event) {
    var link = event.target && event.target.closest ? event.target.closest('a[data-scroll-top]') : null;
    if (!link) return;
    event.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // <img data-fallback-src="..."> swaps in its fallback once if the picture fails to load (the error event does not bubble, so capture it).
  document.addEventListener('error', function (event) {
    var img = event.target;
    if (!img || img.tagName !== 'IMG' || !img.getAttribute('data-fallback-src')) return;
    var fallback = img.getAttribute('data-fallback-src');
    if (img.getAttribute('src') !== fallback) img.setAttribute('src', fallback);
  }, true);

  // <link rel="stylesheet" media="print" data-lazy-css> is switched to all media once it has loaded (it does not block the first paint).
  document.querySelectorAll('link[data-lazy-css]').forEach(function (link) {
    var enable = function () { link.media = 'all'; };
    if (link.sheet) enable(); else link.addEventListener('load', enable);
  });
})();
