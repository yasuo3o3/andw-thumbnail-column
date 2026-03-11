# SETUP.md — 環境セットアップ手順

このファイルは新しいパソコンで Claude Code 環境をセットアップする際の手順。

## グローバル設定

`~/.claude/settings.json` の `permissions.allow` に以下を追加:

```json
"Bash(git add:*)",
"Bash(git commit:*)",
"Bash(git push:*)"
```

これにより、コード変更時の自動コミット・プッシュが確認なしで実行される（複数PC間の同期漏れ防止）。

## プル忘れ防止 Hook

作業開始時にリモートに未取得のコミットがあれば警告を出す。

### 1. チェックスクリプトを作成

`~/.claude/check-git-sync.sh`:

```bash
#!/bin/bash
# 作業開始時にリモートとの同期状態をチェック

# Git リポジトリでなければ終了
git rev-parse --git-dir > /dev/null 2>&1 || exit 0

# リモートの最新情報を取得
git fetch --quiet 2>/dev/null

# 未pullのコミット数
BEHIND=$(git rev-list --count HEAD..@{u} 2>/dev/null || echo 0)

if [ "$BEHIND" -gt 0 ]; then
  echo "⚠️ リモートに $BEHIND 件の新しいコミットがあります。git pull してください"
fi
```

### 2. Hook を設定

`~/.claude/settings.json` の `hooks` に以下を追加:

```json
"UserPromptSubmit": [
  {
    "hooks": [
      {
        "type": "command",
        "command": "bash ~/.claude/check-git-sync.sh"
      }
    ]
  }
]
```

これにより、プロンプト送信時にリモートとの差分をチェックし、未pullのコミットがあれば警告が表示される。

## VSCode ワークスペース設定

各プロジェクトに `.vscode/settings.json` を配置して、PHP の Format On Save を無効化する。
WordPress は独自コーディング規約（WPCS）を使用しており、VSCode の PHP フォーマッタ（Intelephense 等）のデフォルト（PSR-12）とは異なるため、保存時の自動整形を無効にしないとコードスタイルが壊れる。

テンプレートの `.vscode/settings.json` を全プロジェクトにコピーすること:

```bash
# テンプレートから各プロジェクトへコピー（.gitignore 済みのためコミット不要）
for dir in c:/andW/andw-*/; do
  mkdir -p "$dir.vscode"
  cp c:/andW/andw-template/.vscode/settings.json "$dir.vscode/settings.json"
done
```

既に `.vscode/settings.json` が存在するプロジェクト（andw-ai-chat-assistant 等）は、既存設定を壊さないよう手動でマージすること。

## Claude Code カスタムコマンド・スキル・エージェント

テンプレートの `_claude-config/` にカスタム設定が含まれている（.gitignore 済み）。

### 含まれる設定

| 種類 | 内容 |
|------|------|
| commands/ | /blog-yasuo, /blog-draft, /review-code など |
| skills/ | yasuo-blog-writer |
| agents/ | wp-expert, security-expert など |

### セットアップ

新しいPCで以下を実行:

```bash
cd c:/andW/andw-template
bash _claude-config/setup-claude.sh
```

これにより `~/.claude/` に設定ファイルがコピーされる。Claude Code を再起動すると反映される。

### 注意

`_claude-config/` は .gitignore に含まれているため、GitHub には上がらない。
テンプレートを別PCにコピーする際は、Git clone ではなくフォルダごとコピーすること。

## PHP 品質チェックツール（PHPCS + PHPStan）

### 前提条件

| ツール | 最低バージョン | 確認コマンド |
|--------|-------------|------------|
| PHP | 8.0+ | `php -v` |
| Composer | 2.x | `composer --version` |

### セットアップ（プロジェクトごとに1回）

```bash
cd c:/andW/sandbox-badge  # プロジェクトディレクトリ
composer install
```

これで `vendor/` に PHPCS + WordPress Coding Standards + PHPStan がインストールされる。
`vendor/` は `.gitignore` 済みのためリポジトリには入らない。

### 使い方

```bash
# PHPCS: WordPress コーディング規約チェック
composer phpcs

# PHPCS: サマリーのみ表示
vendor/bin/phpcs --report=summary

# PHPCBF: 自動修正可能な違反を修正
composer phpcbf

# PHPStan: 静的解析（型チェック）
composer phpstan
# メモリ不足の場合:
php -d memory_limit=1G vendor/bin/phpstan analyse --memory-limit=1G
```

### 設定ファイル

| ファイル | 内容 | テンプレート |
|---------|------|------------|
| `composer.json` | 依存パッケージ定義 | `andw-template/composer.json` |
| `phpcs.xml.dist` | PHPCS ルール設定（テキストドメイン・プレフィックス等） | `andw-template/phpcs.xml.dist` |
| `phpstan.neon` | PHPStan 設定（解析レベル・除外パス等） | `andw-template/phpstan.neon` |

新しいプロジェクトではテンプレートからコピーし、テキストドメインやプレフィックスをプロジェクト固有の値に変更すること。

### 新しいPCでの環境構築

1. **PHP インストール**: https://windows.php.net/download/ から VS17 x64 Non Thread Safe をダウンロード → `C:\php` に展開 → PATH に追加
2. **Composer インストール**: https://getcomposer.org/download/ の Windows Installer を実行
3. **各プロジェクトで `composer install`** を実行

## 確認方法

設定後、以下で確認:
```bash
cat ~/.claude/settings.json
ls ~/.claude/commands/
ls ~/.claude/skills/
ls ~/.claude/agents/
php -v
composer --version
```
