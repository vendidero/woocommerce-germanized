<?php

namespace Vendidero\OneStopShop;

defined( 'ABSPATH' ) || exit;

class AdminNote {

	public static function get_id() {
		return '';
	}

	public static function get_type() {
		return 'warning';
	}

	public static function get_content() {
		return '';
	}

	public static function get_title() {
		return '';
	}

	public static function get_dismiss_url() {
		return add_query_arg( array( 'action' => 'oss_hide_notice', 'notice' => static::get_id(), '_wpnonce' => wp_create_nonce( 'oss_hide_notice' ) ), admin_url( 'admin-post.php' ) );
	}

	public static function has_actions() {
		$actions = static::get_actions();

		return empty( $actions ) ? false : true;
	}

	public static function get_actions() {
		return array(
            array(
	            'target'      => '',
	            'title'       => _x( 'Dismiss', 'oss', 'woocommerce-germanized' ),
	            'url'         => static::get_dismiss_url(),
	            'is_primary'  => false,
            )
        );
	}

	public static function is_enabled() {
		$enabled = true;

		if ( 'yes' === get_option( 'oss_hide_notice_' . sanitize_key( static::get_id() ) ) ) {
			$enabled = false;
		}

		return $enabled;
	}

	public static function render() {
		?>
		<div class="notice notice-<?php echo esc_attr( static::get_type() ); ?> <?php echo esc_attr( static::get_id() ); ?> fade oss-woocommerce-message" style="position: relative">
			<a class="oss-woocommerce-notice-dismiss notice-dismiss" style="text-decoration: none;" href="<?php echo esc_url( static::get_dismiss_url() ); ?>"></a>

			<h3><?php echo static::get_title(); ?></h3>
			<?php echo wpautop( static::get_content() ); ?>

			<?php if ( static::has_actions() ) : ?>
				<p class="oss-woocommerce-button-wrapper">
					<?php foreach( static::get_actions() as $action ) :
						$action = wp_parse_args( $action, array(
							'title'      => '',
							'url'        => '',
							'is_primary' => true,
							'target'     => '_blank'
						) );
						?>
						<a class="button button-<?php echo ( $action['is_primary'] ? 'primary' : 'secondary' ); ?> oss-woocommerce-button-link" style="margin-right: .5em;" href="<?php echo esc_url( $action['url'] ); ?>" target="<?php echo esc_attr( $action['target'] ); ?>"><?php echo $action['title']; ?></a>
					<?php endforeach; ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
