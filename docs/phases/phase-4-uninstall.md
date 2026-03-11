# Phase 4: アンインストール処理（0.1人日）

**ゴール:** プラグイン削除時に `andw_tc_enabled_post_types` オプションがDBから削除される。

**前提:** Phase 1〜3 完了

**参照仕様:**
- `docs/SPEC.md` — 非機能要件「アンインストール時にオプションを削除」
- `docs/PHASE-PLAN.md` — セキュリティ要件 S5

**満たすべき要求定義:** 非機能要件

---

## タスク一覧

| # | タスク | 成果物 |
|---|--------|--------|
| 4-1 | uninstall.php 作成 | `uninstall.php` |

---

## 4-1: uninstall.php 作成

**ファイル:** `uninstall.php`

### 実装内容

- `WP_UNINSTALL_PLUGIN` 定数ガード
- `delete_option()` でオプション削除

### コード（完成形）

```php
<?php
/**
 * アンインストール処理。
 *
 * プラグイン削除時に実行され、保存済みオプションを削除する。
 *
 * @package Andw_Tc
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'andw_tc_enabled_post_types' );
```

### 注意事項

- `defined('ABSPATH')` ではなく `defined('WP_UNINSTALL_PLUGIN')` を使用
- プラグイン無効化（deactivate）ではオプションを消さない（再有効化で復元されるように）
- マルチサイト対応は今回スコープ外

---

## 完了条件

- [ ] `uninstall.php` が存在する
- [ ] `WP_UNINSTALL_PLUGIN` ガードがファイル先頭にある
- [ ] `delete_option('andw_tc_enabled_post_types')` が呼ばれる
- [ ] `php -l uninstall.php` で構文エラーなし

## コミット粒度の目安

1. `uninstall.php` → 「Phase 4: アンインストール処理を追加」
