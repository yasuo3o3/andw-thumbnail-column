# 実装計画 — andw-thumbnail-column

## 要求定義（SPEC.md より転記）

| # | 項目 | 内容 |
|---|------|------|
| 1 | 対象ユーザー | WordPress 管理者・編集者 |
| 2 | 起点の課題 | 投稿一覧画面でサムネイルが表示されず、記事を開かないと画像を確認できない |
| 3 | 理想の行動変化 | 一覧画面を見るだけでサムネイルが分かり、記事の識別・選択が素早くできる |
| 4 | 事業上の狙い | 管理画面の作業効率向上。自社・クライアント両方で使い回せる汎用プラグイン |
| 5 | 成功指標 | 一覧にサムネイル列表示。投稿・固定・カスタム投稿タイプ対応。投稿タイプごとに有効/無効切替 |

---

## 設計方針

- **アーキテクチャ**: シングルファイルプラグイン + uninstall.php
- **クラス構成**: `Andw_Tc_Plugin` クラス1つに集約
- **プレフィックス**: `andw_tc_` / `Andw_Tc_` / `ANDW_TC_`
- **CSS**: `wp_add_inline_style()` でインライン CSS 出力（`<style>` 直書き禁止）
- **列の位置**: フィルターで配列末尾に追加（priority 高めで後勝ち）
- **フック登録**: 最初から `manage_{$post_type}_posts_columns` 形式で動的登録（`manage_posts_columns` は使わない）
- **初期化ガード**: メインファイルに `is_admin()` チェック、全 PHP に ABSPATH ガード
- **フック登録タイミング**: `admin_init` で設定値を読み込み、有効な投稿タイプに対してのみフック登録

---

## セキュリティ要件（全フェーズ共通）

| # | 要件 | 実装方法 |
|---|------|----------|
| S1 | CSRF 対策 | `settings_fields()` による nonce 自動付与 |
| S2 | 権限チェック | `add_options_page()` の capability に `manage_options` + コールバック冒頭で `current_user_can('manage_options')` 再チェック |
| S3 | 入力サニタイズ | ホワイトリスト方式: `get_post_types(['show_ui'=>true])` の返値のみ許可 + `sanitize_key()` |
| S4 | 出力エスケープ | `get_the_post_thumbnail()` → `wp_kses_post()` 経由で echo。設定画面の値は `esc_attr()` / `esc_html()` |
| S5 | 直接アクセス防止 | メインファイル: `defined('ABSPATH')` ガード、uninstall.php: `defined('WP_UNINSTALL_PLUGIN')` ガード |

---

## 実装ステップ

### Phase 1: プラグイン骨格

**要求定義との関連**: 全項目の基盤

- ファイル: `andw-thumbnail-column.php`
- プラグインヘッダー（Plugin Name, Text Domain, Requires at least: 6.0, Requires PHP: 8.0）
- ABSPATH ガード
- `is_admin()` チェック（フロントでは何もしない）
- `Andw_Tc_Plugin` クラス定義
  - コンストラクタでフック登録
  - シングルトンは使わず、`is_admin()` ガード内で `new Andw_Tc_Plugin()` で初期化
- 有効化フック: `register_activation_hook()` で `andw_tc_enabled_post_types` に `['post', 'page']` を `add_option()` で保存（既存値があれば上書きしない）
- オプション値の型保証ヘルパー: `get_option()` の戻り値が配列でない場合はデフォルト値を返す

**コミット単位**: Phase 1 完了で1コミット

---

### Phase 2: 設定画面

**要求定義との関連**: 成功指標「投稿タイプごとに有効/無効切替」

- `admin_menu` フックで「設定 → サムネイル列」メニュー追加
  - `add_options_page()` の capability: `manage_options`
- Settings API でオプション登録
  - `register_setting('andw_tc_settings_group', 'andw_tc_enabled_post_types', $sanitize_callback)`
  - `settings_fields('andw_tc_settings_group')` を必ず呼び出し（nonce 自動付与）
  - `do_settings_sections()` で描画
- 描画コールバック冒頭: `current_user_can('manage_options')` ガード
- `get_post_types(['show_ui' => true], 'objects')` で投稿タイプを自動検出
  - 表示: 投稿タイプのラベル名のみ（内部スラッグは表示しない）
  - チェックボックスの `value` 属性: `esc_attr()` でエスケープ
  - ラベル名: `esc_html()` でエスケープ
