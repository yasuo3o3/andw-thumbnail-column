# Phase 1: プラグイン骨格（0.3人日）

**ゴール:** `andw-thumbnail-column.php` が有効化でき、デフォルトオプションが保存される状態。

**前提:** なし（初回フェーズ）

**参照仕様:**
- `docs/SPEC.md` — 技術仕様、非機能要件
- `docs/PHASE-PLAN.md` — 設計方針、セキュリティ要件 S5

**満たすべき要求定義:** 全項目の基盤

---

## タスク一覧

| # | タスク | 成果物 |
|---|--------|--------|
| 1-1 | メインファイル作成 | `andw-thumbnail-column.php` |

---

## 1-1: メインファイル作成

**ファイル:** `andw-thumbnail-column.php`

### 実装内容

- プラグインヘッダー（Plugin Name, Description, Version, Author, Text Domain 等）
- ABSPATH ガード
- `is_admin()` チェック（管理画面以外では何もしない）
- `Andw_Tc_Plugin` クラス定義
- 有効化フック: デフォルトオプション保存
- オプション値取得ヘルパーメソッド

### コード（骨格）

```php
<?php
/**
 * Plugin Name:       Thumbnail Column
 * Plugin URI:        https://github.com/yasuo3o3/andw-thumbnail-column
 * Description:       投稿一覧画面にサムネイル列を追加します。
 * Version:           1.0.0
 * Author:            andW
 * Author URI:        https://netservice.jp
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       andw-thumbnail-column
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * プラグインメインクラス。
 */
class Andw_Tc_Plugin {

	/**
	 * オプション名。
	 *
	 * @var string
	 */
	const OPTION_NAME = 'andw_tc_enabled_post_types';

	/**
	 * デフォルトで有効にする投稿タイプ。
	 *
	 * @var array<string>
	 */
	const DEFAULT_POST_TYPES = array( 'post', 'page' );

	/**
	 * コンストラクタ。フックを登録する。
	 */
	public function __construct() {
		// TODO: Phase 2 で設定画面フックを追加
		// TODO: Phase 3 でサムネイル列フックを追加
	}

	/**
	 * 有効な投稿タイプの配列を取得する。
	 * 型保証: get_option() の戻り値が配列でない場合はデフォルト値を返す。
	 *
	 * @return array<string> 有効な投稿タイプのスラッグ配列
	 */
	public function get_enabled_post_types(): array {
		$value = get_option( self::OPTION_NAME, self::DEFAULT_POST_TYPES );
		if ( ! is_array( $value ) ) {
			return self::DEFAULT_POST_TYPES;
		}
		return $value;
	}
}

// 有効化フック: デフォルトオプション保存（既存値があれば上書きしない）。
register_activation_hook(
	__FILE__,
	function () {
		add_option( Andw_Tc_Plugin::OPTION_NAME, Andw_Tc_Plugin::DEFAULT_POST_TYPES );
	}
);

// 管理画面でのみ初期化。
if ( is_admin() ) {
	new Andw_Tc_Plugin();
}
```

---

## 完了条件

- [ ] `php -l andw-thumbnail-column.php` で構文エラーなし
- [ ] プラグインヘッダーに必須項目（Plugin Name, Text Domain, Version, Requires at least, Requires PHP）がある
- [ ] ABSPATH ガードがファイル先頭にある
- [ ] `is_admin()` ガードがあり、フロントでは `Andw_Tc_Plugin` が初期化されない
- [ ] `register_activation_hook` で `add_option()` が呼ばれ、デフォルト値 `['post', 'page']` が保存される
- [ ] `get_enabled_post_types()` が非配列の値に対してもデフォルト値を返す

## コミット粒度の目安

1. `andw-thumbnail-column.php` → 「Phase 1: プラグイン骨格を作成」
