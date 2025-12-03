<?php
session_start();
require 'Parsedown.php';
require_once __DIR__ . '/lib/TagManager.php';
require_once __DIR__ . '/lib/ArticleMetaManager.php';

// Configuration
$articleDir = __DIR__ . '/article';
$dataFile = __DIR__ . '/data/tags.json';
$metaFile = __DIR__ . '/data/article_meta.json';
$siteName = "先生、それ、重くないですか？"; // Site Name Variable

$tagManager = new TagManager($dataFile);
$articleMetaManager = new ArticleMetaManager($metaFile);

// Initialize variables to prevent warnings
$pageTitle = '';
$pageDescription = '';
$pageCanonical = '';
$extraScripts = '';
$article = null;
$relatedByTag = [];


// Calculate Base URL dynamically to support subdirectories
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$scriptDir = str_replace('\\', '/', dirname($scriptName));
if ($scriptDir === '/') {
    $scriptDir = '';
}
$baseUrl = "$protocol://$host$scriptDir";

// Helper: Parse Dialogue
function parseDialogue($content) {
    global $baseUrl;
    $lines = explode("\n", $content);
    $processedLines = [];
    $Parsedown = new Parsedown();

    $currentSpeaker = null;
    $currentMessageLines = [];
    $currentType = null;
    $currentIconHtml = null;

    foreach ($lines as $line) {
        if (preg_match('/^\s*\*\*(.+?)\*\*:\s*(.*)/', $line, $matches)) {
            // Found a new speaker line
            
            // 1. Close previous speaker if exists
            if ($currentSpeaker) {
                $fullMessage = implode("\n", $currentMessageLines);
                $renderedMessage = $Parsedown->line($fullMessage);
                
                $html = "
<div class=\"chat-row $currentType\">
    <div class=\"icon $currentType\">$currentIconHtml</div>
    <div class=\"bubble\">
        <div class=\"message\">$renderedMessage</div>
    </div>
</div>";
                $processedLines[] = $html;
            }

            // 2. Setup new speaker
            $currentSpeaker = $matches[1];
            $currentMessageLines = [$matches[2]]; // Start with the message part of the first line
            
            $currentType = 'other';
            $currentIconHtml = mb_substr($currentSpeaker, 0, 1);

            if (strpos($currentSpeaker, '先生') !== false) {
                $currentType = 'teacher';
                $currentIconHtml = '<img src="' . $baseUrl . '/img/teacher.png" alt="先生">';
            } elseif (strpos($currentSpeaker, 'JK') !== false || strpos($currentSpeaker, '生徒') !== false) {
                $currentType = 'student';
                $currentIconHtml = '<img src="' . $baseUrl . '/img/jk.png" alt="JK">';
            }
            
        } elseif ($currentSpeaker) {
            // We are inside a dialogue
            if (trim($line) === '') {
                // Blank line ends the dialogue
                $fullMessage = implode("\n", $currentMessageLines);
                $renderedMessage = $Parsedown->line($fullMessage); 
                
                $html = "
<div class=\"chat-row $currentType\">
    <div class=\"icon $currentType\">$currentIconHtml</div>
    <div class=\"bubble\">
        <div class=\"message\">$renderedMessage</div>
    </div>
</div>";
                $processedLines[] = $html;
                
                $currentSpeaker = null;
                $currentMessageLines = [];
                $processedLines[] = $line; // Keep the blank line
            } else {
                // Continuation of the message
                $currentMessageLines[] = $line;
            }
        } else {
            // Normal text
            $processedLines[] = $line;
        }
    }

    // Flush last speaker if exists
    if ($currentSpeaker) {
        $fullMessage = implode("\n", $currentMessageLines);
        $renderedMessage = $Parsedown->line($fullMessage);
        $html = "
<div class=\"chat-row $currentType\">
    <div class=\"icon $currentType\">$currentIconHtml</div>
    <div class=\"bubble\">
        <div class=\"message\">$renderedMessage</div>
    </div>
</div>";
        $processedLines[] = $html;
    }

    return implode("\n", $processedLines);
}

