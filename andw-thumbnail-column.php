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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
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

	/**
	 * 設定ページをメニューに追加する。
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'サムネイル列', 'andw-thumbnail-column' ),
			__( 'サムネイル列', 'andw-thumbnail-column' ),
			'manage_options',
			'andw-thumbnail-column',
			array( $this, 'render_settings_page' )
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
	 * 投稿タイプ入力値をサニタイズする。
	 *
	 * @param mixed $input フォームからの入力値。
	 * @return array<string> サニタイズ済み投稿タイプ配列。
	 */
	public function sanitize_post_types( mixed $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$input       = array_map( 'sanitize_key', $input );
		$valid_types = array_keys( get_post_types( array( 'show_ui' => true ) ) );

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
				<?php do_settings_sections( 'andw-thumbnail-column' ); ?>
				<table class="form-table" role="presentation">
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
