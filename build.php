<?php
// build.php

require_once __DIR__ . '/Parsedown.php';
// require_once __DIR__ . '/lib/DBTagManager.php'; // DB connection fails in CLI
require_once __DIR__ . '/lib/ArticleMetaManager.php';
require_once __DIR__ . '/lib/Renderer.php';

// Configuration
$articleDir = __DIR__ . '/article';
$metaFile = __DIR__ . '/data/article_meta.json';
$siteName = "先生、それ、重くないですか？";
$baseUrl = ''; // Root-relative

$articleMetaManager = new ArticleMetaManager($metaFile);
$articleMetaManager = new ArticleMetaManager($metaFile);
// $tagManager = new DBTagManager(); // CLI mismatch
$tagsJsonFile = __DIR__ . '/data/tags.json';
$articleTagsMap = [];
if (file_exists($tagsJsonFile)) {
    $content = file_get_contents($tagsJsonFile);
    $articleTagsMap = json_decode($content, true);
    echo "DEBUG: tags.json Path: " . realpath($tagsJsonFile) . "\n";
    echo "DEBUG: tags.json Size: " . strlen($content) . "\n";
    echo "DEBUG: tags.json MD5:  " . md5($content) . "\n";
    echo "DEBUG: Map Count: " . count($articleTagsMap) . "\n";
    echo "DEBUG: JSON Last Error: " . json_last_error_msg() . "\n";
} else {
    echo "WARNING: data/tags.json not found. Tags will be empty. Run dump_tags.php via browser first.\n";
}

$renderer = new Renderer($baseUrl);

echo "Starting build process (File-based Mode)...\n";

