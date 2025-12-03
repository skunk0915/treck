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
            body { display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f5f5f5; }
            .login-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
            .form-group { margin-bottom: 1rem; }
            .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
            .form-group input { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
            .btn { width: 100%; padding: 0.75rem; background: #333; color: white; border: none; border-radius: 4px; cursor: pointer; }
            .btn:hover { background: #555; }
            .error { color: red; margin-bottom: 1rem; text-align: center; }
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
            $message = "記事「" . htmlspecialchars($filename) . "」のタグを更新しました。";
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
        body { background-color: #f9f9f9; color: #333; }
        .admin-container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #ddd; padding-bottom: 1rem; }
        .admin-section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .admin-section h2 { margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 0.5rem; margin-bottom: 1rem; font-size: 1.25rem; }
        
        .form-inline { display: flex; gap: 1rem; align-items: flex-end; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 0.25rem; font-size: 0.9rem; color: #666; }
        .form-control { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
        
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-warning { background-color: #ffc107; color: #212529; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.85rem; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f8f9fa; font-weight: bold; }
        tr:hover { background-color: #f1f1f1; }
        
        .tag-badge { display: inline-block; background: #e9ecef; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.85rem; margin-right: 0.25rem; margin-bottom: 0.25rem; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        .tag-list-cloud { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .tag-cloud-item { background: #fff; border: 1px solid #ddd; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.9rem; }
        .tag-count { color: #888; font-size: 0.8rem; margin-left: 0.25rem; }
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
                        <span class="tag-cloud-item"><?php echo htmlspecialchars($tag); ?> <span class="tag-count"><?php echo $count; ?></span></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <h2>記事一覧・タグ編集</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30%;">記事タイトル / ファイル名</th>
                        <th style="width: 25%;">公開設定</th>
                        <th style="width: 35%;">タグ (カンマ区切り)</th>
                        <th style="width: 10%;">タグ更新</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td>
                                <div style="font-weight: bold; margin-bottom: 0.25rem;">
                                    <a href="<?php echo htmlspecialchars(str_replace('.md', '', $article['filename'])); ?>" target="_blank" style="text-decoration: none; color: #333;">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </div>
                                <div style="font-size: 0.8rem; color: #888;"><?php echo htmlspecialchars($article['filename']); ?></div>
                            </td>
                            <td>
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
                            <td>
                                <form method="post" id="form-<?php echo md5($article['filename']); ?>" style="display: flex; gap: 0.5rem;">
                                    <input type="hidden" name="action" value="update_tags">
                                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($article['filename']); ?>">
                                    <input type="text" name="tags" class="form-control" value="<?php echo htmlspecialchars(implode(', ', $article['tags'])); ?>" style="font-size: 0.9rem;">
                                </form>
                            </td>
                            <td>
                                <button type="submit" form="form-<?php echo md5($article['filename']); ?>" class="btn btn-primary btn-sm">更新</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html>
