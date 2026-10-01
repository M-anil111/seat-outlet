// Trims lib/bootstrap/5.3.8/bootstrap.min.css down to the classes this site uses.
// Run through tools/build-assets.sh (output: css/bootstrap.min.css, which header.php loads).
// A class that is built from string pieces at runtime ("btn-" + name) cannot be detected:
// write it out in full somewhere in the PHP or JS, or add it to the safelist below.
const path = require('path');
const root = path.resolve(__dirname, '..');
module.exports = {
  content: [
    root + '/*.php', root + '/inc/**/*.php', root + '/admin/**/*.php',   // pages, includes (incl. inc/seo-copy), admin screens
    root + '/js/!(*.min).js',                                         // classes written by our scripts
  ],
  css: [root + '/lib/bootstrap/5.3.8/bootstrap.min.css'],
  variables: false,
  keyframes: false,
  fontFace: false,
  safelist: {
    standard: [
      // added or toggled by Bootstrap's JavaScript at runtime
      'show', 'showing', 'hiding', 'fade', 'active', 'disabled', 'collapsing', 'collapsed',
      'modal-open', 'modal-backdrop', 'offcanvas-backdrop', 'dropdown-menu-end',
      // basics an admin may type into blog posts and editable page blocks (stored in the database)
      'img-fluid', 'lead', 'blockquote', 'list-unstyled', 'table', 'text-center', 'text-start', 'text-end',
      'text-muted', 'fw-bold', 'fw-semibold', 'rounded', 'shadow-sm', 'ratio', 'row', 'container', 'btn',
      'btn-primary', 'btn-outline-primary', 'alert',
    ],
    greedy: [
      /data-bs-popper/, /^modal-static/, /^table-/, /^alert-/, /^ratio-/, /^fs-[1-6]$/,
      /^col(-(sm|md|lg|xl))?(-([1-9]|1[0-2]))?$/, /^m[tby]-[0-5]$/, /^p[tby]?-[0-5]$/,
    ],
  },
};
