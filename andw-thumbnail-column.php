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
