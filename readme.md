# 先生、それ、重くないですか？ (Treck)

登山ギアやハイキングのコツを紹介するブログサイトです。
PHPを用いた静的サイトジェネレータ（SSG）によって構築されています。

## プロジェクト構成

- `article/`: 記事のMarkdownファイル
- `views/`: テンプレートファイル（PHP, EJS）
  - `views/parts/footer.php`: 共通フッターテンプレート
- `static/`: ビルドされた静的HTMLファイル（公開ディレクトリ）
- `build.php`: 静的ファイル生成スクリプト
- `build_local.php`: タグの同期およびビルド実行用スクリプト

## 改修履歴

### 2026-07-14: チャットボット埋め込みコードの追加
- サイト全体にチャットボットを導入するため、以下の埋め込みコードを `</body>` 直前に挿入しました。
  ```html
  <script src="https://chatbot.162.43.25.182.sslip.io/chat-widget.js" data-site-id="demo-tenant-id" defer></script>
  ```
- **変更対象ファイル**:
  - `views/parts/footer.php` (フッターテンプレートの `</body>` 直前に挿入)
  - `views/layout.ejs` (レイアウトテンプレートに挿入)
  - `static/` 配下のすべての静的HTMLファイル（計219個）の `</body>` 直前に一括挿入

### 2026-07-14: Xserver VPS (xserver-vps-mizy) へのデプロイ
- 本番サーバーの Xserver VPS に新規にブログ環境をデプロイしました。
- **デプロイ構成**:
  - デプロイ先パス: `/var/www/sensei-omoi`
  - Webサーバー: Nginx (リバースプロキシ) + PHP-FPM (PHP 8.5)
  - 公開URL: `https://sensei-omoi.162.43.25.182.sslip.io/`
  - SSL証明書: Let's Encrypt (Certbot) による HTTPS 化を適用

### 2026-07-27: Kagoya VPS (kagoya-vps-mizy) へのデプロイ・ドメイン紐づけ
- 本番サーバーを Kagoya VPS (`133.18.144.38`) へ切り替え、ドメイン `sensei-omoi.flow-t.net` として公開・デプロイしました。
- **デプロイ構成**:
  - サーバー: Kagoya VPS (IP: `133.18.144.38`)
  - デプロイ先パス: `/var/www/sensei-omoi`
  - Webサーバー: Nginx (`root /var/www/sensei-omoi/static;`)
  - 公開URL: `https://sensei-omoi.flow-t.net/`
  - SSL証明書: Let's Encrypt (Certbot) による HTTPS 対応


