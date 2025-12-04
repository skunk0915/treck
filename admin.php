<?php
session_start();
require_once __DIR__ . '/lib/TagManager.php';
require_once __DIR__ . '/lib/ArticleMetaManager.php';

// Configuration
$articleDir = __DIR__ . '/article';
$dataFile = __DIR__ . '/data/tags.json';
$metaFile = __DIR__ . '/data/article_meta.json';
$adminEmail = 'skunk0915@gmail.com';
$adminPassword = 'yosuke0915'; // Change this in production!

$tagManager = new TagManager($dataFile);
$articleMetaManager = new ArticleMetaManager($metaFile);

// Handle Login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    if ($_POST['email'] === $adminEmail && $_POST['password'] === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = "メールアドレスまたはパスワードが間違っています。";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Auth Check
if (!isset($_SESSION['admin_logged_in'])) {
?>
    <!DOCTYPE html>
    <html lang="ja">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <link rel="stylesheet" href="css/style.css">
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-color: #f5f5f5;
            }

            .login-card {
                background: white;
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 400px;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: bold;
            }

            .form-group input {
                width: 100%;
                padding: 0.5rem;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
            }

            .btn {
                width: 100%;
                padding: 0.75rem;
                background: #333;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }

            .btn:hover {
                background: #555;
            }

            .error {
                color: red;
                margin-bottom: 1rem;
                text-align: center;
            }
        </style>
    </head>

    <body>
        <div class="login-card">
            <h1 style="text-align: center; margin-bottom: 1.5rem;">Admin Login</h1>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
    </body>

    </html>
<?php
    exit;
}

// --- Admin Logic ---

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_tags') {
            $filename = $_POST['filename'];
            $tags = explode(',', $_POST['tags']);
            $tagManager->setTags($filename, $tags);

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'タグを更新しました']);
            exit;
        } elseif ($_POST['action'] === 'rename_tag') {
            $oldTag = trim($_POST['old_tag']);
            $newTag = trim($_POST['new_tag']);
            if ($oldTag && $newTag) {
                $count = $tagManager->renameTag($oldTag, $newTag);
                $message = "タグ「{$oldTag}」を「{$newTag}」に変更しました。（{$count}件の記事を更新）";
            }
        } elseif ($_POST['action'] === 'merge_tags') {
            $sourceTag = trim($_POST['source_tag']);
            $targetTag = trim($_POST['target_tag']);
            if ($sourceTag && $targetTag && $sourceTag !== $targetTag) {
                $isDelete = ($targetTag === '(削除)');
                $count = $tagManager->mergeTags($sourceTag, $targetTag, $isDelete);

                if ($isDelete) {
                    $message = "タグ「{$sourceTag}」を削除しました。（{$count}件の記事から削除）";
                } else {
                    $message = "タグ「{$sourceTag}」を「{$targetTag}」に統合しました。（{$count}件の記事を更新）";
                }
            }
        } elseif ($_POST['action'] === 'update_meta') {
            $filename = $_POST['filename'];
            $published_at = $_POST['published_at'] ?: null;
            $status = $_POST['status'];

            $articleMetaManager->setMeta($filename, [
                'published_at' => $published_at,
                'status' => $status
            ]);

            // Return JSON for AJAX
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => '保存しました']);
            exit;
        } elseif ($_POST['action'] === 'update_title') {
            $filename = $_POST['filename'];
            $newTitle = trim($_POST['title']);
            $filePath = $articleDir . '/' . $filename;

            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                // Replace the first H1 header
                $newContent = preg_replace('/^#\s+(.*)/m', '# ' . $newTitle, $content, 1);

                // If no H1 found, prepend it (fallback)
                if ($newContent === $content && strpos($content, '# ' . $newTitle) === false) {
                    $newContent = "# " . $newTitle . "\n\n" . $content;
                }

                file_put_contents($filePath, $newContent);

                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'タイトルを更新しました']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'ファイルが見つかりません']);
            }
            exit;
        }
    }
}

