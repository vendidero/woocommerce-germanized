<?php
/**
 * Admin View: Notice - Theme supported
 *
 * @var WC_GZD_Admin_Note $notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="notice <?php echo esc_attr( $notice->get_fallback_notice_type() ); ?> fade woocommerce-gzd-message">
	<?php if ( $notice->is_dismissable() ) : ?>
		<a class="woocommerce-gzd-message-close notice-dismiss" href="<?php echo esc_url( $notice->get_dismiss_url() ); ?>"><?php esc_html_e( 'Hide', 'woocommerce-germanized' ); ?></a>
	<?php endif; ?>

	<h3><?php echo esc_html( $notice->get_title() ); ?></h3>

	<?php echo wp_kses_post( wpautop( $notice->get_content() ) ); ?>

	<?php if ( $notice->has_actions() ) : ?>

		<p class="alignleft wc-gzd-button-wrapper">
			<?php
			foreach ( $notice->get_actions() as $action ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$action = wp_parse_args( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					$action,
					array(
						'title'      => '',
						'url'        => '',
						'is_primary' => true,
						'target'     => '_blank',
					)
				);
				?>
				<a class="button button-<?php echo esc_attr( $action['is_primary'] ? 'primary' : 'secondary' ); ?> wc-gzd-action-button-link" href="<?php echo esc_url( $notice->get_action_url( $action ) ); ?>" target="<?php echo esc_attr( $action['target'] ); ?>"><?php echo esc_html( $action['title'] ); ?></a>
			<?php endforeach; ?>
		</p>

	<?php endif; ?>

	<?php if ( $notice->is_deactivatable() ) : ?>
		<p class="alignright wc-gzd-button-wrapper">
			<?php if ( $notice->is_deactivatable() ) : ?>
				<a href="<?php echo esc_url( $notice->get_deactivate_url() ); ?>"><?php echo esc_html( $notice->get_deactivate_text() ); ?></a>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<div class="clear"></div>
</div>
