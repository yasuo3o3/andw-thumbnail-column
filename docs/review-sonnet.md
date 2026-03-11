# WordPress Plugin Review (Sonnet)

対象:
- `andw-thumbnail-column.php`
- `uninstall.php`

レビュー日: 2026-03-11
レビュアー: Claude Sonnet 4.6

---

## Findings

### HIGH

なし。致命的なセキュリティ欠陥は確認されなかった。

---

### MEDIUM

#### M-1: `register_column_hooks()` が未登録カスタム投稿タイプを通す可能性

**場所:** `register_column_hooks()` (L96–102)

`admin_init` 時点では全カスタム投稿タイプが登録済みとは限らない（テーマの `after_setup_theme` や他プラグインの `init` より早い場合）。
`get_enabled_post_types()` はオプションをそのまま返すため、存在しない投稿タイプのフックが登録されても実害はないが、フックの取り捨てを明示する設計ではない。

**推奨:** `register_column_hooks()` 内で `post_type_exists()` を使い、登録済みの投稿タイプのみフックを追加する。

```php
if ( ! post_type_exists( $post_type ) ) {
    continue;
}
```

または `admin_init` より遅い `current_screen` アクションに移動する。

---

#### M-2: `enqueue_admin_styles()` のフック条件が `edit.php` のみで絞りすぎ

**場所:** `enqueue_admin_styles()` (L146)

`edit.php` フックは全投稿タイプの一覧ページで一致するが、**CPT（カスタム投稿タイプ）の一覧はすべて `edit.php` を返す**ため、実際には問題ない。ただしコードを読む人が誤解する可能性がある。

**推奨:** コメントで「CPT も含め全投稿一覧が `edit.php` を返す」旨を補足するか、より明示的に `get_current_screen()` でスクリーン種別を確認する。

---

#### M-3: `render_thumbnail_column()` での `file_exists()` チェックはパフォーマンス上の懸念

**場所:** `render_thumbnail_column()` (L130–134)

一覧表示で投稿数 × I/Oが発生する。`get_attached_file()` がパスを返し `file_exists()` を呼ぶのは物理ファイルの存在確認として丁寧だが、100件以上の一覧ではディスクアクセスが多発する。

**推奨:** 物理ファイル確認を省略し `get_the_post_thumbnail()` が空かどうかだけで制御するか、`wp_get_attachment_image_src()` でメタデータが存在するかを確認する（DBアクセスのみで完結）。

```php
// 現在: get_attached_file() + file_exists() → ディスクI/O
// 代替: wp_get_attachment_image_src() → DB参照のみ
$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'thumbnail' );
if ( $src ) {
    echo wp_kses_post( $thumbnail );
    return;
}
```

---

### LOW

#### L-1: `add_thumbnail_column()` で列の挿入位置が制御されていない

**場所:** `add_thumbnail_column()` (L110–113)

`$columns['andw_tc_thumbnail'] = ...` による末尾追加のため、列はタイトルの右端に付く。
サムネイルは慣例的に「タイトル列の直前」に置かれることが多い（WooCommerce 等のパターン）。

**推奨:** 配列操作で先頭または特定位置への挿入を実装する（必須ではないが UX 向上）。

```php
$new = array( 'andw_tc_thumbnail' => __( 'サムネイル', 'andw-thumbnail-column' ) );
$columns = array_merge( $new, $columns );
return $columns;
```

---

#### L-2: `uninstall.php` がマルチサイト非対応

**場所:** `uninstall.php` (L14)

`delete_option()` はシングルサイトの `wp_options` のみを対象とする。
マルチサイト環境でネットワーク有効化した場合、各サブサイトのオプションが残留する。

**推奨:** マルチサイト対応が将来必要になった場合は以下を追加する。

```php
if ( is_multisite() ) {
    $sites = get_sites( array( 'number' => 0 ) );
    foreach ( $sites as $site ) {
        switch_to_blog( $site->blog_id );
        delete_option( 'andw_tc_enabled_post_types' );
        restore_current_blog();
    }
} else {
    delete_option( 'andw_tc_enabled_post_types' );
}
```

現時点でマルチサイト対応の記述がないため、README または `Requires:` ヘッダーで単一サイト専用であることを明示することを推奨。

---

