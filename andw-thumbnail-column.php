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
 *
 * @package Andw_Tc
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
		add_action( 'admin_init', array( $this, 'register_column_hooks' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
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
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string> サムネイル列を追加した列配列。
	 */
	public function add_thumbnail_column( array $columns ): array {
		$columns['andw_tc_thumbnail'] = __( 'サムネイル', 'andw-thumbnail-column' );
		return $columns;
	}

	/**
	 * サムネイル列のセルを描画する。
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 */
	public function render_thumbnail_column( string $column_name, int $post_id ): void {
		if ( 'andw_tc_thumbnail' !== $column_name ) {
			return;
		}

		$thumbnail = get_the_post_thumbnail( $post_id, 'thumbnail' );

		if ( $thumbnail ) {
			$thumbnail_id = (int) get_post_thumbnail_id( $post_id );
			$file_path    = get_attached_file( $thumbnail_id );
			if ( $file_path && file_exists( $file_path ) ) {
				echo wp_kses_post( $thumbnail );
				return;
			}
		}

		echo '<span class="andw-tc-no-image" aria-hidden="true"></span>';
	}

	/**
	 * 管理画面にインライン CSS を追加する。
	 *
	 * @param string $hook Admin page hook suffix.
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

	/**
	 * 投稿タイプ入力値をサニタイズする。
	 *
	 * @param mixed $input Form input value.
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

// Activation hook: save default options.
register_activation_hook(
	__FILE__,
	function () {
		add_option( Andw_Tc_Plugin::OPTION_NAME, Andw_Tc_Plugin::DEFAULT_POST_TYPES );
	}
);

// Initialize only in admin.
if ( is_admin() ) {
	new Andw_Tc_Plugin();
}
