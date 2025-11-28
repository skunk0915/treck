<?php
require 'Parsedown.php';

// Configuration
$articleDir = __DIR__ . '/article';
$siteName = "先生、それ、重くないですか？"; // Site Name Variable

// Calculate Base URL dynamically to support subdirectories
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
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

    foreach ($lines as $line) {
        if (preg_match('/^\s*\*\*(.+?)\*\*:\s*(.*)/', $line, $matches)) {
            $name = $matches[1];
            $message = $matches[2];
            $type = 'other';
            $iconText = mb_substr($name, 0, 1);

            if (strpos($name, '先生') !== false) {
                $type = 'teacher';
                $iconHtml = '<img src="' . $baseUrl . '/img/teacher.png" alt="先生">';
            } elseif (strpos($name, 'JK') !== false || strpos($name, '生徒') !== false) {
                $type = 'student';
                $iconHtml = '<img src="' . $baseUrl . '/img/jk.png" alt="JK">';
            } else {
                 $iconHtml = mb_substr($name, 0, 1);
            }

            $renderedMessage = $Parsedown->line($message);

            $html = "
<div class=\"chat-row $type\">
    <div class=\"icon $type\">$iconHtml</div>
    <div class=\"bubble\">
        <div class=\"message\">$renderedMessage</div>
    </div>
</div>";
            $processedLines[] = $html;
        } else {
            $processedLines[] = $line;
        }
    }
    return implode("\n", $processedLines);

    
}

// Helper: Get Article Metadata
function getArticleMetadata($filename) {
    global $articleDir;
    $filePath = $articleDir . '/' . $filename;
    if (!file_exists($filePath)) {
        return null;
    }
    $content = file_get_contents($filePath);

    // Extract Title
    preg_match('/^#\s+(.*)/m', $content, $titleMatch);
    $title = $titleMatch ? trim($titleMatch[1]) : str_replace('.md', '', $filename);

    // Extract Image Prompt
    preg_match('/>\s*\*\*Image Prompt:\*\*\s*(.*)/', $content, $imagePromptMatch);
    $imagePrompt = $imagePromptMatch ? trim($imagePromptMatch[1]) : null;

    // Generate Tags
    $filenameBase = str_replace('.md', '', $filename);
    $parts = explode('_', $filenameBase);
    $tags = array_filter($parts, function($t) {
        return !in_array($t, ['guide', 'article', 'review', 'comparison']);
    });
    
    // Add Category
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
        'imagePrompt' => $imagePrompt,
        'tags' => array_values($tags),
        'category' => $category,
        'description' => $description,
        'content' => $content
    ];
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
        if ($meta && in_array($tagName, $meta['tags'])) {
            $articles[] = $meta;
        }
    }

    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タグ: <?php echo htmlspecialchars($tagName); ?> - <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="canonical" href="<?php echo $baseUrl; ?>/tag/<?php echo htmlspecialchars($tagName); ?>">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <a href="<?php echo $baseUrl; ?>/" class="logo"><?php echo htmlspecialchars($siteName); ?></a>
            <nav>
                <ul>
                    <li><a href="<?php echo $baseUrl; ?>/">Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>/about">About</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>タグ: <?php echo htmlspecialchars($tagName); ?> の記事一覧</h1>
        <ul class="article-list">
            <?php if (empty($articles)): ?>
                <li>該当する記事は見つかりませんでした。</li>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <li>
                        <a href="<?php echo $baseUrl; ?>/<?php echo htmlspecialchars($article['filename']); ?>"><?php echo htmlspecialchars($article['title']); ?></a>
                        <div class="meta">
                            <span class="tags">Tags: 
                                <?php 
                                $tagLinks = array_map(function($t) use ($baseUrl) {
                                    return '<a href="' . $baseUrl . '/tag/' . urlencode($t) . '" class="tag-link">' . htmlspecialchars($t) . '</a>';
                                }, $article['tags']);
                                echo implode(', ', $tagLinks); 
                                ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 <?php echo htmlspecialchars($siteName); ?></p>
        </div>
    </footer>
</body>
</html>
    <?php
    exit;
}

