<?php
session_start();
require_once __DIR__ . '/Parsedown.php';
require_once __DIR__ . '/lib/DBTagManager.php';
require_once __DIR__ . '/lib/ArticleMetaManager.php';
require_once __DIR__ . '/lib/Renderer.php';

// Configuration
$articleDir = __DIR__ . '/article';
$metaFile = __DIR__ . '/data/article_meta.json';
$siteName = "先生、それ、重くないですか？";

$tagManager = new DBTagManager();
$articleMetaManager = new ArticleMetaManager($metaFile);

// Base URL calculation
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
// Normalize scriptDir to forward slashes and remove trailing slash
$scriptDir = str_replace('\\', '/', $scriptDir);
if ($scriptDir === '/') $scriptDir = '';
$baseUrl = "$protocol://$host$scriptDir";

$renderer = new Renderer($baseUrl);

// helper function
function getArticleMetadata($filename, $articleDir, $articleMetaManager, $tagManager)
{
    $filePath = $articleDir . '/' . $filename;
    if (!file_exists($filePath)) return null;
    $content = file_get_contents($filePath);

    preg_match('/^#\s+(.*)/m', $content, $titleMatch);
    $title = $titleMatch ? trim($titleMatch[1]) : str_replace('.md', '', $filename);

    preg_match('/!\[.*?\]\((.*?)\)/', $content, $imageMatch);
    $thumbnail = $imageMatch ? $imageMatch[1] : null;

    // Normalize thumbnail path
    if ($thumbnail && strpos($thumbnail, 'http') !== 0) {
        $cleanPath = ltrim($thumbnail, '/');
        // Check if exists in root or img/
        if (file_exists(dirname($articleDir) . '/' . $cleanPath)) {
            $thumbnail = '/' . $cleanPath;
        } elseif (file_exists(dirname($articleDir) . '/img/' . $cleanPath)) {
            $thumbnail = '/img/' . $cleanPath;
        }
    }

    // Parse tags from DB
    $tags = $tagManager->getTags($filename);

    $meta = $articleMetaManager->getMeta($filename);

    $plainText = preg_replace('/^#\s+.*\n/', '', $content);
    $plainText = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $plainText);
    $plainText = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $plainText);
    $plainText = strip_tags($plainText);
    $description = mb_substr(trim($plainText), 0, 160) . '...';

    return [
        'title' => $title,
        'filename' => str_replace('.md', '', $filename),
        'thumbnail' => $thumbnail,
        'tags' => $tags,
        'published_at' => $meta['published_at'],
        'status' => $meta['status'],
        'description' => $description,
        'content' => $content
    ];
}


// Router Logic
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove script dir prefix if present
if ($scriptDir !== '' && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}

// 0. Static Pages
if ($path === '/about' || $path === '/about/') {
    include __DIR__ . '/views/about.php';
    exit;
}

