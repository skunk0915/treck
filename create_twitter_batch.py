#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Twitter コンテンツ一括作成スクリプト
rule_twitter.md に従って summary と article から Twitter コンテンツを生成
"""

import os
import sys
import re
from datetime import datetime
from anthropic import Anthropic

# 設定
SUMMARY_DIR = "C:/Users/OW/pj/sensei-omoi/summary"
ARTICLE_DIR = "C:/Users/OW/pj/sensei-omoi/article"
TWITTER_DIR = "C:/Users/OW/pj/sensei-omoi/twitter"
RULE_FILE = "C:/Users/OW/pj/sensei-omoi/rule_twitter.md"

def read_file(filepath):
    """ファイルを読み込む"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            return f.read()
    except Exception as e:
        print(f"Error reading {filepath}: {e}")
        return None

def write_file(filepath, content):
    """ファイルに書き込む"""
    try:
        os.makedirs(os.path.dirname(filepath), exist_ok=True)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return True
    except Exception as e:
        print(f"Error writing {filepath}: {e}")
        return False

def create_twitter_content(filename, rule_content, summary_content, article_content):
    """Claude APIを使ってTwitterコンテンツを生成"""

    client = Anthropic(api_key=os.environ.get("ANTHROPIC_API_KEY"))

    prompt = f"""以下のルールに従って、summaryとarticleからTwitterコンテンツを作成してください。

# ルール
{rule_content}

# Summary
{summary_content}

# Article
{article_content}

# 指示
1. 記事から5-8個の独立した投稿を作成してください
2. 各投稿は100-140文字で、1話完結形式にしてください
3. 具体的な数字、フック、誘導文言、画像プロンプトを含めてください
4. rule_twitter.mdのファイル形式に従ってMarkdown形式で出力してください
5. 記事URL: https://sensei-omoi.flow-t.net/{filename.replace('.md', '')}#section-X の形式で誘導してください
6. 各投稿に適切な画像プロンプトを追加してください

ファイル形式の例:
```markdown
# [記事タイトル] - Twitterコンテンツ

元記事: /article/{filename}
作成日: {datetime.now().strftime('%Y-%m-%d')}
投稿数: 6

---

## 投稿一覧

### 投稿1: [切り口]
[本文100-140文字]

→ [具体的な誘導文言]
https://sensei-omoi.flow-t.net/{filename.replace('.md', '')}#section-X

> **Image Prompt:** [画像生成プロンプト]

---

### 投稿2: [切り口]
...
```

Markdown形式で出力してください。"""

    try:
        message = client.messages.create(
            model="claude-sonnet-4-5-20250929",
            max_tokens=8000,
            messages=[
                {"role": "user", "content": prompt}
            ]
        )

        return message.content[0].text
    except Exception as e:
        print(f"Error calling Claude API for {filename}: {e}")
        return None

def process_file(filename, rule_content):
    """1ファイルを処理"""
    print(f"Processing: {filename}")

    # ファイルパス
    summary_path = os.path.join(SUMMARY_DIR, filename)
    article_path = os.path.join(ARTICLE_DIR, filename)
    twitter_path = os.path.join(TWITTER_DIR, filename)

    # 既存チェック
    if os.path.exists(twitter_path):
        print(f"  Skipped: Already exists")
        return True

    # 読み込み
    summary_content = read_file(summary_path)
    article_content = read_file(article_path)

    if not summary_content or not article_content:
        print(f"  Error: Could not read source files")
        return False

    # Twitter コンテンツ生成
    twitter_content = create_twitter_content(filename, rule_content, summary_content, article_content)

    if not twitter_content:
        print(f"  Error: Could not generate Twitter content")
        return False

    # 保存
    if write_file(twitter_path, twitter_content):
        print(f"  Success: {twitter_path}")
        return True
    else:
        print(f"  Error: Could not write file")
        return False

def main():
    """メイン処理"""
    # コマンドライン引数からファイルリストを取得
    if len(sys.argv) < 2:
        print("Usage: python create_twitter_batch.py file1.md file2.md ...")
        sys.exit(1)

    files = sys.argv[1:]

    # ルール読み込み
    rule_content = read_file(RULE_FILE)
    if not rule_content:
        print(f"Error: Could not read rule file: {RULE_FILE}")
        sys.exit(1)

    print(f"Starting batch processing for {len(files)} files...")
    print("=" * 60)

    success_count = 0
    fail_count = 0

    for filename in files:
        if process_file(filename, rule_content):
            success_count += 1
        else:
            fail_count += 1
        print()

    print("=" * 60)
    print(f"Completed: {success_count} success, {fail_count} failed")

if __name__ == "__main__":
    main()
