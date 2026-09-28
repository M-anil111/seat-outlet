-- Blog (see admin/blog-posts.php, blog.php, blog-post.php). Deliberately a
-- single-table design (no separate categories/tags/comments tables) - a
-- flat, chronological blog is what was actually asked for; those would be
-- real additions to make later if content strategy needs them, not
-- speculative schema added now on the chance they might.
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `excerpt` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_general_ci NOT NULL
    COMMENT 'Raw HTML, rendered as-is on blog-post.php - written by an admin, not user-submitted',
  `featured_image` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `author_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_description` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('draft','published') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `blog_posts_slug_unique` (`slug`),
  KEY `blog_posts_status_published_at` (`status`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
