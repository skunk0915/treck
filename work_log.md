# 作業履歴

## 2025-12-14

### 問題: 関連記事リストがmenu-modal-contentに表示されない

**症状:**
- 各記事ページで、その記事のタグと同じタグがつけられた記事リストを表示する関連記事リストが`class="menu-modal-content"`に表示されていない

**調査結果:**
1. `relatedArticlesData`は`views/parts/footer.php`で正しくJSONエンコードされ、静的HTMLファイルに埋め込まれている
2. `js/hamburger.js`の`renderRelated()`関数は正しく実装されており、`openMenu()`時に呼ばれている
3. HTML構造も正しく、`#menu-related-section`と`#menu-related-list`が存在している
4. **問題の原因**: JavaScriptで動的に作成される`.menu-related-group`クラスのCSSスタイルが定義されていなかった

**修正内容:**
- `css/style.css`に`.menu-related-group`および関連スタイルを追加
- `static/css/style.css`にも同様の修正を適用

**追加されたCSSコード:**
```css
.menu-related-group {
  margin-bottom: 1.5rem;
}

.menu-related-group h4 {
  font-size: 0.95rem;
  margin-bottom: 8px;
  color: #555;
}

.menu-related-group .menu-list {
  display: flex;
  flex-direction: row;
  gap: 15px;
  overflow-x: auto;
  padding-bottom: 10px;
}
```

**編集したファイル:**
- `/Users/mizy/Dropbox/treck/css/style.css` (1168行目付近に追加)
- `/Users/mizy/Dropbox/treck/static/css/style.css` (1168行目付近に追加)

**結果:**
- 関連記事がタグごとにグループ化され、横スクロール可能なリストとして`menu-modal-content`内に表示されるようになった

---

### 追加問題: 動的PHPページで関連記事が表示されない

**症状:**
- 静的HTMLファイル（`static/`ディレクトリ）には関連記事が含まれているが、実際のサイト（`https://sensei-omoi.flow-t.net/`）では表示されない
- ブラウザでソースを表示すると`<!-- Related section omitted for fallback simplicity, or TODO: implement -->`というコメントがある
- JavaScriptのデバッグログで`relatedArticlesData: []`（空配列）になっている

**原因:**
- サイトでは動的なPHPページ（`index.php`）が使われており、静的HTMLファイルとは異なるコードが実行されている
- `index.php`の記事表示部分（171-236行目）では、関連記事セクションが実装されていなかった
- `$relatedByTag`が生成されていないため、`footer.php`で空配列になっていた

**修正内容:**

1. **`getRelatedArticles()`関数を追加** (27-42行目)
   - `build.php`と同じロジックで関連記事を取得する関数を実装

2. **記事表示時に全記事を取得して関連記事を生成** (189-200行目)
   ```php
   // Get all articles for related posts
   $files = glob($articleDir . '/*.md');
   $allArticles = [];
   foreach ($files as $file) {
       $f = basename($file);
       $articleMeta = getArticleMetadata($f, $articleDir, $articleMetaManager, $tagManager);
       if ($articleMeta && $articleMeta['status'] !== 'private') {
           if ($articleMeta['published_at'] && strtotime($articleMeta['published_at']) > time()) continue;
           $allArticles[] = $articleMeta;
       }
   }
   $relatedByTag = getRelatedArticles($meta, $allArticles);
   ```

3. **関連記事セクションのHTMLを追加** (230-256行目)
   - `build.php`と同じHTML構造で関連記事を表示
   - タグごとにグループ化された関連記事リストを生成

**編集したファイル:**
- `/Users/mizy/Dropbox/treck/index.php`

**結果:**
- 動的PHPページでも`$relatedByTag`が正しく生成され、ハンバーガーメニューの`relatedArticlesData`にデータが含まれるようになった
- 記事本文下にも関連記事セクションが表示されるようになった

---

### 追加問題: 静的サイト運用のためのディレクトリ構造最適化

**要件:**
- サーバー上で記事を`/static/`配下で管理したい（ロジックファイルと分離）
- URLは`https://sensei-omoi.flow-t.net/arcteryx_backpack_guide`のようにルート直下に見せたい
- 静的HTMLが存在する場合は優先的に表示（高速）
- 静的HTMLが存在しない場合は404エラー（フォールバック不要）

**解決策:**

`.htaccess`にリライトルールを追加:
```apache
# タグページは動的処理（index.phpへ）
RewriteRule ^tag/(.+)$ index.php [QSA,L]

# aboutページは動的処理
RewriteRule ^about/?$ index.php [QSA,L]

# ルート（/）はstatic/index.htmlへ
RewriteRule ^$ /static/index.html [L]

# 静的記事ページへのリダイレクト
# /arcteryx_backpack_guide を /static/arcteryx_backpack_guide/ にマップ
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{DOCUMENT_ROOT}/static/$1/index.html -f
RewriteRule ^([^/?\.]+)$ /static/$1/ [L]
```

**動作:**
1. `/tag/*` → `index.php`で動的処理
2. `/about` → `index.php`で動的処理
3. `/` → `/static/index.html`を表示
4. `/slug` → `/static/slug/index.html`が存在すれば表示、存在しなければ404

**サーバー構造:**
```
/public_html/
  ├── static/
  │   ├── index.html
  │   ├── arcteryx_backpack_guide/index.html
  │   ├── dod_tarp_complete_guide/index.html
  │   └── ...
  ├── article/
  ├── index.php (タグページ・aboutページ用)
  └── .htaccess
```

**編集したファイル:**
- `/Users/mizy/Dropbox/treck/.htaccess`

**結果:**
- 記事は`/static/`配下で整理されつつ、URLはルート直下に見える
- 静的HTMLのみを表示し、存在しない場合は404エラー
- 編集が意図通り反映されない場合の原因追求が容易

---

### 追加機能: 関連記事リストのシャッフル

**要件:**
- `id="menu-related-section"`の関連記事リストにも、メニューを開くたびにシャッフル機能を適用したい

**修正内容:**

`renderRelated()`関数内で、各タグの記事配列をシャッフルするコードを追加（120-121行目）:
```javascript
// Shuffle articles for this tag
const shuffled = [...articles].sort(() => 0.5 - Math.random());
```

そして、元の`articles`配列ではなく`shuffled`配列を使って記事カードを生成（138行目）:
```javascript
shuffled.forEach(art => {
    const card = createCard(art);
    console.log('[renderRelated] Created card for:', art.title);
    listRow.appendChild(card);
});
```

**編集したファイル:**
- `/Users/mizy/Dropbox/treck/js/hamburger.js`
- `/Users/mizy/Dropbox/treck/static/js/hamburger.js`

**結果:**
- 関連記事セクションも、ハンバーガーメニューを開くたびに各タグごとの記事がシャッフルされる
- 静的HTMLでありながら、クライアントサイドJavaScriptで動的なユーザー体験を提供
