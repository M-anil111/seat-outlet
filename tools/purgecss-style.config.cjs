// Drops rules from css/style.css (the site stylesheet) whose class or id never appears in the site's markup or scripts.
// Run through tools/build-assets.sh; the readable source css/style.css is never edited, only css/style.min.css is built from the result.
//
// What is scanned for class names: every page and include (PHP), the admin screens, the AJAX endpoints that return markup, our scripts
// (js/*.js, not the .min copies) and the SQL migrations and seeds (blog posts and editable page blocks are stored as HTML there).
// A class that is only ever built from string pieces at runtime ("is-" + state) cannot be seen: write the whole name somewhere in a PHP or JS
// file, or add it to the safelist below. Classes that third-party scripts put on the page (slick carousel, flatpickr, Google Places
// autocomplete, reCAPTCHA, Bootstrap's own JS) are safelisted here, because our files never spell them out.
const root = require('path').resolve(__dirname, '..');
module.exports = {
  content: [
    root + '/*.php', root + '/inc/**/*.php', root + '/admin/**/*.php', root + '/ajax/*.php', root + '/cron/*.php',
    root + '/js/!(*.min).js',
    root + '/db/migrations/*.sql', root + '/db/seeds/*.sql',
    root + '/offline.html',
  ],
  variables: false,   // keep every custom property
  keyframes: false,   // keep every @keyframes
  fontFace: false,    // keep every @font-face
  safelist: {
    standard: ['show', 'showing', 'hiding', 'fade', 'active', 'disabled', 'collapsing', 'collapsed', 'modal-open', 'offcanvas-open', 'loaded', 'is-active', 'is-open', 'is-expanded'],
    greedy: [/slick/, /flatpickr/, /^pac-/, /^grecaptcha/, /^grecaptcha/, /data-bs/, /^offcanvas/, /^dropdown/, /^modal/, /^tooltip/, /^popover/, /^bs-/, /^sea-/i, /^tn-/, /^so-mega-/,
      // class names built from pieces in PHP or JS ("so-chipx--" . $kind, "suggestion-" + type, ...): found by tools/ scan of the sources
      /^so-chipx--/, /^so-evc--/, /^so-topc--/, /^so-vc--/, /^so-ad--/, /^suggestion-/, /^so-tab-/, /^so-art__toc/,
      // the blog: article and listing markup is assembled by inc/blog-render.php and stored posts, so keep the whole family
      /^so-art__/, /^so-np__/, /^so-blog/, /^so-article/,
      // classes that exist only inside stored HTML (blog posts and page blocks in the database; checked against the seeded content)
      /^so-cta/, /^so-faq/, /^so-keybox/, /^so-table-wrap/, /^so-toc/],
  },
};