function ensureDir($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// Helper to get tags from content
function getTagsFromContent($content) {
    // Look for "Tags: Tag1, Tag2"
    if (preg_match('/^Tags:\s*(.*)/mi', $content, $matches)) {
        $tags = explode(',', $matches[1]);
        return array_map('trim', $tags);
    }
    return [];
}

// 1. Get All Articles & Collect All Tags
$files = glob($articleDir . '/*.md');
$articles = [];
$allTagsCounter = [];

foreach ($files as $file) {
    $filename = basename($file);
    $meta = getArticleMetadataBuild($filename, $articleDir, $articleMetaManager, $articleTagsMap);
    
    if ($meta && isArticleVisibleBuild($meta)) {
        $articles[] = $meta;
        foreach ($meta['tags'] as $t) {
            if (!isset($allTagsCounter[$t])) $allTagsCounter[$t] = 0;
            $allTagsCounter[$t]++;
        }
    }
}
ksort($allTagsCounter);
$allTags = array_keys($allTagsCounter);

// 2. Build Article Pages
foreach ($articles as $article) {
    echo "Building article: " . $article['title'] . "\n";
    
    $slug = $article['filename'];
    $outDir = __DIR__ . '/' . $slug;
    ensureDir($outDir);
    
    // Render Content
    $htmlContent = $renderer->render($article['content']);
    $relatedByTag = getRelatedArticles($article, $articles);

    // Prepare variables for view
    $pageTitle = $article['title'] . ' - ' . $siteName;
    $pageDescription = $article['description'];
    $pageCanonical = '/' . $slug; 
    $extraScripts = '<script src="' . $baseUrl . '/js/pwa.js" defer></script>';

    // Buffer output
    ob_start();
    include __DIR__ . '/views/parts/head.php';
    include __DIR__ . '/views/parts/header.php';
    ?>
    <main class="container">
        <article class="post">
            <header class="post-header">
                <h1><?php echo htmlspecialchars($article['title']); ?></h1>
                <div class="post-meta">
                    <span class="tags-label">Tags:
                        <?php
                        $tagLinks = array_map(function ($t) use ($baseUrl) {
                            return '<a href="' . $baseUrl . '/tag/' . urlencode($t) . '" class="tag-link">' . htmlspecialchars($t) . '</a>';
                        }, $article['tags'] ?? []);
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
            <section class="related-posts-container">
            <?php foreach ($relatedByTag as $tag => $posts): ?>
                <?php if (!empty($posts)): ?>
                    <div class="related-tag-section">
                        <h3><?php echo htmlspecialchars($tag); ?>に関連する記事</h3>
                        <div class="related-list">
                            <?php foreach ($posts as $post): ?>
                                <div class="related-card">
                                    <a href="<?php echo $baseUrl; ?>/<?php echo htmlspecialchars($post['filename']); ?>" class="related-card-link">
                                        <div class="related-card-image">
                                            <?php if ($post['thumbnail']): ?>
                                                <img src="<?php echo (strpos($post['thumbnail'], 'http') === 0 ? '' : $baseUrl) . htmlspecialchars($post['thumbnail']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy">
                                            <?php else: ?>
                                                <div class="no-image">No Image</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="related-card-content">
                                            <h4 class="related-card-title"><?php echo htmlspecialchars($post['title']); ?></h4>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            </section>
        </article>
    </main>
    <?php include __DIR__ . '/views/parts/footer.php'; ?>
    <?php
    $finalHtml = ob_get_clean();
    file_put_contents($outDir . '/index.html', $finalHtml);
}

// 3. Build Homepage (index.html)
echo "Building Homepage...\n";
$pageTitle = $siteName;
$pageDescription = "A blog about mountain gear, hiking tips, and outdoor adventures.";
$pageCanonical = '/';
$activeTag = 'all';
// Shuffle for random homepage
shuffle($articles);

ob_start();
include __DIR__ . '/views/parts/head.php';
include __DIR__ . '/views/parts/header.php';
?>
<main class="container"> 
    <div class="filter-section">
        <input type="text" id="searchInput" placeholder="キーワードで検索..." class="search-input">
        <div class="tag-accordion-container">
            <div class="tag-filter tag-accordion" id="tagFilter">
                <button class="tag-btn active" data-tag="all">All</button>
                <?php foreach ($allTags as $tag): ?>
                    <button class="tag-btn" data-tag="<?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars($tag); ?></button>
                <?php endforeach; ?>
            </div>
            <button id="showMoreTags" class="show-more-tags">もっと見る</button>
        </div>
    </div>
    
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
                    <?php foreach ($article['tags'] as $tag): ?>
                        <a href="<?php echo $baseUrl; ?>/tag/<?php echo urlencode($tag); ?>" class="card-tag">#<?php echo htmlspecialchars($tag); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
    <div id="noResults" style="display: none; text-align: center; margin-top: 2rem;">該当する記事は見つかりませんでした。</div>
</main>
<?php 
$extraScripts = '<script src="' . $baseUrl . '/js/home.js" defer></script><script src="' . $baseUrl . '/js/pwa.js" defer></script>'; 
include __DIR__ . '/views/parts/footer.php'; 
?>
<?php
$indexHtml = ob_get_clean();
file_put_contents(__DIR__ . '/index.html', $indexHtml);

// 4. Generate articles.json for search
echo "Generating articles.json...\n";
$jsonArticles = [];
foreach ($articles as $article) {
    $jsonArticles[] = [
        'title' => $article['title'],
        'filename' => $article['filename'],
        'thumbnail' => $article['thumbnail'],
        'tags' => $article['tags']
    ];
}
file_put_contents(__DIR__ . '/js/articles.json', json_encode($jsonArticles));

echo "Build Complete!\n";


// --- Helpers ---

function getRelatedArticles($currentArticle, $allArticles) {
    $related = [];
    foreach ($currentArticle['tags'] as $tag) {
        $related[$tag] = [];
        foreach ($allArticles as $a) {
            if ($a['filename'] === $currentArticle['filename']) continue;
            if (in_array($tag, $a['tags'])) {
                $related[$tag][] = $a;
            }
        }
        $related[$tag] = array_slice($related[$tag], 0, 5);
    }
    return $related;
}

function isArticleVisibleBuild($article) {
    if ($article['status'] === 'private') return false;
    if ($article['published_at']) {
        if (strtotime($article['published_at']) > time()) return false;
    }
    return true;
}

function getArticleMetadataBuild($filename, $articleDir, $articleMetaManager, $articleTagsMap) {
    $filePath = $articleDir . '/' . $filename;
    if (!file_exists($filePath)) return null;
    $content = file_get_contents($filePath);
    
    preg_match('/^#\s+(.*)/m', $content, $titleMatch);
    $title = $titleMatch ? trim($titleMatch[1]) : str_replace('.md', '', $filename);
    
    preg_match('/!\[.*?\]\((.*?)\)/', $content, $imageMatch);
    $thumbnail = $imageMatch ? $imageMatch[1] : null;
    
    // Normalize Thumbnail
    if ($thumbnail && strpos($thumbnail, 'http') !== 0) {
        $cleanPath = ltrim($thumbnail, '/');
         if (file_exists(dirname($articleDir) . '/' . $cleanPath)) {
             $thumbnail = '/' . $cleanPath;
         } elseif (file_exists(dirname($articleDir) . '/img/' . $cleanPath)) {
             $thumbnail = '/img/' . $cleanPath;
         }
    }
    
    // Parse Tags from JSON
    $tags = $articleTagsMap[$filename] ?? [];
    if (strpos($filename, 'montbell_fleece') !== false) {
        echo "DEBUG: Filename [$filename]\n";
        echo "DEBUG: Map Entry Exists? " . (isset($articleTagsMap[$filename]) ? "YES" : "NO") . "\n";
        echo "DEBUG: Tags Count: " . count($tags) . "\n";
        if (isset($articleTagsMap[$filename])) {
             print_r($articleTagsMap[$filename]);
        } else {
             echo "DEBUG: Map Keys Sample:\n";
             $keys = array_keys($articleTagsMap);
             print_r(array_slice($keys, 0, 5));
             // Check fuzzy
             foreach ($keys as $k) {
                 if (strpos($k, 'montbell_fleece') !== false) {
                     echo "DEBUG: Found close match key: [$k]\n";
                 }
             }
        }
    }
    
    $meta = $articleMetaManager->getMeta($filename);
    $published_at = $meta['published_at'];
    $status = $meta['status'];
    
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
        'published_at' => $published_at,
        'status' => $status,
        'description' => $description,
        'content' => $content
    ];
}
