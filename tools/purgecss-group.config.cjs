// Like purgecss-style.config.cjs, but the content to scan is one page type's text file (tools/css-split.php writes it;
// the path comes in through SO_CSS_CONTENT). Used by tools/build-assets.sh to make css/style.<page type>.min.css.
const base = require('./purgecss-style.config.cjs');
module.exports = Object.assign({}, base, { content: [process.env.SO_CSS_CONTENT] });