#### L-3: `wp_register_style()` に `false` を渡す方法は非推奨方向

**場所:** `enqueue_admin_styles()` (L156)

`wp_register_style( 'handle', false, ... )` はインラインスタイルのための慣用句として使われてきたが、WordPress 6.3 以降では `wp_add_inline_style()` 単体での利用が推奨されつつある（依存ハンドルとして既存スタイルを使う形）。現時点では動作するが将来的な互換性に注意。

**推奨:** 管理画面の共通ハンドル（`wp-admin` や `common`）に依存させる。

```php
wp_add_inline_style( 'common', $css );
```

---

#### L-4: `sanitize_post_types()` でチェックボックスを全部外した場合、空配列が保存される

**場所:** `sanitize_post_types()` (L167–176)

全チェックを外してフォーム送信すると `$input` は `null`（チェックボックスはキーが送信されない）になり、`is_array()` が `false` → 空配列が返る。
この空配列がオプションに保存され、次回以降「有効な投稿タイプなし」になる。仕様であれば問題ないが、意図が不明瞭。

**推奨:** 空配列の場合にデフォルト値に戻すか、設定ページ上に「全解除するとサムネイル列が非表示になります」等の注意書きを追加する。

---

#### L-5: `get_enabled_post_types()` の配列要素が文字列かどうかの検証なし

**場所:** `get_enabled_post_types()` (L57–63)

`get_option()` の返り値が `array` であることは検証しているが、各要素が文字列かどうかは検証していない。DB が直接書き換えられた場合にオブジェクトや整数が混入する可能性がある。

**推奨:** `array_filter( $value, 'is_string' )` を挟む、または `sanitize_post_types()` を通す。

---

## Good Points

### セキュリティ

- **ABSPATH チェック:** ファイル先頭で `defined( 'ABSPATH' )` を確認しており、直接アクセスをブロックしている（`andw-thumbnail-column.php` L18–20、`uninstall.php` L10–12）。
- **WP_UNINSTALL_PLUGIN チェック:** `uninstall.php` が `WP_UNINSTALL_PLUGIN` 定数を正しく確認しており、直接実行防止が適切。
- **Settings API の nonce:** `settings_fields()` を使っているため、WordPress が自動的に nonce を発行・検証する。手動 nonce は不要で正しい実装。
- **capability check の二重実装:** `add_options_page()` での `manage_options` 指定に加え、`render_settings_page()` 内でも `current_user_can( 'manage_options' )` を再確認しており、二重防御になっている。
- **出力エスケープ:** テンプレート内で `esc_html()`, `esc_attr()`, `wp_kses_post()` が適切に使われており、XSS リスクが低い。
- **サニタイズ:** `sanitize_post_types()` で `sanitize_key()` と `post_type_exists()` 相当の `array_intersect()` による許可リスト方式を採用しており、不正な値の保存を防いでいる。

### コード品質

- **PHP 8.0 型宣言:** `mixed`, `array`, `void`, `int`, `string` を適切に使用しており、型安全性が高い。
- **PHPDoc:** 全メソッドに `@param` / `@return` 付きの PHPDoc が記載されており、可読性が高い。
- **定数の一元管理:** `OPTION_NAME`, `DEFAULT_POST_TYPES` を `const` で定義し、`uninstall.php` との一貫性も確保されている（文字列リテラルを直接 `uninstall.php` にも使っており重複はあるが最小限）。
- **早期リターン:** `render_thumbnail_column()` や `enqueue_admin_styles()` で条件不一致時の早期 `return` を採用しており、ネストが浅い。

### WordPress 規約

- **Settings API の正しい利用:** `register_setting()` → `settings_fields()` → `do_settings_sections()` → `submit_button()` の標準フローに沿っている。
- **`admin_init` での設定登録:** `register_settings()` を `admin_init` にフックしており、適切なタイミング。
- **`is_admin()` ガード:** フロントエンドでクラスをインスタンス化しないよう `is_admin()` で制御しており、不要なフック登録を回避している。
- **activation hook の `add_option()` 利用:** 初回有効化時に `add_option()` を使っており、既存値を上書きしない安全な実装。
- **Text Domain:** プラグインヘッダーの `Text Domain` とコード内の `__()` / `esc_html_e()` の第2引数が一致している。
