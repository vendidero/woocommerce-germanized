<?php
/**
 * Admin View: Settings section
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$section_key = sanitize_key( $current_section );
?>

<div class="wc-gzd-admin-settings <?php echo ( ! empty( $sidebar ) ? 'wc-gzd-admin-settings-has-sidebar' : '' ); ?> wc-gzd-admin-settings-<?php echo $current_tab; ?> <?php echo ( ! empty( $current_section ) ? 'wc-gzd-admin-settings-' . $current_tab . '-' . $section_key : '' ); ?>">
    <div class="wc-gzd-admin-settings-fields">
        <?php WC_Admin_Settings::output_fields( $settings ); ?>
    </div>

    <?php if ( ! empty( $sidebar ) ) : ?>
        <div class="wc-gzd-admin-settings-sidebar">
	        <?php echo $sidebar; ?>
        </div>
    <?php endif; ?>
</div>