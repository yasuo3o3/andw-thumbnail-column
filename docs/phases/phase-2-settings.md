# Phase 2: 設定画面（0.5人日）

**ゴール:** 「設定 → サムネイル列」画面で投稿タイプごとの有効/無効をチェックボックスで切り替え・保存できる。

**前提:** Phase 1 完了（`Andw_Tc_Plugin` クラスが存在する）

**参照仕様:**
- `docs/SPEC.md` — 設定画面の画面仕様
- `docs/PHASE-PLAN.md` — セキュリティ要件 S1〜S3

**満たすべき要求定義:** 成功指標「投稿タイプごとに有効/無効切替」

---

## タスク一覧

| # | タスク | 成果物 |
|---|--------|--------|
| 2-1 | 設定ページ登録・描画 | `andw-thumbnail-column.php` に追記 |

---

## 2-1: 設定ページ登録・描画

**ファイル:** `andw-thumbnail-column.php`（既存クラスに追記）

### 実装内容

- `admin_menu` フックで設定ページ登録
- `admin_init` フックで Settings API オプション登録
- 設定ページ描画コールバック
- サニタイズコールバック

### コード（骨格）

```php
// コンストラクタに追加:
add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
add_action( 'admin_init', array( $this, 'register_settings' ) );

/**
 * 設定ページをメニューに追加する。
 */
public function add_settings_page(): void {
	add_options_page(
		__( 'サムネイル列', 'andw-thumbnail-column' ),    // ページタイトル
		__( 'サムネイル列', 'andw-thumbnail-column' ),    // メニュータイトル
		'manage_options',                                  // capability
		'andw-thumbnail-column',                           // メニュースラッグ
		array( $this, 'render_settings_page' )             // コールバック
	);
}

/**
 * Settings API でオプションを登録する。
 */
public function register_settings(): void {
	register_setting(
		'andw_tc_settings_group',
		self::OPTION_NAME,
		array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_post_types' ),
			'default'           => self::DEFAULT_POST_TYPES,
		)
	);
}

/**
 * サニタイズコールバック。ホワイトリスト方式で検証する。
 *
 * @param mixed $input フォームからの入力値。
 * @return array<string> サニタイズ済み投稿タイプ配列。
 */
public function sanitize_post_types( mixed $input ): array {
	if ( ! is_array( $input ) ) {
		return array();  // 全チェックOFF対応
	}
	$input          = array_map( 'sanitize_key', $input );
	$valid_types    = array_keys( get_post_types( array( 'show_ui' => true ) ) );
	return array_values( array_intersect( $input, $valid_types ) );
}

/**
 * 設定ページを描画する。
 */
public function render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'この操作を行う権限がありません。', 'andw-thumbnail-column' ) );
	}

	$enabled    = $this->get_enabled_post_types();
	$post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'andw_tc_settings_group' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'サムネイル列を表示する投稿タイプ', 'andw-thumbnail-column' ); ?>
					</th>
					<td>
						<fieldset>
							<?php foreach ( $post_types as $post_type ) : ?>
								<label>
									<input
										type="checkbox"
										name="<?php echo esc_attr( self::OPTION_NAME ); ?>[]"
										value="<?php echo esc_attr( $post_type->name ); ?>"
										<?php checked( in_array( $post_type->name, $enabled, true ) ); ?>
									/>
									<?php echo esc_html( $post_type->label ); ?>
								</label><br />
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
```

### 注意事項

- `settings_fields()` を必ず呼び出す（nonce 自動付与）
- 投稿タイプのラベルのみ表示（内部スラッグは非表示）
- 全チェックOFFの場合、`$input` が配列でなくなるため空配列を返す
- `submit_button()` は WordPress 標準の「変更を保存」ラベルを出力

---

## 完了条件

- [ ] 「設定 → サムネイル列」メニューが管理画面に表示される
- [ ] 設定ページに `show_ui=true` の投稿タイプがチェックボックスで一覧表示される
- [ ] チェックの ON/OFF を保存でき、リロード後も反映されている
- [ ] 全チェックを外して保存すると空配列が保存される
- [ ] `manage_options` 権限のないユーザーは設定ページにアクセスできない
- [ ] `phpcs` / `php -l` でエラーなし

## コミット粒度の目安

1. 設定画面の実装 → 「Phase 2: 設定画面を追加」
