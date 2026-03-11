# WordPress プラグイン セキュリティ・品質チェックリスト

このチェックリストは AI エージェント（Claude Sonnet / Gemini 等）が機械的に実行できる形式で記述している。
各項目は「検索指示 → 判定基準 → NG例 → OK例」の構造。全ファイルを対象に順番に実行すること。

---

## A. PHP セキュリティチェック

### A-1. 出力エスケープ漏れ（XSS）

**指示**: 全 PHP ファイルの `echo` / `printf` / `sprintf` / `print` 文を列挙し、各出力に適切なエスケープ関数が適用されているか確認せよ。

| 出力コンテキスト | 必須エスケープ |
|---|---|
| HTML テキスト | `esc_html()` / `esc_html__()` |
| HTML 属性値 | `esc_attr()` / `esc_attr__()` |
| URL | `esc_url()` |
| 信頼済み HTML 断片 | `wp_kses_post()` / `wp_kses()` |
| JavaScript 文字列 | `esc_js()` |
| JSON-LD (`<script>` 内) | `wp_json_encode()` + `JSON_HEX_TAG` フラグ |
| インライン CSS | `wp_strip_all_tags()` |

**NG例**:
```php
echo $variable;
echo '<a href="' . $url . '">';
echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_SLASHES) . '</script>';
```

**OK例**:
```php
echo esc_html($variable);
echo '<a href="' . esc_url($url) . '">';
echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>';
```

**特記**: `selected()`, `checked()`, `disabled()` 等の WP コア関数はエスケープ済みのため OK。

### A-2. `<script>` タグ内の `</script>` インジェクション

**指示**: `<script` を含む全ての `echo` / `printf` 文を列挙し、ユーザー由来データが `</script>` を含む可能性がないか確認せよ。

**判定基準**:
- `wp_json_encode()` を使用している場合、`JSON_HEX_TAG` フラグがあるか？
- `JSON_UNESCAPED_SLASHES` を使用している場合、特に注意（`</script>` の `/` がエスケープされない）

### A-3. CSRF 対策（nonce）

**指示**: `admin_post_*` / `wp_ajax_*` / REST API エンドポイント / `options.php` 以外のフォーム送信先を列挙し、各々で nonce 検証が行われているか確認せよ。

**判定基準**:
- Settings API 使用（`settings_fields()` 呼び出し）→ 自動で nonce 付与されるため OK
- カスタムフォーム → `wp_nonce_field()` + `wp_verify_nonce()` / `check_admin_referer()` が必要
- AJAX → `check_ajax_referer()` が必要
- REST API → `permission_callback` が必要

### A-4. 権限チェック

**指示**: 管理画面の描画関数・データ変更処理の全てで `current_user_can()` が呼ばれているか確認せよ。

**検索対象**:
- `add_menu_page` / `add_submenu_page` / `add_options_page` の capability 引数
- 描画コールバック関数の冒頭に `current_user_can()` ガードがあるか
- AJAX ハンドラの冒頭に権限チェックがあるか

### A-5. SQL インジェクション

**指示**: `$wpdb->query()` / `$wpdb->get_*()` / `$wpdb->prepare()` の全呼び出しを列挙せよ。

**判定基準**:
- `$wpdb->prepare()` を経由しない直接クエリは NG
- `$wpdb->prepare()` のプレースホルダに `%s` / `%d` / `%f` 以外を使用していないか

### A-6. 入力サニタイズ

**指示**: `$_GET` / `$_POST` / `$_REQUEST` / `$_SERVER` の全使用箇所を列挙し、サニタイズが行われているか確認せよ。

**判定基準**:
- `sanitize_text_field()` / `sanitize_textarea_field()` / `absint()` / `intval()` / `rest_sanitize_boolean()` 等で処理されているか
- URL の DB 保存時に `esc_url_raw()` が使われているか（`esc_url()` は出力用、`esc_url_raw()` は保存用）
- コメント等の制限付き HTML 許可に `wp_kses_data()` が使われているか
- `register_setting()` の `sanitize_callback` が設定されているか

### A-7. 直接アクセス防止

**指示**: 全 PHP ファイルの先頭付近に以下のガードがあるか確認せよ。

```php
if (!defined('ABSPATH')) { exit; }
```

**例外**: `uninstall.php` は `WP_UNINSTALL_PLUGIN` で代用可。

### A-8. ファイルインクルード

**指示**: `require` / `require_once` / `include` / `include_once` の全呼び出しを列挙せよ。

**判定基準**:
- パスにユーザー入力が含まれていないか
- `plugin_dir_path(__FILE__)` 等の固定パスを基点としているか

---

## B. WordPress 品質チェック

### B-1. アンインストールクリーンアップ

