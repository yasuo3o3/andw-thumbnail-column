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