// Helper: Get Article Metadata
function getArticleMetadata($filename) {
    global $articleDir, $tagManager, $articleMetaManager;
    $filePath = $articleDir . '/' . $filename;
    if (!file_exists($filePath)) {
        return null;
    }
    $content = file_get_contents($filePath);

    // Extract Title
    preg_match('/^#\s+(.*)/m', $content, $titleMatch);
    $title = $titleMatch ? trim($titleMatch[1]) : str_replace('.md', '', $filename);



    // Extract First Image URL
    preg_match('/!\[.*?\]\((.*?)\)/', $content, $imageMatch);
    $thumbnail = $imageMatch ? $imageMatch[1] : null;

    // Extract Tags from TagManager
    $tags = $tagManager->getTags($filename);
    
    // Extract Meta from ArticleMetaManager
    $meta = $articleMetaManager->getMeta($filename);
    $published_at = $meta['published_at'];
    $status = $meta['status'];
    
    // Add Category
    $filenameBase = str_replace('.md', '', $filename);
    $category = 'General';
    if (strpos($filenameBase, 'guide') !== false) $category = 'Guide';
    elseif (strpos($filenameBase, 'review') !== false) $category = 'Review';
    elseif (strpos($filenameBase, 'comparison') !== false) $category = 'Comparison';

    // Extract Description (first ~160 chars)
    $plainText = preg_replace('/^#\s+.*\n/', '', $content);
    $plainText = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $plainText);
    $plainText = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $plainText);
    $plainText = strip_tags($plainText);
    $description = mb_substr(trim($plainText), 0, 160) . '...';

    return [
        'title' => $title,
        'filename' => $filenameBase,

        'thumbnail' => $thumbnail,
        'tags' => $tags,
        'published_at' => $published_at,
        'status' => $status,
        'category' => $category,
        'description' => $description,
        'content' => $content
    ];
}

// Helper: Check if article is visible
function isArticleVisible($article) {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        return true;
    }
    
    if ($article['status'] === 'private') {
        return false;
    }
    
    if ($article['published_at']) {
        $publishTime = strtotime($article['published_at']);
        if ($publishTime > time()) {
            return false;
        }
    }
    
    return true;
}

// Router
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Normalize path for subdirectory support
$scriptName = $_SERVER['SCRIPT_NAME'];
$scriptDir = str_replace('\\', '/', dirname($scriptName));

// If the request path starts with the script directory, remove it
if ($scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}

$path = trim($path, '/');
error_log("Debug Path: [" . $path . "]");

// If path is 'index.php', treat it as empty (home)
if ($path === 'index.php') {
    $path = '';
}

// About Page
if ($path === 'about') {
    include 'views/about.php';
    exit;
}

// Tag Page
if (preg_match('/^tag\/(.+)$/', $path, $matches)) {
    $tagName = urldecode($matches[1]);
    $files = glob($articleDir . '/*.md');
    $articles = [];
    foreach ($files as $file) {
        $meta = getArticleMetadata(basename($file));
        if ($meta && in_array($tagName, $meta['tags']) && isArticleVisible($meta)) {
            $articles[] = $meta;
        }
    }

    $pageTitle = 'タグ: ' . $tagName . ' - ' . $siteName;
    $pageCanonical = '/tag/' . urlencode($tagName);
    include 'views/parts/head.php';
    include 'views/parts/header.php';

    echo '<main class="container">';
    echo '<h1>タグ: ' . htmlspecialchars($tagName) . ' の記事一覧</h1>';
    echo '<ul class="article-list">';
    if (empty($articles)) {
        echo '<li>該当する記事は見つかりませんでした。</li>';
    } else {
        foreach ($articles as $article) {
            echo '<li>';
            echo '<a href="' . $baseUrl . '/' . htmlspecialchars($article['filename']) . '">' . htmlspecialchars($article['title']) . '</a>';
            echo '<div class="meta">';
            echo '<span class="tags">Tags: ';
            $tagLinks = array_map(function($t) use ($baseUrl) {
                return '<a href="' . $baseUrl . '/tag/' . urlencode($t) . '" class="tag-link">' . htmlspecialchars($t) . '</a>';
            }, $article['tags']);
            echo implode(', ', $tagLinks);
            echo '</span>';
            echo '</div>';
            echo '</li>';
        }
    }
    echo '</ul>';
    echo '</main>';

    include 'views/parts/footer.php';
    exit;
}

