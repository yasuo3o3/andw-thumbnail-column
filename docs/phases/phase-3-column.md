# Phase 3: サムネイル列追加（0.5人日）

**ゴール:** 設定で有効化された投稿タイプの一覧画面に「サムネイル」列が最右に表示され、アイキャッチ画像またはグレー placeholder が表示される。

**前提:** Phase 2 完了（設定画面が動作し、有効な投稿タイプを取得できる）

**参照仕様:**
- `docs/SPEC.md` — 一覧画面の画面仕様
- `docs/PHASE-PLAN.md` — セキュリティ要件 S4、設計方針（動的フック登録）

**満たすべき要求定義:** 成功指標「一覧にサムネイル列表示」「投稿・固定・カスタム投稿タイプ対応」

---

## タスク一覧

| # | タスク | 成果物 |
|---|--------|--------|
| 3-1 | サムネイル列のフック登録・描画 | `andw-thumbnail-column.php` に追記 |
| 3-2 | CSS 出力 | `andw-thumbnail-column.php` に追記 |

---

## 3-1: サムネイル列のフック登録・描画

**ファイル:** `andw-thumbnail-column.php`（既存クラスに追記）

### 実装内容

- `admin_init` フックで有効な投稿タイプごとに列フィルター・アクションを登録
- 列追加コールバック
- 列描画コールバック

### コード（骨格）

```php
// コンストラクタに追加:
add_action( 'admin_init', array( $this, 'register_column_hooks' ) );

/**
 * 有効な投稿タイプに対してサムネイル列フックを登録する。
 */
public function register_column_hooks(): void {
	$enabled = $this->get_enabled_post_types();
	foreach ( $enabled as $post_type ) {
		add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_thumbnail_column' ), 99 );
		add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_thumbnail_column' ), 10, 2 );
	}
}

/**
 * 列ヘッダーにサムネイル列を追加する。
 *
 * @param array<string, string> $columns 既存の列配列。
 * @return array<string, string> サムネイル列を追加した列配列。
 */
public function add_thumbnail_column( array $columns ): array {
	$columns['andw_tc_thumbnail'] = __( 'サムネイル', 'andw-thumbnail-column' );
	return $columns;
}

/**
 * サムネイル列のセルを描画する。
 *
 * @param string $column_name 列名。
 * @param int    $post_id     投稿ID。
 */
public function render_thumbnail_column( string $column_name, int $post_id ): void {
	if ( 'andw_tc_thumbnail' !== $column_name ) {
		return;
	}

	$thumbnail = get_the_post_thumbnail( $post_id, 'thumbnail' );

	if ( $thumbnail ) {
		// 画像ファイルが存在するかも確認。
		$thumbnail_id = (int) get_post_thumbnail_id( $post_id );
		$file_path    = get_attached_file( $thumbnail_id );
		if ( $file_path && file_exists( $file_path ) ) {
			echo wp_kses_post( $thumbnail );
			return;
		}
	}

	// サムネイルなし or ファイル消失 → placeholder。
	echo '<span class="andw-tc-no-image" aria-hidden="true"></span>';
}
```

### 注意事項

- `manage_{$post_type}_posts_columns` を使う（`manage_posts_columns` ではない）
- priority 99 で最右を狙う
- `get_the_post_thumbnail()` の戻り値は `wp_kses_post()` 経由で出力
- 画像ファイル消失（`_thumbnail_id` はあるが実ファイルが存在しない）の場合も placeholder を表示

---

## 3-2: CSS 出力

**ファイル:** `andw-thumbnail-column.php`（既存クラスに追記）

### 実装内容

- `admin_enqueue_scripts` フックで edit.php のみに CSS を出力
- `wp_add_inline_style()` を使用

### コード（骨格）

```php
// コンストラクタに追加:
add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

/**
 * 管理画面にインライン CSS を追加する。
 *
 * @param string $hook 現在の管理画面フック名。
 */
public function enqueue_admin_styles( string $hook ): void {
	if ( 'edit.php' !== $hook ) {
		return;
	}

	$css = '
		.column-andw_tc_thumbnail { width: 70px; }
		.column-andw_tc_thumbnail img { width: 60px; height: 60px; object-fit: cover; display: block; }
		.andw-tc-no-image { display: block; width: 60px; height: 60px; background: #ddd; }
	';

	wp_register_style( 'andw-tc-admin', false, array(), '1.0.0' );
	wp_enqueue_style( 'andw-tc-admin' );
	wp_add_inline_style( 'andw-tc-admin', $css );
}
```

### 注意事項

- `$hook === 'edit.php'` で投稿一覧画面のみに限定
- `<style>` 直書きは禁止。必ず `wp_add_inline_style()` を使用
- `wp_register_style()` の `$src` に `false` を渡してインラインのみのハンドルを作成

---

## 完了条件

- [ ] 投稿一覧画面の最右列に「サムネイル」列が表示される
- [ ] 固定ページ一覧画面にも「サムネイル」列が表示される
- [ ] アイキャッチ画像が設定された投稿では 60x60px の画像が表示される
- [ ] アイキャッチ画像がない投稿ではグレー placeholder が表示される
- [ ] 画像ファイルが消失した投稿でもグレー placeholder が表示される
- [ ] 設定で無効化した投稿タイプの一覧にはサムネイル列が表示されない
- [ ] CSS が edit.php 以外の管理画面では読み込まれない
- [ ] `phpcs` / `php -l` でエラーなし

## コミット粒度の目安

1. サムネイル列追加 + CSS → 「Phase 3: サムネイル列を追加」