// Get Data for View
$files = glob($articleDir . '/*.md');
$articles = [];
$allTags = $tagManager->getAllTags();

foreach ($files as $file) {
    $content = file_get_contents($file);
    preg_match('/^#\s+(.*)/m', $content, $titleMatch);
    $title = $titleMatch ? trim($titleMatch[1]) : basename($file);
    $filename = basename($file);
    $tags = $tagManager->getTags($filename);
    $meta = $articleMetaManager->getMeta($filename);

    $articles[] = [
        'filename' => $filename,
        'title' => $title,
        'tags' => $tags,
        'meta' => $meta
    ];
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treck Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Admin Specific Styles */
        body {
            background-color: #f9f9f9;
            color: #333;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid #ddd;
            padding-bottom: 1rem;
        }

        .admin-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .admin-section h2 {
            margin-top: 0;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .form-inline {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
            color: #666;
        }

        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            cursor: pointer;
            user-select: none;
        }

        th:hover {
            background-color: #e9ecef;
        }

        th::after {
            content: ' ↕';
            font-size: 0.8em;
            color: #999;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .tag-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .tag-list-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .tag-cloud-item {
            background: #fff;
            border: 1px solid #ddd;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .tag-count {
            color: #888;
            font-size: 0.8rem;
            margin-left: 0.25rem;
        }

        .search-box {
            margin-bottom: 1rem;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .title-edit-input {
            width: 100%;
            padding: 0.25rem;
            border: 1px solid transparent;
            background: transparent;
            font-weight: bold;
            font-size: 1rem;
            color: #333;
        }

        .title-edit-input:focus {
            border-color: #007bff;
            background: white;
            outline: none;
        }

        .title-link-icon {
            font-size: 0.8rem;
            color: #007bff;
            text-decoration: none;
            margin-left: 0.5rem;
        }

        /* Tag Suggestions */
        .tag-input-wrapper {
            position: relative;
            width: 100%;
        }

        .tag-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            display: none;
            min-width: 200px;
        }

        .tag-suggestion-item {
            padding: 0.5rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }

        .tag-suggestion-item:last-child {
            border-bottom: none;
        }

        .tag-suggestion-item:hover,
        .tag-suggestion-item.active {
            background-color: #f0f0f0;
        }

        .tag-suggestion-match {
            font-weight: bold;
            color: #007bff;
        }

        .tag-cloud-item {
            cursor: pointer;
            user-select: none;
            transition: all 0.2s;
        }

        .tag-cloud-item:hover {
            background-color: #e2e6ea;
        }

        .tag-cloud-item.active {
            background-color: #007bff;
            color: white;
            border-color: #0056b3;
        }

        .tag-cloud-item.active .tag-count {
            color: #e0e0e0;
        }

        #floating-save-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #28a745;
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            z-index: 1000;
            transition: transform 0.2s;
        }

        #floating-save-btn:hover {
            transform: scale(1.1);
            background-color: #218838;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Treck Admin Panel</h1>
            <div>
                <span>Logged in as <?php echo htmlspecialchars($adminEmail); ?></span>
                <a href="?logout=1" class="btn btn-danger" style="margin-left: 1rem; text-decoration: none;">Logout</a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="admin-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem;">
                <h2 style="border: none; margin: 0; padding: 0;">タグ一括管理</h2>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Rename Tag -->
                <div>
                    <h3>タグ名の変更</h3>
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">指定したタグの名前を変更します。全ての記事に反映されます。</p>
                    <form method="post" class="form-inline">
                        <input type="hidden" name="action" value="rename_tag">
                        <div class="form-group">
                            <label>変更前のタグ名</label>
                            <select name="old_tag" class="form-control" required>
                                <option value="">選択してください</option>
                                <?php foreach ($allTags as $tag => $count): ?>
                                    <option value="<?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars($tag); ?> (<?php echo $count; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>新しいタグ名</label>
                            <input type="text" name="new_tag" class="form-control" placeholder="新しいタグ名" required>
                        </div>
                        <button type="submit" class="btn btn-primary">変更</button>
                    </form>
                </div>

                <!-- Merge Tags -->
                <div>
                    <h3>タグの統合・削除</h3>
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">「統合元」のタグを削除し、「統合先」のタグを付与します。「(削除)」を選ぶとタグ自体が消えます。</p>
                    <form method="post" class="form-inline">
                        <input type="hidden" name="action" value="merge_tags">
                        <div class="form-group">
                            <label>統合元（消えるタグ）</label>
                            <select name="source_tag" class="form-control" required>
                                <option value="">選択してください</option>
                                <?php foreach ($allTags as $tag => $count): ?>
                                    <option value="<?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars($tag); ?> (<?php echo $count; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>統合先（残るタグ）</label>
                            <input type="text" name="target_tag" class="form-control" list="existing-tags" placeholder="既存または新規タグ" required>
                            <datalist id="existing-tags">
                                <option value="(削除)">
                                    <?php foreach ($allTags as $tag => $count): ?>
                                <option value="<?php echo htmlspecialchars($tag); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <button type="submit" class="btn btn-primary">統合</button>
                    </form>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <h3>現在のタグ一覧</h3>
                <div class="tag-list-cloud">
                    <?php foreach ($allTags as $tag => $count): ?>
                        <span class="tag-cloud-item" data-tag="<?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars($tag); ?> <span class="tag-count"><?php echo $count; ?></span></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <h2>記事一覧・タグ編集</h2>

            <div class="search-box">
                <input type="text" id="article-search" placeholder="キーワードで記事を検索...">
            </div>

            <table id="article-table">
                <thead>
                    <tr>
                        <th style="width: 35%;" data-sort="title">記事タイトル / ファイル名</th>
                        <th style="width: 25%;" data-sort="date">公開設定</th>
                        <th style="width: 40%;" data-sort="tags">タグ (カンマ区切り)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <tr class="article-row">
                            <td data-title="<?php echo htmlspecialchars($article['title']); ?>" data-filename="<?php echo htmlspecialchars($article['filename']); ?>">
                                <div style="display: flex; align-items: center; margin-bottom: 0.25rem;">
                                    <input type="text" class="title-edit-input" value="<?php echo htmlspecialchars($article['title']); ?>" data-filename="<?php echo htmlspecialchars($article['filename']); ?>">
                                    <a href="<?php echo htmlspecialchars(str_replace('.md', '', $article['filename'])); ?>" target="_blank" class="title-link-icon" title="記事を表示">
                                        🔗
                                    </a>
                                </div>
                                <div style="font-size: 0.8rem; color: #888;"><?php echo htmlspecialchars($article['filename']); ?></div>
                                <div class="save-status-title" style="font-size: 0.75rem; color: green; height: 1.2em;"></div>
                            </td>
                            <td data-date="<?php echo htmlspecialchars($article['meta']['published_at'] ?? ''); ?>" data-status="<?php echo htmlspecialchars($article['meta']['status']); ?>">
                                <form class="meta-form" data-filename="<?php echo htmlspecialchars($article['filename']); ?>" style="background: #f8f9fa; padding: 0.5rem; border-radius: 4px;">
                                    <div style="margin-bottom: 0.5rem;">
                                        <label style="font-size: 0.75rem; display: block; color: #666; margin-bottom: 2px;">公開日時</label>
                                        <input type="datetime-local" name="published_at" class="form-control" style="font-size: 0.85rem; padding: 0.25rem;" value="<?php echo htmlspecialchars($article['meta']['published_at'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label style="font-size: 0.75rem; display: block; color: #666; margin-bottom: 2px;">ステータス</label>
                                        <select name="status" class="form-control" style="font-size: 0.85rem; padding: 0.25rem;">
                                            <option value="public" <?php echo ($article['meta']['status'] === 'public') ? 'selected' : ''; ?>>公開</option>
                                            <option value="private" <?php echo ($article['meta']['status'] === 'private') ? 'selected' : ''; ?>>非公開</option>
                                        </select>
                                    </div>
                                    <div class="save-status" style="font-size: 0.75rem; color: green; height: 1.2em; margin-top: 2px;"></div>
                                </form>
                            </td>
                            <td data-tags="<?php echo htmlspecialchars(implode(', ', $article['tags'])); ?>">
                                <div class="tag-input-wrapper">
                                    <input type="text" class="form-control tag-input"
                                        value="<?php echo htmlspecialchars(implode(', ', $article['tags'])); ?>"
                                        data-original-value="<?php echo htmlspecialchars(implode(', ', $article['tags'])); ?>"
                                        data-filename="<?php echo htmlspecialchars($article['filename']); ?>"
                                        style="font-size: 0.9rem;" autocomplete="off">
                                    <div class="tag-suggestions"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <button id="floating-save-btn" title="変更を保存">💾</button>

    <script>
        // Meta Update Logic
        document.querySelectorAll('.meta-form').forEach(form => {
            const inputs = form.querySelectorAll('input, select');
            const statusDiv = form.querySelector('.save-status');

            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    const formData = new FormData(form);
                    formData.append('action', 'update_meta');
                    formData.append('filename', form.dataset.filename);

                    statusDiv.textContent = '保存中...';
                    statusDiv.style.color = '#666';

                    fetch('admin.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                statusDiv.textContent = '保存しました';
                                statusDiv.style.color = 'green';
                                setTimeout(() => {
                                    statusDiv.textContent = '';
                                }, 2000);
                            } else {
                                statusDiv.textContent = 'エラーが発生しました';
                                statusDiv.style.color = 'red';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            statusDiv.textContent = '通信エラー';
                            statusDiv.style.color = 'red';
                        });
                });
            });
        });

        // Title Update Logic
        document.querySelectorAll('.title-edit-input').forEach(input => {
            input.addEventListener('change', () => {
                const filename = input.dataset.filename;
                const newTitle = input.value;
                const statusDiv = input.closest('td').querySelector('.save-status-title');

                statusDiv.textContent = '保存中...';
                statusDiv.style.color = '#666';

                const formData = new FormData();
                formData.append('action', 'update_title');
                formData.append('filename', filename);
                formData.append('title', newTitle);

                fetch('admin.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            statusDiv.textContent = '保存しました';
                            statusDiv.style.color = 'green';
                            // Update data attribute for sorting/filtering
                            input.closest('td').dataset.title = newTitle;
                            setTimeout(() => {
                                statusDiv.textContent = '';
                            }, 2000);
                        } else {
                            statusDiv.textContent = 'エラーが発生しました';
                            statusDiv.style.color = 'red';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        statusDiv.textContent = '通信エラー';
                        statusDiv.style.color = 'red';
                    });
            });
        });

        // Filtering Logic
        const searchInput = document.getElementById('article-search');
        const tableRows = document.querySelectorAll('.article-row');
        const tagCloudItems = document.querySelectorAll('.tag-cloud-item');
        let selectedTags = new Set();

        function applyFilters() {
            const keyword = searchInput.value.toLowerCase();

            tableRows.forEach(row => {
                const title = row.querySelector('td[data-title]').dataset.title.toLowerCase();
                const filename = row.querySelector('td[data-title]').dataset.filename.toLowerCase();
                const tagsStr = row.querySelector('td[data-tags]').dataset.tags;
                const tagsLower = tagsStr.toLowerCase();
                const articleTags = tagsStr.split(',').map(t => t.trim());

                // Keyword Match
                const keywordMatch = !keyword || title.includes(keyword) || filename.includes(keyword) || tagsLower.includes(keyword);

                // Tag Match (AND condition)
                let tagsMatch = true;
                if (selectedTags.size > 0) {
                    for (let tag of selectedTags) {
                        if (!articleTags.includes(tag)) {
                            tagsMatch = false;
                            break;
                        }
                    }
                }

                if (keywordMatch && tagsMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', applyFilters);

        tagCloudItems.forEach(item => {
            item.addEventListener('click', () => {
                const tag = item.dataset.tag;
                if (selectedTags.has(tag)) {
                    selectedTags.delete(tag);
                    item.classList.remove('active');
                } else {
                    selectedTags.add(tag);
                    item.classList.add('active');
                }
                applyFilters();
            });
        });

        // Floating Save Button Logic
        const saveBtn = document.getElementById('floating-save-btn');

        saveBtn.addEventListener('click', async () => {
            const inputs = document.querySelectorAll('.tag-input');
            const updates = [];

            inputs.forEach(input => {
                const currentVal = input.value;
                const originalVal = input.dataset.originalValue;

                if (currentVal !== originalVal) {
                    updates.push({
                        filename: input.dataset.filename,
                        tags: currentVal,
                        inputElement: input
                    });
                }
            });

            if (updates.length === 0) {
                alert('変更されたタグはありません。');
                return;
            }

            if (!confirm(`${updates.length}件の記事のタグを更新しますか？`)) {
                return;
            }

            saveBtn.disabled = true;
            saveBtn.textContent = '⏳';

            let successCount = 0;
            let errors = [];

            const promises = updates.map(update => {
                const formData = new FormData();
                formData.append('action', 'update_tags');
                formData.append('filename', update.filename);
                formData.append('tags', update.tags);

                return fetch('admin.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            successCount++;
                            // Update original value and data-tags attribute
                            update.inputElement.dataset.originalValue = update.tags;
                            update.inputElement.closest('td').dataset.tags = update.tags;
                            // Flash success
                            update.inputElement.style.backgroundColor = '#d4edda';
                            setTimeout(() => update.inputElement.style.backgroundColor = '', 1000);
                        } else {
                            errors.push(`${update.filename}: ${data.message}`);
                        }
                    })
                    .catch(err => {
                        errors.push(`${update.filename}: 通信エラー`);
                    });
            });

            await Promise.all(promises);

            saveBtn.disabled = false;
            saveBtn.textContent = '💾';

            if (errors.length > 0) {
                alert(`完了しましたが、以下のエラーが発生しました：\n${errors.join('\n')}`);
            } else {
                // alert('すべての更新が完了しました。');
            }

            // Re-apply filters in case tags changed
            applyFilters();
        });

        // Sorting Logic
        const table = document.getElementById('article-table');
        const headers = table.querySelectorAll('th[data-sort]');
        let currentSort = {
            column: null,
            direction: 'asc'
        };

        headers.forEach(header => {
            header.addEventListener('click', () => {
                const column = header.dataset.sort;
                const direction = (currentSort.column === column && currentSort.direction === 'asc') ? 'desc' : 'asc';

                // Update sort state
                currentSort = {
                    column,
                    direction
                };

                // Sort rows
                const rowsArray = Array.from(tableRows);
                rowsArray.sort((a, b) => {
                    let valA, valB;

                    if (column === 'title') {
                        valA = a.querySelector('td[data-title]').dataset.title.toLowerCase();
                        valB = b.querySelector('td[data-title]').dataset.title.toLowerCase();
                    } else if (column === 'date') {
                        valA = a.querySelector('td[data-date]').dataset.date || '';
                        valB = b.querySelector('td[data-date]').dataset.date || '';
                        // Handle empty dates (put them last)
                        if (valA === '' && valB !== '') return 1;
                        if (valA !== '' && valB === '') return -1;
                    } else if (column === 'tags') {
                        valA = a.querySelector('td[data-tags]').dataset.tags.toLowerCase();
                        valB = b.querySelector('td[data-tags]').dataset.tags.toLowerCase();
                    }

                    if (valA < valB) return direction === 'asc' ? -1 : 1;
                    if (valA > valB) return direction === 'asc' ? 1 : -1;
                    return 0;
                });

                // Re-append rows
                const tbody = table.querySelector('tbody');
                rowsArray.forEach(row => tbody.appendChild(row));

                // Update header visual (optional)
                headers.forEach(h => h.style.backgroundColor = '');
                header.style.backgroundColor = '#e9ecef';
            });
        });
    </script>
    <script>
        // Tag Suggestion Logic
        const EXISTING_TAGS = <?php echo json_encode(array_keys($allTags)); ?>;

        document.querySelectorAll('.tag-input').forEach(input => {
            const wrapper = input.closest('.tag-input-wrapper');
            const suggestionsBox = wrapper.querySelector('.tag-suggestions');
            let currentFocus = -1;

            input.addEventListener('input', function(e) {
                const val = this.value;
                const cursorPosition = this.selectionStart;

                // Find the current tag being typed (between commas)
                const lastCommaIndex = val.lastIndexOf(',', cursorPosition - 1);
                const nextCommaIndex = val.indexOf(',', cursorPosition);

                const start = lastCommaIndex + 1;
                const end = nextCommaIndex === -1 ? val.length : nextCommaIndex;

                const currentTag = val.substring(start, end).trim();

                closeAllLists();
                if (!currentTag) return false;

                currentFocus = -1;

                // Filter matches
                const matches = EXISTING_TAGS.filter(tag => tag.toLowerCase().includes(currentTag.toLowerCase()) && tag !== currentTag);

                if (matches.length === 0) return;

                suggestionsBox.style.display = 'block';

                matches.forEach(match => {
                    const item = document.createElement('div');
                    item.className = 'tag-suggestion-item';

                    // Highlight match
                    const regex = new RegExp(`(${currentTag})`, "gi");
                    item.innerHTML = match.replace(regex, "<span class='tag-suggestion-match'>$1</span>");

                    item.addEventListener('click', function() {
                        // Insert the selected tag
                        const before = val.substring(0, start);
                        const after = val.substring(end);

                        // Add comma if not at the end and no comma exists
                        const newTag = match;

                        input.value = before + (before.trim() && !before.endsWith(' ') ? '' : '') + newTag + after;

                        // Move cursor to end of inserted tag
                        // input.selectionStart = input.selectionEnd = before.length + newTag.length;

                        closeAllLists();
                    });

                    suggestionsBox.appendChild(item);
                });
            });

            input.addEventListener('keydown', function(e) {
                let x = suggestionsBox.querySelectorAll('.tag-suggestion-item');
                if (e.key === 'ArrowDown') {
                    currentFocus++;
                    addActive(x);
                } else if (e.key === 'ArrowUp') {
                    currentFocus--;
                    addActive(x);
                } else if (e.key === 'Enter') {
                    if (currentFocus > -1) {
                        e.preventDefault();
                        if (x) x[currentFocus].click();
                    }
                }
            });

            function addActive(x) {
                if (!x) return false;
                removeActive(x);
                if (currentFocus >= x.length) currentFocus = 0;
                if (currentFocus < 0) currentFocus = (x.length - 1);
                x[currentFocus].classList.add('active');
                x[currentFocus].scrollIntoView({
                    block: 'nearest'
                });
            }

            function removeActive(x) {
                for (let i = 0; i < x.length; i++) {
                    x[i].classList.remove('active');
                }
            }

            function closeAllLists(elmnt) {
                suggestionsBox.innerHTML = '';
                suggestionsBox.style.display = 'none';
            }

            // Close list when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target !== input && e.target !== suggestionsBox) {
                    closeAllLists();
                }
            });
        });
    </script>
</body>

</html>