// Home Page
if ($path === '') {
    $files = glob($articleDir . '/*.md');
    $articles = [];
    $allTags = array_keys($tagManager->getAllTags());

    foreach ($files as $file) {
        $meta = getArticleMetadata(basename($file));
        if ($meta && isArticleVisible($meta)) {
            $articles[] = $meta;
        }
    }

    // Shuffle articles for random order
    shuffle($articles);

    // Render Index
    $pageTitle = $siteName;
    $pageDescription = "A blog about mountain gear, hiking tips, and outdoor adventures.";
    $pageCanonical = '/';
    include 'views/parts/head.php';
    include 'views/parts/header.php';

    echo '<main class="container">';
    echo '<div class="filter-section">';
    echo '<input type="text" id="searchInput" placeholder="キーワードで検索..." class="search-input">';
    echo '<div class="tag-accordion-container">';
    echo '<div class="tag-filter tag-accordion" id="tagFilter">';
    echo '<button class="tag-btn active" data-tag="all">All</button>';
    foreach ($allTags as $tag) {
        echo '<button class="tag-btn" data-tag="' . htmlspecialchars($tag) . '">' . htmlspecialchars($tag) . '</button>';
    }
    echo '</div>';
    echo '<button id="showMoreTags" class="show-more-tags">もっと見る</button>';
    echo '</div>';
    echo '</div>';

    echo '<div class="article-grid" id="articleGrid">';
    foreach ($articles as $article) {
        $thumbnailUrl = $article['thumbnail'] ? ((strpos($article['thumbnail'], 'http') === 0 ? '' : $baseUrl) . htmlspecialchars($article['thumbnail'])) : '';
        
        echo '<article class="article-card" data-tags="' . htmlspecialchars(json_encode($article['tags'])) . '" data-title="' . htmlspecialchars($article['title']) . '">';
        echo '<a href="' . $baseUrl . '/' . htmlspecialchars($article['filename']) . '" class="card-link">';
        echo '<div class="card-image">';
        if ($thumbnailUrl) {
            echo '<img src="' . $thumbnailUrl . '" alt="' . htmlspecialchars($article['title']) . '" loading="lazy">';
        } else {
            echo '<div class="no-image">No Image</div>';
        }
        echo '</div>';
        echo '<div class="card-content">';
        echo '<h2 class="card-title">' . htmlspecialchars($article['title']) . '</h2>';
        echo '<div class="card-tags">';
        foreach ($article['tags'] as $tag) {
            echo '<span class="card-tag">#' . htmlspecialchars($tag) . '</span>';
        }
        echo '</div>';
        echo '</div>';
        echo '</a>';
        echo '</article>';
    }
    echo '</div>';
    echo '<div id="noResults" style="display: none; text-align: center; margin-top: 2rem;">該当する記事は見つかりませんでした。</div>';
    echo '</main>';

    $extraScripts = '<script src="' . $baseUrl . '/js/home.js"></script>';
    include 'views/parts/footer.php';
    die();
}

// Article Page
$slug = $path;
$filename = $slug . '.md';
$article = getArticleMetadata($filename);

if (!$article || !isArticleVisible($article)) {
    http_response_code(404);
    echo "Article not found";
    exit;
}

// Find Related Articles Grouped by Tag
$files = glob($articleDir . '/*.md');
$relatedByTag = [];

foreach ($article['tags'] as $tag) {
    $relatedByTag[$tag] = [];
    foreach ($files as $file) {
        $fName = basename($file);
        if ($fName !== $filename) {
            $meta = getArticleMetadata($fName);
            if ($meta && in_array($tag, $meta['tags']) && isArticleVisible($meta)) {
                $relatedByTag[$tag][] = $meta;
            }
        }
    }
    // Limit to 5 articles per tag
    $relatedByTag[$tag] = array_slice($relatedByTag[$tag], 0, 5);
}

// Process Content
$contentBody = preg_replace('/^#\s+.*\n/', '', $article['content']);
$contentBody = parseDialogue($contentBody);
$Parsedown = new Parsedown();
$htmlContent = $Parsedown->text($contentBody);
$htmlContent = str_replace('src="/img/', 'src="' . $baseUrl . '/img/', $htmlContent);

?>
<?php
    $pageTitle = $article['title'] . ' - ' . $siteName;
    $pageDescription = $article['description'];
    $pageCanonical = '/' . $article['filename'];
    include 'views/parts/head.php';
    include 'views/parts/header.php';
?>

    <main class="container">
        <article class="post">
            <header class="post-header">
                <h1><?php echo htmlspecialchars($article['title']); ?></h1>
                <div class="post-meta">

                    <span class="tags-label">Tags: 
                        <?php 
                        $tagLinks = array_map(function($t) use ($baseUrl) {
                            return '<a href="' . $baseUrl . '/tag/' . urlencode($t) . '" class="tag-link">' . htmlspecialchars($t) . '</a>';
                        }, $article['tags'] ?? []);
                        echo implode(', ', $tagLinks); 
                        ?>
                    </span>
                </div>

            </header>
            <div class="post-content">
                <?php echo $htmlContent; ?>
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

<?php include 'views/parts/footer.php'; ?>