// Home Page
if ($path === '') {
    $files = glob($articleDir . '/*.md');
    $articles = [];
    foreach ($files as $file) {
        $meta = getArticleMetadata(basename($file));
        if ($meta) {
            $articles[] = $meta;
        }
    }

    // Group by Category
    $categories = array_unique(array_column($articles, 'category'));
    $articlesByCategory = [];
    foreach ($categories as $cat) {
        $articlesByCategory[$cat] = array_filter($articles, function($a) use ($cat) {
            return $a['category'] === $cat;
        });
    }

    // Render Index
    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?></title>
    <meta name="description" content="A blog about mountain gear, hiking tips, and outdoor adventures.">
    <link rel="canonical" href="<?php echo $baseUrl; ?>/">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <a href="<?php echo $baseUrl; ?>/" class="logo"><?php echo htmlspecialchars($siteName); ?></a>
            <nav>
                <ul>
                    <li><a href="<?php echo $baseUrl; ?>/">Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>/about">About</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1><?php echo htmlspecialchars($siteName); ?> 記事一覧</h1>
        <div class="categories">
            <?php foreach ($categories as $cat): ?>
                <section class="category-section">
                    <h2><?php echo htmlspecialchars($cat); ?></h2>
                    <ul class="article-list">
                        <?php foreach ($articlesByCategory[$cat] as $article): ?>
                            <li>
                                <a href="<?php echo $baseUrl; ?>/<?php echo htmlspecialchars($article['filename']); ?>"><?php echo htmlspecialchars($article['title']); ?></a>
                                <div class="meta">
                                    <span class="tags">Tags: 
                                        <?php 
                                        $tagLinks = array_map(function($t) use ($baseUrl) {
                                            return '<a href="' . $baseUrl . '/tag/' . urlencode($t) . '" class="tag-link">' . htmlspecialchars($t) . '</a>';
                                        }, $article['tags']);
                                        echo implode(', ', $tagLinks); 
                                        ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 <?php echo htmlspecialchars($siteName); ?></p>
        </div>
    </footer>
</body>
</html>
    <?php
    exit;
}

// Article Page
$slug = $path;
$filename = $slug . '.md';
$article = getArticleMetadata($filename);

if (!$article) {
    http_response_code(404);
    echo "Article not found";
    exit;
}

// Find Related Articles
$files = glob($articleDir . '/*.md');
$related = [];
foreach ($files as $file) {
    $fName = basename($file);
    if ($fName !== $filename) {
        $meta = getArticleMetadata($fName);
        if ($meta && array_intersect($article['tags'], $meta['tags'])) {
            $related[] = $meta;
        }
    }
}
$related = array_slice($related, 0, 5);

// Process Content
$contentBody = preg_replace('/^#\s+.*\n/', '', $article['content']);
$contentBody = parseDialogue($contentBody);
$Parsedown = new Parsedown();
$htmlContent = $Parsedown->text($contentBody);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - <?php echo htmlspecialchars($siteName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($article['description']); ?>">
    <link rel="canonical" href="<?php echo $baseUrl; ?>/<?php echo htmlspecialchars($article['filename']); ?>">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <a href="<?php echo $baseUrl; ?>/" class="logo"><?php echo htmlspecialchars($siteName); ?></a>
            <nav>
                <ul>
                    <li><a href="<?php echo $baseUrl; ?>/">Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>/about">About</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <article class="post">
            <header class="post-header">
                <h1><?php echo htmlspecialchars($article['title']); ?></h1>
                <div class="post-meta">
                    <span class="category-label"><?php echo htmlspecialchars($article['category']); ?></span>
                    <span class="tags-label">Tags: 
                        <?php 
                        $tagLinks = array_map(function($t) use ($baseUrl) {
                            return '<a href="' . $baseUrl . '/tag/' . urlencode($t) . '" class="tag-link">' . htmlspecialchars($t) . '</a>';
                        }, $article['tags']);
                        echo implode(', ', $tagLinks); 
                        ?>
                    </span>
                </div>
                <?php if ($article['imagePrompt']): ?>
                    <div class="post-hero-placeholder">
                        <p>Image Prompt: <?php echo htmlspecialchars($article['imagePrompt']); ?></p>
                    </div>
                <?php endif; ?>
            </header>
            <div class="post-content">
                <?php echo $htmlContent; ?>
            </div>

            <?php if (!empty($related)): ?>
            <section class="related-posts">
                <h2>Related Articles</h2>
                <ul class="related-list">
                    <?php foreach ($related as $post): ?>
                        <li><a href="<?php echo $baseUrl; ?>/<?php echo htmlspecialchars($post['filename']); ?>"><?php echo htmlspecialchars($post['title']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>
        </article>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 <?php echo htmlspecialchars($siteName); ?></p>
        </div>
    </footer>
</body>
</html>