**指示**: `uninstall.php` が存在するか確認し、以下を検証せよ。

- `WP_UNINSTALL_PLUGIN` ガードがあるか
- `add_option()` / `update_option()` で保存した全キーに対応する `delete_option()` があるか
- 独自テーブルがあれば `DROP TABLE` があるか
- `wp_cache_flush()` を使用していないか（禁止）

**検証方法**: メインファイルと `includes/` 内で `get_option` / `update_option` / `add_option` を検索し、オプションキーを全て列挙。`uninstall.php` の `delete_option` と照合。

### B-2. 早期リターン（パフォーマンス）

**指示**: `wp_head` / `wp_footer` / `wp_enqueue_scripts` / `the_content` フィルターのコールバックを列挙し、各々で不要な実行を防ぐ早期リターンがあるか確認せよ。

**チェック項目**:
- `is_admin()` チェック（フロント専用処理の場合）
- `is_singular()` チェック（単一投稿のみの場合）
- `has_block()` / `has_shortcode()` チェック（該当ブロック/ショートコードがある場合のみ）

### B-3. エンキュー適正

**指示**: `wp_enqueue_script()` / `wp_enqueue_style()` の全呼び出しを列挙せよ。

**判定基準**:
- `<script>` / `<style>` タグの直書きがないか（禁止）
- 管理画面用アセットが `admin_enqueue_scripts` フック内にあるか
- フロント用アセットが `wp_enqueue_scripts` フック内にあるか
- 必要な画面でのみ読み込まれているか（全画面読み込みは NG）

### B-4. バージョン整合性

**指示**: 以下の値が全て一致しているか確認せよ。

- メイン PHP ファイルのヘッダー `Version:`
- `define()` で定義されたバージョン定数
- `readme.txt` の `Stable tag:`（存在する場合）
- `package.json` の `version`（存在する場合）

### B-5. Text Domain 整合性

**指示**: `__()` / `_e()` / `esc_html__()` / `esc_attr__()` 等の翻訳関数で使われている Text Domain が、メインファイルヘッダーの `Text Domain` と一致しているか確認せよ。

---

## C. Gutenberg JS チェック

### C-1. `useSelect` 依存配列

**指示**: `useSelect(` の全呼び出しを列挙し、第2引数（依存配列）を確認せよ。

**判定基準**:
- 空配列 `[]` → マウント時のみ実行。動的に変わるべき値なのに更新されない可能性がないか
- 依存配列内の値が `useSelect` 内で実際に使われているか

### C-2. `useEffect` クリーンアップ

**指示**: `useEffect(` の全呼び出しを列挙し、イベントリスナーやタイマーを設定している場合にクリーンアップ関数（return）があるか確認せよ。

### C-3. innerBlocks 再帰処理

**指示**: ブロック一覧を走査するコードがある場合、`innerBlocks` の再帰処理が行われているか確認せよ。

**判定基準**:
- `getBlocks()` の結果をフラットに走査するだけでは、ネストされたブロックを見落とす
- 再帰関数またはキュー方式で `innerBlocks` を探索しているか

### C-4. RichText の allowedFormats

**指示**: `<RichText` コンポーネントの全使用箇所を列挙し、`allowedFormats` prop が設定されているか確認せよ。

**判定基準**:
- プレーンテキスト想定の入力に `allowedFormats={[]}` があるか
- リッチテキスト想定でも、必要最小限のフォーマットに制限されているか

### C-5. ブロック属性のバリデーション

**指示**: `block.json` の `attributes` 定義と、`edit.js` / `save.js` / `render.php` での使用箇所を照合せよ。

**判定基準**:
- 数値型属性に `absint()` / `Number()` 等の型変換があるか
- 範囲制限が必要な値（見出しレベル等）に上下限チェックがあるか

---

## D. 実行手順

### 第1段階（AI エージェント）

1. 全 PHP / JS ファイルを読み込む（1ファイルも省略しない）
2. セクション A → B → C の順にチェックを実行
3. 各項目について「PASS / WARN / FAIL」と根拠（ファイル名:行番号）を記録
4. 結果をマークダウン表形式でまとめる

### 第2段階（Opus レビュー）

1. 第1段階の結果を受け取り、FAIL / WARN 項目を精査
2. 攻撃チェーンの推論（複数項目の組み合わせで悪用可能か）
3. 見落としがないか、全ファイルリストと照合

### レポート形式

```markdown
| # | チェック項目 | 結果 | ファイル:行 | 備考 |
|---|---|---|---|---|
| A-1 | 出力エスケープ | PASS | settings.php:111 | esc_html__() 使用 |
| A-2 | script インジェクション | FAIL | main.php:96 | JSON_HEX_TAG なし |
```