// 1. Tag Page: /tag/TagName
if (preg_match('#^/tag/([^/?]+)#', $path, $matches)) {
    $tagName = urldecode($matches[1]);
    $pageTitle = "Tag: " . htmlspecialchars($tagName) . " - $siteName";
    $pageDescription = "Articles tagged with $tagName";
    $pageCanonical = "/tag/" . urlencode($tagName);

    $filenames = $tagManager->getArticlesByTag($tagName);
    $articles = [];
    foreach ($filenames as $f) {
        if (substr($f, -3) !== '.md') $f .= '.md';
        $meta = getArticleMetadata($f, $articleDir, $articleMetaManager, $tagManager);
        if ($meta && ($meta['status'] !== 'private' || (isset($meta['published_at']) && strtotime($meta['published_at']) > time()))) {
            // In build.php private check was: if status == private return false.
            // Here we just skip.
            // Wait, published_at logic in build.php: if (published > time) return false.
            if ($meta['status'] === 'private') continue;
            if ($meta['published_at'] && strtotime($meta['published_at']) > time()) continue;
            $articles[] = $meta;
        }
    }

    // View
    ob_start();
    include __DIR__ . '/views/parts/head.php';
    include __DIR__ . '/views/parts/header.php';
?>
    <main class="container">
        <h2>Tag: <?php echo htmlspecialchars($tagName); ?></h2>
        <div class="article-grid" id="articleGrid">
            <?php foreach ($articles as $article):
                $thumbnailUrl = $article['thumbnail'] ? ((strpos($article['thumbnail'], 'http') === 0 ? '' : $baseUrl) . htmlspecialchars($article['thumbnail'])) : '';
            ?>
                <article class="article-card" data-tags="<?php echo htmlspecialchars(json_encode($article['tags'])); ?>" data-title="<?php echo htmlspecialchars($article['title']); ?>">
                    <a href="<?php echo $baseUrl; ?>/<?php echo htmlspecialchars($article['filename']); ?>" class="card-link-image">
                        <div class="card-image">
                            <?php if ($thumbnailUrl): ?>
                                <img src="<?php echo $thumbnailUrl; ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="card-content">
                        <a href="<?php echo $baseUrl; ?>/<?php echo htmlspecialchars($article['filename']); ?>" class="card-link-title">
                            <h2 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h2>
                        </a>
                        <div class="card-tags">
                            <?php foreach ($article['tags'] as $t): ?>
                                <a href="<?php echo $baseUrl; ?>/tag/<?php echo urlencode($t); ?>" class="card-tag">#<?php echo htmlspecialchars($t); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (empty($articles)): ?>
            <p>No articles found for this tag.</p>
        <?php endif; ?>
    </main>
    <?php
    $extraScripts = '<script src="' . $baseUrl . '/js/home.js" defer></script><script src="' . $baseUrl . '/js/pwa.js" defer></script>';
    include __DIR__ . '/views/parts/footer.php';
    ob_end_flush();
    exit;
} elseif (preg_match('#^/([^/?\.]+)$#', $path, $matches)) {
    // 2. Article Fallback (e.g. /slug)
    // Exclude index.php itself if matched regex (though standard req involves extension usually handled by webserver)
    $slug = $matches[1];

    // Avoid matching 'index.php' or static assets if regex is loose, but [^/?\.]+ excludes dots usually.
    // If slug has no dot, it matches.

    $filename = $slug . '.md';
    if (file_exists($articleDir . '/' . $filename)) {
        $meta = getArticleMetadata($filename, $articleDir, $articleMetaManager, $tagManager);
        if ($meta && $meta['status'] !== 'private') {
            $pageTitle = $meta['title'] . ' - ' . $siteName;
            $pageDescription = $meta['description'];
            $pageCanonical = '/' . $slug;

            $htmlContent = $renderer->render($meta['content']);

            // View
            ob_start();
            include __DIR__ . '/views/parts/head.php';
            include __DIR__ . '/views/parts/header.php';
    ?>
            <main class="container">
                <article class="post">
                    <header class="post-header">
                        <h1><?php echo htmlspecialchars($meta['title']); ?></h1>
                        <div class="post-meta">
                            <span class="tags-label">Tags:
                                <?php
                                $tagLinks = array_map(function ($t) use ($baseUrl) {
                                    return '<a href="' . $baseUrl . '/tag/' . urlencode($t) . '" class="tag-link">' . htmlspecialchars($t) . '</a>';
                                }, $meta['tags'] ?? []);
                                echo implode(', ', $tagLinks);
                                ?>
                            </span>
                        </div>
                    </header>
                    <div class="post-body-container">
                        <div class="post-content">
                            <?php echo $htmlContent; ?>
                        </div>
                        <aside class="toc-sidebar">
                            <div class="toc-sidebar-inner"></div>
                        </aside>
                    </div>
                    <!-- Related section omitted for fallback simplicity, or TODO: implement -->
                </article>
            </main>
<?php
            $extraScripts = '<script src="' . $baseUrl . '/js/pwa.js" defer></script>';
            include __DIR__ . '/views/parts/footer.php';
            ob_end_flush();
            exit;
        }
    }
}

// 3. Default / 404
// If path is root '/', it usually hits index.html. If hits index.php, we can render home dynamic or just redirect.
if ($path === '/' || $path === '') {
    // Dynamic Home Fallback?
    // Let's just 404 for now to avoid duplications if index.html is expected.
    // Or render home.
    // Given the task is SSG, dynamic home is less critical.
    // But if index.html is missing, better to show something.
    // Serve static/index.html if exists
    $staticHome = __DIR__ . '/static/index.html';
    if (file_exists($staticHome)) {
        // Prevent Caching
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        readfile($staticHome);
        exit;
    }
    echo "Dynamic Home Fallback Placeholder (static/index.html not found)";
} else {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The requested page could not be found.</p><a href='/'>Go Home</a></body></html>";
}
