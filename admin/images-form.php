<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$existing = null;
if ($id > 0) {
    $stmt = MYSQLI->prepare('SELECT * FROM images WHERE ID = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    if (!$existing) {
        admin_flash_set('error', 'That image row no longer exists.');
        header('Location: images');
        exit;
    }
}

$values = [
    'entity_type' => $existing['entity_type'] ?? trim((string) ($_GET['type'] ?? 'artist')),
    'entity_name' => $existing['entity_name'] ?? trim((string) ($_GET['name'] ?? '')),
    'image_url'   => '',
    'attribution' => $existing['attribution'] ?? '',
    'license'     => $existing['license'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['entity_type'] = trim((string) ($_POST['entity_type'] ?? ''));
        $values['entity_name'] = trim((string) ($_POST['entity_name'] ?? ''));
        $values['image_url']   = trim((string) ($_POST['image_url'] ?? ''));
        $values['attribution'] = trim((string) ($_POST['attribution'] ?? ''));
        $values['license']     = trim((string) ($_POST['license'] ?? ''));

        if (!in_array($values['entity_type'], ['artist', 'team', 'venue', 'festival', 'city'], true)) $errors[] = 'Pick a valid type.';
        if ($values['entity_name'] === '') $errors[] = 'Name is required and must match the name TicketNetwork uses.';

        $imageContent = '';
        if (!empty($_FILES['image_file']['tmp_name']) && is_uploaded_file($_FILES['image_file']['tmp_name'])) {
            if ((int) $_FILES['image_file']['size'] > IMAGE_MAX_DOWNLOAD_BYTES) $errors[] = 'File is too large (15 MB max).';
            $mime = (string) (mime_content_type($_FILES['image_file']['tmp_name']) ?: '');
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) $errors[] = 'Upload a JPEG, PNG, WebP or GIF.';
            if (!$errors) $imageContent = (string) file_get_contents($_FILES['image_file']['tmp_name']);
        } elseif ($values['image_url'] !== '') {
            if (!filter_var($values['image_url'], FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $values['image_url'])) {
                $errors[] = 'Image URL must be an http(s) URL.';
            } else {
                $imageContent = (string) downloadImage($values['image_url']);
                if ($imageContent === '') $errors[] = 'Could not download that URL.';
            }
        } else {
            $errors[] = 'Upload a file or paste an image URL.';
        }

        if (!$errors) {
            $webp = resizeAndConvertToWebP(fixImageOrientation($imageContent), 1200, 82);
            if (!$webp) {
                $errors[] = 'That file is not a readable image.';
            } else {
                $slug = trim(preg_replace('/[^a-z0-9]+/i', '-', strtolower($values['entity_name'])), '-');
                $key  = imageStorageFolder($values['entity_type']) . '/' . $slug . '-manual-' . time() . '.webp';
                try {
                    uploadImageToS3($webp, $key, 'image/webp');
                } catch (Throwable $e) {
                    $errors[] = 'Upload to storage failed: ' . $e->getMessage();
                }
                if (!$errors) {
                    imageRecordUpsert([
                        'imgkey'      => imageEntityKey($values['entity_type'], $values['entity_name']),
                        'entity_type' => $values['entity_type'],
                        'entity_name' => $values['entity_name'],
                        'url'         => getS3PublicUrl($key),
                        'status'      => 'manual',
                        'source'      => 'admin',
                        'source_url'  => $values['image_url'] !== '' ? $values['image_url'] : null,
                        'license'     => $values['license'] !== '' ? $values['license'] : 'Licensed by Seat Outlet',
                        'attribution' => $values['attribution'] !== '' ? $values['attribution'] : null,
                        'attempts'    => (int) ($existing['attempts'] ?? 0),
                        'resolved_at' => date('Y-m-d H:i:s'),
                        'expires_at'  => null,
                    ]);
                    admin_flash_set('success', 'Image saved for ' . $values['entity_name'] . '.');
                    header('Location: images?q=' . urlencode($values['entity_name']));
                    exit;
                }
            }
        }
    }
}
if ($errors) admin_flash_set('error', implode(' ', $errors));

$pageTitle = 'Set Image — Seat Outlet Admin';
$currentPage = 'images';
include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3">
            <div class="col">
                <h2 class="page-title">Set image<?php echo $existing ? ': ' . htmlspecialchars($existing['entity_name'], ENT_QUOTES, 'UTF-8') : ''; ?></h2>
                <div class="text-secondary">A manual image wins over every automatic source and is never replaced by the cron. Only use images you own or have licensed.</div>
            </div>
        </div>

        <form method="post" action="images-form<?php echo $id > 0 ? '?id=' . $id : ''; ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
            <div class="card mb-3">
                <div class="card-body">
                    <?php if ($existing && !empty($existing['url'])): ?>
                        <div class="mb-3">
                            <label class="form-label">Current</label><br>
                            <img src="<?php echo htmlspecialchars($existing['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-height:160px" class="rounded">
                            <div class="text-secondary small mt-1"><?php echo htmlspecialchars(($existing['source'] ?? '') . ' · ' . ($existing['license'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Type</label>
                            <select name="entity_type" class="form-select" <?php echo $existing ? 'disabled' : ''; ?>>
                                <?php foreach (['artist', 'team', 'venue', 'festival', 'city'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $values['entity_type'] === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($existing): ?><input type="hidden" name="entity_type" value="<?php echo htmlspecialchars($values['entity_type'], ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
                        </div>
                        <div class="col-md-9 mb-3">
                            <label class="form-label">Name (exactly as TicketNetwork spells it)</label>
                            <input type="text" name="entity_name" class="form-control" required value="<?php echo htmlspecialchars($values['entity_name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $existing ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload image</label>
                        <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        <small class="form-hint">Resized to 1200px wide and converted to WebP. Landscape works best for cards and hero areas.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">…or image URL</label>
                        <input type="url" name="image_url" class="form-control" placeholder="https://" value="<?php echo htmlspecialchars($values['image_url'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">License</label>
                            <input type="text" name="license" class="form-control" placeholder="e.g. Press kit, Licensed stock, CC BY 4.0" value="<?php echo htmlspecialchars($values['license'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Credit shown on the page (optional)</label>
                            <input type="text" name="attribution" class="form-control" placeholder="Photo: Jane Doe" value="<?php echo htmlspecialchars($values['attribution'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save image</button>
            <a href="images" class="btn btn-link">Cancel</a>
        </form>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
