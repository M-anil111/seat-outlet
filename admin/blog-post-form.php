<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/blog-render.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$existing = $id > 0 ? getBlogPostById($id) : null;
if ($id > 0 && !$existing) {
    admin_flash_set('error', 'That blog post no longer exists.');
    header('Location: blog-posts');
    exit;
}

$errors = [];
$values = $existing ?: [
    'title' => '', 'focus_keyword' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 'featured_image' => '',
    'featured_image_alt' => '', 'category' => '', 'live_search' => '', 'author_name' => '', 'meta_title' => '', 'meta_description' => '', 'status' => 'draft',
    'published_at' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values = array_merge($values, $_POST);
        try {
            saveBlogPost(array_merge($values, ['id' => $id]));
            admin_flash_set('success', 'Blog post saved.');
            header('Location: blog-posts');
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

if (!empty($errors)) {
    admin_flash_set('error', implode(' ', $errors));
}

$pageTitle = ($id > 0 ? 'Edit' : 'Add') . ' Blog Post — Seat Outlet Admin';
$currentPage = 'blog-posts';
include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3">
            <div class="col">
                <h2 class="page-title"><?php echo $id > 0 ? 'Edit' : 'Add'; ?> Blog Post</h2>
            </div>
        </div>

        <form method="post" action="blog-post-form<?php echo $id > 0 ? '?id=' . $id : ''; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Content</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="f-title">Title</label>
                        <input type="text" id="f-title" name="title" class="form-control" required
                            value="<?php echo htmlspecialchars($values['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-slug">Slug</label>
                        <input type="text" id="f-slug" name="slug" class="form-control" placeholder="Leave blank to auto-generate from the title"
                            value="<?php echo htmlspecialchars($values['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="form-hint">Public URL will be /blog/{slug}.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-focus_keyword">Focus keyword</label>
                        <input type="text" id="f-focus_keyword" name="focus_keyword" class="form-control"
                            placeholder="e.g. best concert venues in austin"
                            value="<?php echo htmlspecialchars($values['focus_keyword'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="form-hint">
                            Save this post, then check its score on the
                            <a href="seo-scores">Page Content &amp; SEO Scores</a> screen.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-excerpt">Excerpt</label>
                        <textarea id="f-excerpt" name="excerpt" class="form-control" rows="2"><?php echo htmlspecialchars($values['excerpt'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="form-hint">Shown on the blog listing page and used as the default meta description.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-featured_image">Featured image URL</label>
                        <input type="text" id="f-featured_image" name="featured_image" class="form-control" placeholder="https://…"
                            value="<?php echo htmlspecialchars($values['featured_image'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (!empty($values['featured_image'])) { ?><img src="<?php echo htmlspecialchars($values['featured_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="" class="mt-2 rounded" style="max-height:90px;max-width:100%"><?php } ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-featured_image_alt">Featured image alt text</label>
                        <input type="text" id="f-featured_image_alt" name="featured_image_alt" class="form-control" maxlength="255" placeholder="Describe what the picture shows, e.g. Crowd at an outdoor concert at sunset"
                            value="<?php echo htmlspecialchars($values['featured_image_alt'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="form-hint">Read aloud by screen readers and used by image search and link previews. Describe the picture, do not repeat the title.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-category">Category</label>
                        <input type="text" id="f-category" name="category" class="form-control" maxlength="60" placeholder="e.g. Ticket Safety, City Guides"
                            value="<?php echo htmlspecialchars($values['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="form-hint">Shown as a label on the blog listing and used for the category filter.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-live_search">Live tickets block (optional)</label>
                        <input type="text" id="f-live_search" name="live_search" class="form-control" maxlength="120" placeholder="e.g. Taylor Swift"
                            value="<?php echo htmlspecialchars($values['live_search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="form-hint">Type an exact performer name to show their upcoming events with live prices at the end of the post. Leave blank for none.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-author_name">Author name</label>
                        <input type="text" id="f-author_name" name="author_name" class="form-control"
                            value="<?php echo htmlspecialchars($values['author_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="f-content">Content (HTML)</label>
                        <div class="d-flex flex-wrap gap-2 mb-2" id="blogTools">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-snip="performer">+ Live events: performer</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-snip="category">+ Live events: category</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-snip="venue">+ Live events: venue</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-snip="city">+ Live events: city</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-snip="newsletter">+ Newsletter box</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-snip="cta">+ Ticket CTA box</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#blogImages">+ Image from library</button>
                        </div>
                        <div class="collapse mb-2" id="blogImages">
                            <div class="border rounded p-2" style="max-height:260px;overflow:auto">
                                <div class="row g-2">
                                <?php foreach (blogImageLibrary() as $img) { ?>
                                    <div class="col-4 col-md-3 col-lg-2">
                                        <button type="button" class="btn p-0 border w-100" data-img="<?php echo htmlspecialchars($img['path'], ENT_QUOTES, 'UTF-8'); ?>" data-w="<?php echo (int) $img['w']; ?>" data-h="<?php echo (int) $img['h']; ?>" title="<?php echo htmlspecialchars($img['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <img src="<?php echo htmlspecialchars($img['path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy" style="width:100%;height:70px;object-fit:cover">
                                        </button>
                                    </div>
                                <?php } ?>
                                </div>
                            </div>
                            <small class="form-hint">Pictures already licensed and used on the site. Click one to insert it with alt text and a caption you can edit.</small>
                        </div>
                        <textarea id="f-content" name="content" class="form-control font-monospace" rows="16" required><?php echo htmlspecialchars($values['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="form-hint">Rendered as raw HTML - write real HTML (paragraphs, headings, links), not Markdown. The <strong>table of contents is built automatically</strong> from your H2 and H3 headings (do not add one by hand). Use the buttons above for live ticket listings, newsletter and ticket call-to-action boxes. Shortcodes: <code>[events performer="Taylor Swift" limit="6"]</code>, <code>[events category="concerts"]</code>, <code>[newsletter]</code>, <code>[cta title="..." text="..." button="..." url="/concert-tickets-for-sale"]</code>.</small>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">SEO metadata (optional)</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="f-meta_title">Meta title</label>
                        <input type="text" id="f-meta_title" name="meta_title" class="form-control" placeholder="Defaults to the title"
                            value="<?php echo htmlspecialchars($values['meta_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="f-meta_description">Meta description</label>
                        <textarea id="f-meta_description" name="meta_description" class="form-control" rows="2" placeholder="Defaults to the excerpt"><?php echo htmlspecialchars($values['meta_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Publishing</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="f-status">Status</label>
                        <select id="f-status" name="status" class="form-select">
                            <option value="draft" <?php echo ($values['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo ($values['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="f-published_at">Published date</label>
                        <input type="datetime-local" id="f-published_at" name="published_at" class="form-control"
                            value="<?php echo !empty($values['published_at']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($values['published_at'])), ENT_QUOTES, 'UTF-8') : ''; ?>">
                        <small class="form-hint">Leave blank to publish immediately (right now) the first time this post is marked Published.</small>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
            <a href="blog-posts" class="btn btn-link">Cancel</a>
        </form>

<script>
(function () {
  [['f-meta_title', 60], ['f-meta_description', 160], ['f-excerpt', 160]].forEach(function (c) {
    var el = document.getElementById(c[0]); if (!el) return;
    var out = document.createElement('small'); out.className = 'form-hint d-block'; out.setAttribute('aria-live', 'polite');
    el.parentNode.appendChild(out);
    function upd() { var n = el.value.length; out.textContent = n + ' characters' + (n > c[1] ? ' (search results usually cut off after about ' + c[1] + ')' : ' (about ' + c[1] + ' fit in a search result)'); }
    el.addEventListener('input', upd); upd();
  });
  var ta = document.querySelector('textarea[name=content]');
  if (!ta) return;
  function insert(text) {
    var s = ta.selectionStart, e = ta.selectionEnd;
    ta.value = ta.value.slice(0, s) + text + ta.value.slice(e);
    ta.focus(); ta.selectionStart = ta.selectionEnd = s + text.length;
  }
  var snips = {
    performer: function () { var n = prompt('Performer name (exactly as listed, e.g. Taylor Swift):'); return n ? '\n[events performer="' + n.replace(/"/g, '') + '" limit="6"]\n' : ''; },
    category: function () { var c = prompt('Category: concerts, sports, theatre or festival', 'concerts'); return c ? '\n[events category="' + c.replace(/"/g, '') + '" limit="6"]\n' : ''; },
    venue: function () { var n = prompt('Venue name (e.g. Madison Square Garden):'); return n ? '\n[events venue="' + n.replace(/"/g, '') + '" limit="6"]\n' : ''; },
    city: function () { var n = prompt('City name (e.g. Austin):'); if (!n) return ''; var c = prompt('Optional category: concerts, sports, theatre or festival (leave blank for all)', ''); return '\n[events city="' + n.replace(/"/g, '') + '"' + (c ? ' category="' + c.replace(/"/g, '') + '"' : '') + ' limit="6"]\n'; },
    newsletter: function () { return '\n[newsletter]\n'; },
    cta: function () { return '\n[cta title="Find tickets" text="Compare seats and prices." button="Browse tickets" url="/buy-tickets-online"]\n'; }
  };
  document.querySelectorAll('#blogTools [data-snip]').forEach(function (b) {
    b.addEventListener('click', function () { var t = snips[b.dataset.snip](); if (t) insert(t); });
  });
  document.querySelectorAll('#blogImages [data-img]').forEach(function (b) {
    b.addEventListener('click', function () {
      var alt = prompt('Describe the picture for accessibility and SEO (alt text):', '');
      if (alt === null) return;
      var cap = prompt('Caption (optional):', '') || '';
      insert('\n<figure><img src="' + b.dataset.img + '" alt="' + alt.replace(/"/g, '&quot;') + '" width="' + b.dataset.w + '" height="' + b.dataset.h + '" loading="lazy">' + (cap ? '<figcaption>' + cap + '</figcaption>' : '') + '</figure>\n');
    });
  });
})();
</script>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
