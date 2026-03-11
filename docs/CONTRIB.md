# CONTRIB.md — andW ローカル開発環境・デフォルト設定（v1.1）

andW プロジェクト共通の環境情報・初期値を定義する。
コーディング規約・禁止事項・チェック手順は **[WORDPRESS.md](WORDPRESS.md)** を参照。

## ローカル開発環境
- **PHP**: `/c/php/php`（Git Bash）/ `C:\php\php.exe`（PowerShell）
  - シンタックスチェック: `php -l <file>`
- **PHPCS**: `vendor/bin/phpcs` (Composerでインストール済みの場合)

## 命名デフォルト
- 接頭辞: `andw`（詳細ルールは WORDPRESS.md の命名規約を参照）
- Text Domain = スラッグ（例: `andw-season`）
- CPT 例: `andw_work_note`、タクソノミー例: `andw_category`

## 既定ヘッダー（初期値）
```
/**
Plugin Name: andW [機能による名称]
Description: [説明]
Version: 0.0.1
Author: yasuo3o3
Author URI: https://yasuo-o.xyz/
Contributors: yasuo3o3
License: GPLv2 or later
Text Domain: [スラッグと一致させる]
*/
```
- 初回は `0.0.1`、以降コミット毎に +0.0.1 で増分

## メール・通知
- 管理者宛の既定: `plugins-dev@yasuo-o.xyz`
- `wp_mail()` を利用し、マルチバイト対策（件名・本文のエンコーディング）を徹底

## フォルダ構成（最小）
```
plugin-name/
├── plugin-name.php
├── includes/
├── assets/
├── languages/
└── readme.txt
```

## 申請前チェック（andW固有）
- [ ] Contributors = `yasuo3o3`
- [ ] Text Domain = スラッグ（完全一致）
- [ ] WORDPRESS.md のチェックリスト・出荷ゲートをすべて通過
