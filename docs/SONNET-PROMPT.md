# 実装指示 — andw-thumbnail-column（サムネイル列プラグイン）

## 作業範囲

- ステップ: Phase 1〜4（全フェーズ）
- バージョン: 1.0.0

## 参照ファイル

- 計画: `docs/PHASE-PLAN.md`（全体設計・セキュリティ要件）
- 仕様書: `docs/SPEC.md`（要求定義・画面仕様）
- Phase 詳細:
  - `docs/phases/phase-1-skeleton.md`（プラグイン骨格）
  - `docs/phases/phase-2-settings.md`（設定画面）
  - `docs/phases/phase-3-column.md`（サムネイル列追加）
  - `docs/phases/phase-4-uninstall.md`（アンインストール処理）
- 規約: `docs/WORDPRESS.md`, `docs/CONTRIB.md`
- 既存パターン: なし（新規プラグイン）

## 成果物

| ファイル | 内容 |
|----------|------|
| `andw-thumbnail-column.php` | メインプラグインファイル（Phase 1〜3 で段階的に構築） |
| `uninstall.php` | アンインストール処理（Phase 4） |

## コーディング規約

### 必須ルール

1. **プレフィックス**: `andw_tc_` / `Andw_Tc_` / `ANDW_TC_` を必ず使う
2. **テキストドメイン**: `andw-thumbnail-column`（全ての `__()`, `esc_html_e()` 等で統一）
3. **ABSPATH ガード**: 全 PHP ファイルの先頭に `defined('ABSPATH')` チェック（uninstall.php は `WP_UNINSTALL_PLUGIN`）
4. **エスケープ**: 出力時は必ずエスケープ関数を使用
   - HTML 出力: `wp_kses_post()` or `esc_html()`
   - 属性値: `esc_attr()`
   - URL: `esc_url()`
5. **CSS**: `<style>` 直書き禁止。`wp_add_inline_style()` を使用
6. **フック**: `manage_posts_columns` は使わない。`manage_{$post_type}_posts_columns` 形式を使用
7. **Settings API**: `settings_fields()` を必ず呼ぶ（nonce 自動付与）

### PHPCS / PHPStan

- `phpcs.xml.dist` と `phpstan.neon` が設定済み
- 各 Phase 完了時に `php -l` で構文チェックを実行
- 全 Phase 完了後に `composer phpcs` と `composer phpstan` を実行

## コミット粒度

- Phase 1 完了 → `Phase 1: プラグイン骨格を作成`
- Phase 2 完了 → `Phase 2: 設定画面を追加`
- Phase 3 完了 → `Phase 3: サムネイル列を追加`
- Phase 4 完了 → `Phase 4: アンインストール処理を追加`

## 完了条件

- [ ] 投稿・固定ページの一覧にサムネイル列が表示される
- [ ] 設定画面で投稿タイプごとに有効/無効を切り替えられる
- [ ] アンインストール時にオプションが削除される
- [ ] `php -l` で全 PHP ファイルの構文エラーなし
- [ ] `composer phpcs` でエラーなし
- [ ] `composer phpstan` でエラーなし

## 作業手順

1. **まず `composer install` を実行**（WPCS・PHPStan をインストール）
2. `docs/phases/phase-1-skeleton.md` を読み、Phase 1 を実装 → コミット
3. `docs/phases/phase-2-settings.md` を読み、Phase 2 を実装 → コミット
4. `docs/phases/phase-3-column.md` を読み、Phase 3 を実装 → コミット
5. `docs/phases/phase-4-uninstall.md` を読み、Phase 4 を実装 → コミット
6. `composer phpcs` と `composer phpstan` を実行し、エラーがあれば修正
7. HANDOFF.md を更新（Phase D 完了、次は Phase E レビュー）
