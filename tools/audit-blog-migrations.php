<?php

declare(strict_types=1);

/**
 * Summarize blog posts embedded in idempotent SQL migrations.
 *
 * Usage: php tools/audit-blog-migrations.php [--json]
 */

function splitSqlValues(string $values): array
{
    $parts = [];
    $buffer = '';
    $depth = 0;
    $quoted = false;
    $length = strlen($values);

    for ($index = 0; $index < $length; $index++) {
        $character = $values[$index];

        if ($character === "'" && ($index === 0 || $values[$index - 1] !== '\\')) {
            $quoted = !$quoted;
        }

        if (!$quoted) {
            if ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth--;
            } elseif ($character === ',' && $depth === 0) {
                $parts[] = trim($buffer);
                $buffer = '';
                continue;
            }
        }

        $buffer .= $character;
    }

    if (trim($buffer) !== '') {
        $parts[] = trim($buffer);
    }

    return $parts;
}

function decodeSqlValue(string $value): string
{
    if (preg_match('/^CONVERT\(0x([0-9a-f]+) USING utf8mb4\)$/i', $value, $matches)) {
        return (string) hex2bin($matches[1]);
    }

    if (preg_match("/^'(.*)'$/s", $value, $matches)) {
        return str_replace("''", "'", $matches[1]);
    }

    return $value;
}

function countArticleWords(string $html): int
{
    $text = strip_tags(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    return preg_match_all("/[\\p{L}\\p{N}]+(?:['’-][\\p{L}\\p{N}]+)*/u", $text) ?: 0;
}

$posts = [];
$migrationFiles = glob(__DIR__ . '/../db/migrations/*.sql') ?: [];
sort($migrationFiles);

foreach ($migrationFiles as $migrationFile) {
    $sql = file_get_contents($migrationFile);
    if ($sql === false) {
        continue;
    }

    preg_match_all(
        '/INSERT INTO `blog_posts` \((.*?)\) VALUES \((.*?)\)\s*ON DUPLICATE KEY UPDATE/s',
        $sql,
        $statements,
        PREG_SET_ORDER
    );

    foreach ($statements as $statement) {
        $columns = array_map(
            static fn (string $column): string => trim($column, " `\t\n\r\0\x0B"),
            explode(',', $statement[1])
        );
        $values = splitSqlValues($statement[2]);
        $row = [];

        foreach ($columns as $index => $column) {
            $row[$column] = decodeSqlValue($values[$index] ?? '');
        }

        if (($row['slug'] ?? '') === '') {
            continue;
        }

        $posts[$row['slug']] = [
            'migration' => basename($migrationFile),
            'slug' => $row['slug'],
            'title' => $row['title'] ?? '',
            'words' => countArticleWords($row['content'] ?? ''),
            'image' => $row['featured_image'] ?? '',
            'alt' => $row['featured_image_alt'] ?? '',
            'headings' => (static function (string $html): array {
                preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/is', $html, $matches);
                return array_map(
                    static fn (string $heading): string => trim(strip_tags($heading)),
                    $matches[1] ?? []
                );
            })($row['content'] ?? ''),
        ];
    }
}

// Apply later UPDATE migrations to the projected state produced by the seeds.
foreach ($migrationFiles as $migrationFile) {
    $sql = file_get_contents($migrationFile);
    if ($sql === false) {
        continue;
    }

    preg_match_all(
        "/UPDATE `blog_posts`\\s+SET (.*?)\\s+WHERE `slug` = '([^']+)';/s",
        $sql,
        $updates,
        PREG_SET_ORDER
    );

    foreach ($updates as $update) {
        $slug = str_replace("''", "'", $update[2]);
        if (!isset($posts[$slug])) {
            continue;
        }

        if (preg_match("/`featured_image` = '((?:''|[^'])*)'/", $update[1], $match)) {
            $posts[$slug]['image'] = str_replace("''", "'", $match[1]);
        }
        if (preg_match("/`featured_image_alt` = '((?:''|[^'])*)'/", $update[1], $match)) {
            $posts[$slug]['alt'] = str_replace("''", "'", $match[1]);
        }
        if (preg_match('/CONVERT\(0x([0-9a-f]+) USING utf8mb4\)/i', $update[1], $match)) {
            $posts[$slug]['words'] += countArticleWords((string) hex2bin($match[1]));
        }
    }
}

ksort($posts);

if (in_array('--json', $argv, true)) {
    echo json_encode(array_values($posts), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

printf("%-48s %7s %-46s %s\n", 'SLUG', 'WORDS', 'IMAGE', 'ALT');
foreach ($posts as $post) {
    printf(
        "%-48s %7d %-46s %s\n",
        $post['slug'],
        $post['words'],
        $post['image'],
        $post['alt'] === '' ? '[missing]' : $post['alt']
    );
}