- サニタイズコールバック:
  - 入力が配列でなければ空配列を返す（全チェックOFF対応）
  - `array_map('sanitize_key', $input)` で各値をサニタイズ
  - `array_intersect($input, array_keys(get_post_types(['show_ui'=>true])))` で実在タイプのみに絞り込み
- 保存ボタン: WordPress 標準の `submit_button()` を使用（「変更を保存」ラベル）

**コミット単位**: Phase 2 完了で1コミット

---

### Phase 3: サムネイル列追加

**要求定義との関連**: 成功指標「一覧にサムネイル列表示」「投稿・固定・カスタム投稿タイプ対応」

- `admin_init` フックで設定値を読み込み、有効な投稿タイプごとにフック登録:
  ```
  foreach ( $enabled_post_types as $post_type ) {
      add_filter( "manage_{$post_type}_posts_columns", [ $this, 'add_thumbnail_column' ] );
      add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'render_thumbnail_column' ], 10, 2 );
  }
  ```
- `add_thumbnail_column()`: 列配列の末尾に `'andw_tc_thumbnail' => 'サムネイル'` を追加
  - priority: 99（他プラグインの列追加より後に実行し、最右を狙う）
- `render_thumbnail_column()`:
  - `get_the_post_thumbnail( $post_id, 'thumbnail' )` で画像取得
  - 画像あり: `echo wp_kses_post( get_the_post_thumbnail( $post_id, 'thumbnail' ) );`
  - 画像なし（`_thumbnail_id` なし、または画像ファイル消失）: CSS placeholder を出力
    - placeholder: `<span class="andw-tc-no-image" aria-hidden="true"></span>`
- CSS 出力:
  - `admin_enqueue_scripts` フックで `$hook === 'edit.php'` の場合のみ実行
  - `wp_register_style('andw-tc-admin', false)` → `wp_enqueue_style()` → `wp_add_inline_style()`
  - スタイル内容:
    ```css
    .column-andw_tc_thumbnail { width: 70px; }
    .column-andw_tc_thumbnail img { width: 60px; height: 60px; object-fit: cover; display: block; }
    .andw-tc-no-image { display: block; width: 60px; height: 60px; background: #ddd; }
    ```

**コミット単位**: Phase 3 完了で1コミット

---

### Phase 4: アンインストール処理

**要求定義との関連**: 非機能要件「アンインストール時にオプションを削除」

- `uninstall.php` を作成
- `defined('WP_UNINSTALL_PLUGIN')` ガード
- `delete_option('andw_tc_enabled_post_types')`

**コミット単位**: Phase 4 完了で1コミット

---

## 確認項目

### 機能確認

- [ ] サムネイル列が投稿一覧に表示される
- [ ] サムネイル列が固定ページ一覧に表示される
- [ ] サムネイルなしの投稿でグレー placeholder が表示される
- [ ] 画像ファイル消失時にもグレー placeholder が表示される
- [ ] 設定画面で投稿タイプごとに有効/無効を切り替えられる
- [ ] 全チェックボックスOFFで保存すると、全投稿タイプで列が非表示になる
- [ ] カスタム投稿タイプ（WooCommerce 等）にも対応する
- [ ] アンインストール時にオプションが削除される
- [ ] アンインストール→再インストールでデフォルト設定が復元される

### セキュリティ確認

- [ ] `settings_fields()` が呼び出されている（CSRF 対策）
- [ ] 描画コールバック冒頭に `current_user_can('manage_options')` がある
- [ ] サニタイズコールバックがホワイトリスト方式になっている
- [ ] `get_the_post_thumbnail()` を `wp_kses_post()` 経由で echo している
- [ ] 全 PHP ファイルに ABSPATH / WP_UNINSTALL_PLUGIN ガードがある
- [ ] CSS が `wp_add_inline_style()` 経由で出力されている
- [ ] CSS が `edit.php` 以外の画面では読み込まれない

### 品質確認

- [ ] `php -l` で構文エラーなし
- [ ] `phpcs` で WPCS 違反なし
- [ ] `phpstan` level 6 でエラーなし
- [ ] プレフィックス `andw_tc_` が一貫して使われている
- [ ] WordPress 6.0 / PHP 8.0 以上で動作する
