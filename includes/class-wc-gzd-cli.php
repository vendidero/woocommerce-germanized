<?php
/**
 * Enables Germanized, via the the command line.
 */

defined( 'ABSPATH' ) || exit;

/**
 * CLI class.
 */
class WC_GZD_CLI {
	/**
	 * Load required files and hooks to make the CLI work.
	 */
	public function __construct() {
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::add_hook( 'after_wp_load', array( $this, 'register_update_command' ) );
		}
	}

	public function register_update_command() {
		WP_CLI::add_command( 'wc_gzd update', array( $this, 'update_command' ) );
	}

	public function update_command() {
		global $wpdb;

		$wpdb->hide_errors();

		include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-install.php';

		$current_db_version = get_option( 'woocommerce_gzd_db_version' );
		$update_count       = 0;
		$callbacks          = WC_GZD_Install::get_db_update_callbacks();
		$scripts_to_run     = array();

		foreach ( $callbacks as $version => $update_callback ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				$scripts_to_run[ $version ] = $update_callback;
			}
		}

		if ( empty( $scripts_to_run ) ) {
			// Ensure DB version is set to the current WC version to match WP-Admin update routine.
			WC_GZD_Install::update_db_version();
			/* translators: %s Database version number */
			WP_CLI::success( sprintf( __( 'No updates required. Database version is %s', 'woocommerce-germanized' ), get_option( 'woocommerce_gzd_db_version' ) ) );
			return;
		}

		/* translators: 1: Number of database updates 2: List of update callbacks */
		WP_CLI::log( sprintf( __( 'Found %1$d updates (%2$s)', 'woocommerce-germanized' ), count( $scripts_to_run ), implode( ', ', $scripts_to_run ) ) );

		$progress = \WP_CLI\Utils\make_progress_bar( __( 'Updating database', 'woocommerce-germanized' ), count( $scripts_to_run ) ); // phpcs:ignore PHPCompatibility.LanguageConstructs.NewLanguageConstructs.t_ns_separatorFound

		foreach ( $scripts_to_run as $version => $script ) {
			include $script;
			WC_GZD_Install::update_db_version( $version );
			$update_count ++;
			$progress->tick();
		}

		$progress->finish();

		WC_GZD_Install::update_db_version();

		if ( $note = WC_GZD_Admin_Notices::instance()->get_note( 'update' ) ) {
			$note->dismiss();
		}

		delete_option( '_wc_gzd_needs_update' );
		delete_transient( '_wc_gzd_activation_redirect' );

		/* translators: 1: Number of database updates performed 2: Database version number */
		WP_CLI::success( sprintf( __( '%1$d update functions completed. Database version is %2$s', 'woocommerce-germanized' ), absint( $update_count ), get_option( 'woocommerce_gzd_db_version' ) ) );
	}
}

new WC_GZD_CLI();
