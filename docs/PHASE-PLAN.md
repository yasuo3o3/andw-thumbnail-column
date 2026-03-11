# 実装計画 — 記事知見の取り込み

## 背景
`docs/review-article-insights.md` の知見から、以下3点をテンプレートに取り込む。

## 施策一覧

### 施策1: `.claude/rules/` によるコンテキスト条件分割

**目的**: PHP を触らないセッションで WORDPRESS.md / CONTRIB.md を読み込まないようにし、トークンを節約する。

**方針**:
- `docs/WORDPRESS.md` と `docs/CONTRIB.md` の内容を `.claude/rules/` に移すのではなく、**rules ファイルから docs を参照する構成**にする
- docs/ は引き続き正式な規約文書として残す（他AIエージェントも参照するため）
- rules/ は Claude Code 専用の「いつ読むか」の指示のみ

**ファイル構成**:
```
.claude/rules/
├── wordpress.md    # paths: ["**/*.php", "**/readme.txt"]
└── contrib.md      # paths: ["**/*.php"]
```

**rules ファイルの内容例（wordpress.md）**:
```yaml
---
paths: ["**/*.php", "**/readme.txt", "**/uninstall.php"]
---
PHP ファイルまたは readme.txt を編集する場合は、以下のドキュメントを必ず参照し規約に従うこと:
- `docs/WORDPRESS.md`（最上位規範）
- `docs/AGENTS.md`（共通運用ガイド）
```

**CLAUDE.md の変更**:
- 「規約の読み込み」セクションから WORDPRESS.md / CONTRIB.md の明示的読み込み指示を削除
- AGENTS.md は汎用的な作業手順なので CLAUDE.md に残す

**テンプレート同期への影響**:
- 同期対象に `.claude/rules/wordpress.md`, `.claude/rules/contrib.md` を追加

---

### 施策2: `php -l` PostToolUse Hook

**目的**: PHP ファイル編集後に構文チェックを自動実行し、エラーを即座に検出する。

**方針**:
- PostToolUse hook（matcher: `Write|Edit`）で、編集対象が `.php` ファイルの場合のみ `php -l` を実行
- PostToolUse はツール実行後なのでブロックはできないが、エラー出力を Claude が認識して自動修正できる

**ファイル**: `~/.claude/check-php-syntax.sh`

**スクリプト概要**:
```bash
#!/bin/bash
# PostToolUse hook: Write/Edit 後に PHP 構文チェック
INPUT=$(cat)
FILE_PATH=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty')
# .php ファイル以外は無視
[[ "$FILE_PATH" != *.php ]] && exit 0
# php -l で構文チェック
php -l "$FILE_PATH" 2>&1
```

**settings.json への追加**:
```json
"PostToolUse": [
  {
    "matcher": "Write|Edit",
    "hooks": [
      {
        "type": "command",
        "command": "bash ~/.claude/check-php-syntax.sh"
      }
    ]
  }
]
```

**配置先**: スクリプトはグローバル（`~/.claude/`）、hook 設定はプロジェクト側（`.claude/settings.json`）に追加しテンプレート同期で全プロジェクトに配布。

---

### 施策3: COMMANDS.md に操作小技を追記

**追記内容**:

| キー | 効果 |
|------|------|
| Tab | 許可ダイアログで Yes/No ではなく追加指示を入力 |
| Ctrl+B | 長い処理をバックグラウンドに送って会話を継続 |
| Shift+Tab | Plan モード切り替え |

---

## 確認項目
- [ ] rules/ ファイルが正しいパス条件で動作するか
- [ ] php -l hook が .php 以外のファイルでは発火しないか
- [ ] テンプレート同期対象リストの更新
- [ ] 既存プロジェクトへの影響（破壊的変更がないか）
