<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Vendidero\Germanized\Packages;

if ( ! class_exists( 'WC_GZD_Install' ) ) :

	/**
	 * Installation related functions and hooks
	 *
	 * @class        WC_GZD_Install
	 * @version        1.0.0
	 * @author        Vendidero
	 */
	class WC_GZD_Install {

		/** @var array DB updates that need to be run */
		private static $db_updates = array(
			'1.0.4'   => 'updates/woocommerce-gzd-update-1.0.4.php',
			'1.4.2'   => 'updates/woocommerce-gzd-update-1.4.2.php',
			'1.4.6'   => 'updates/woocommerce-gzd-update-1.4.6.php',
			'1.5.0'   => 'updates/woocommerce-gzd-update-1.5.0.php',
			'1.6.0'   => 'updates/woocommerce-gzd-update-1.6.0.php',
			'1.6.3'   => 'updates/woocommerce-gzd-update-1.6.3.php',
			'1.8.0'   => 'updates/woocommerce-gzd-update-1.8.0.php',
			'1.8.9'   => 'updates/woocommerce-gzd-update-1.8.9.php',
			'1.9.2'   => 'updates/woocommerce-gzd-update-1.9.2.php',
			'2.0.1'   => 'updates/woocommerce-gzd-update-2.0.1.php',
			'2.2.5'   => 'updates/woocommerce-gzd-update-2.2.5.php',
			'2.3.0'   => 'updates/woocommerce-gzd-update-2.3.0.php',
			'3.0.0'   => 'updates/woocommerce-gzd-update-3.0.0.php',
			'3.0.1'   => 'updates/woocommerce-gzd-update-3.0.1.php',
			'3.0.6'   => 'updates/woocommerce-gzd-update-3.0.6.php',
			'3.0.8'   => 'updates/woocommerce-gzd-update-3.0.8.php',
			'3.1.6'   => 'updates/woocommerce-gzd-update-3.1.6.php',
			'3.1.9'   => 'updates/woocommerce-gzd-update-3.1.9.php',
			'3.3.4'   => 'updates/woocommerce-gzd-update-3.3.4.php',
			'3.3.5'   => 'updates/woocommerce-gzd-update-3.3.5.php',
			'3.4.0'   => 'updates/woocommerce-gzd-update-3.4.0.php',
			'3.7.0'   => 'updates/woocommerce-gzd-update-3.7.0.php',
			'3.8.0'   => 'updates/woocommerce-gzd-update-3.8.0.php',
			'3.9.1'   => 'updates/woocommerce-gzd-update-3.9.1.php',
			'3.9.3'   => 'updates/woocommerce-gzd-update-3.9.3.php',
			'3.10.0'  => 'updates/woocommerce-gzd-update-3.10.0.php',
			'3.10.4'  => 'updates/woocommerce-gzd-update-3.10.4.php',
			'3.12.2'  => 'updates/woocommerce-gzd-update-3.12.2.php',
			'3.13.2'  => 'updates/woocommerce-gzd-update-3.13.2.php',
			'3.15.5'  => 'updates/woocommerce-gzd-update-3.15.5.php',
			'3.16.3'  => 'updates/woocommerce-gzd-update-3.16.3.php',
			'3.19.12' => 'updates/woocommerce-gzd-update-3.19.12.php',
			'3.19.13' => 'updates/woocommerce-gzd-update-3.19.13.php',
			'3.20.0'  => 'updates/woocommerce-gzd-update-3.20.0.php',
		);

		/**
		 * Hook in tabs.
		 */
		public function __construct() {
			add_action( 'init', array( __CLASS__, 'check_version' ), 10 );
			add_action( 'admin_init', array( __CLASS__, 'redirect' ), 15 );

			add_action(
				'in_plugin_update_message-woocommerce-germanized/woocommerce-germanized.php',
				array(
					__CLASS__,
					'in_plugin_update_message',
				)
			);
		}

		public static function redirect() {
			if ( ! empty( $_GET['do_update_woocommerce_gzd'] ) && current_user_can( 'manage_woocommerce' ) ) {
				check_admin_referer( 'wc_gzd_db_update', 'wc_gzd_db_update_nonce' );

				self::update();

				// Update complete
				delete_option( '_wc_gzd_needs_pages' );
				delete_option( '_wc_gzd_needs_update' );

				if ( $note = WC_GZD_Admin_Notices::instance()->get_note( 'update' ) ) {
					$note->dismiss();
				}

				delete_transient( '_wc_gzd_activation_redirect' );

				// What's new redirect
				wp_safe_redirect( esc_url_raw( admin_url( 'index.php?page=wc-gzd-about&wc-gzd-updated=true' ) ) );
				exit;
			}

			if ( get_transient( '_wc_gzd_setup_wizard_redirect' ) ) {
				$do_redirect = true;

				// Bail if activating from network, or bulk, or within an iFrame, or AJAX (e.g. plugins screen)
				if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) || wp_doing_ajax() || ! current_user_can( 'manage_woocommerce' ) ) {
					$do_redirect = false;
				}

				if ( ( isset( $_REQUEST['action'] ) && 'upgrade-plugin' === $_REQUEST['action'] ) && ( isset( $_REQUEST['plugin'] ) && strstr( wc_clean( wp_unslash( $_REQUEST['plugin'] ) ), 'woocommerce-germanized.php' ) ) ) {
					$do_redirect = false;
				}

				// Prevent redirect loop in case options fail
				if (
					apply_filters( 'woocommerce_gzd_disable_setup_redirect', false ) ||
					( isset( $_GET['page'] ) && 'wc-gzd-setup' === wc_clean( wp_unslash( $_GET['page'] ) ) ) ||
					isset( $_GET['activate-multi'] )
				) {
					$do_redirect = false;

					delete_transient( '_wc_gzd_setup_wizard_redirect' );
				}

				if ( $do_redirect ) {
					delete_transient( '_wc_gzd_setup_wizard_redirect' );

					wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=wc-gzd-setup' ) ) );
					exit;
				}
			} elseif ( get_transient( '_wc_gzd_activation_redirect' ) ) {
				$do_redirect = true;

				// Bail if activating from network, or bulk, or within an iFrame, or AJAX (e.g. plugins screen)
				if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) || wp_doing_ajax() || ! current_user_can( 'manage_woocommerce' ) ) {
					$do_redirect = false;
				}

				if ( ( isset( $_REQUEST['action'] ) && 'upgrade-plugin' === $_REQUEST['action'] ) && ( isset( $_REQUEST['plugin'] ) && strstr( wc_clean( wp_unslash( $_REQUEST['plugin'] ) ), 'woocommerce-germanized.php' ) ) ) {
					$do_redirect = false;
				}

				if (
					apply_filters( 'woocommerce_gzd_disable_activation_redirect', false ) ||
					get_option( '_wc_gzd_needs_update' ) ||
					( isset( $_GET['page'] ) && 'wc-gzd-about' === wc_clean( wp_unslash( $_GET['page'] ) ) ) ||
					isset( $_GET['activate-multi'] )
				) {
					$do_redirect = false;

					delete_transient( '_wc_gzd_activation_redirect' );
				}

				if ( $do_redirect ) {
					delete_transient( '_wc_gzd_activation_redirect' );

					wp_safe_redirect( esc_url_raw( admin_url( 'index.php?page=wc-gzd-about' ) ) );
					exit;
				}
			}
		}

		/**
		 * check_version function.
		 *
		 * @access public
		 * @return void
		 */
		public static function check_version() {
			if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_gzd_version' ) !== WC_germanized()->version ) ) {
				self::install();

				/**
				 * Plugin updated.
				 *
				 * Germanized was updated to a new version.
				 *
				 * @since 1.0.0
				 */
				do_action( 'woocommerce_gzd_updated' );
			}
		}

		public static function get_shiptastic_db_updates( $force_override = false, $is_downgrade = false ) {
			global $wpdb;
			$wpdb->hide_errors();

			$db_updates      = array();
			$existing_prefix = ! $is_downgrade ? 'gzd' : 'stc';
			$new_prefix      = ! $is_downgrade ? 'stc' : 'gzd';

			/**
			 * Migrate tables
			 */
			$tables = array(
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_shipment_itemmeta" => array(
					"{$existing_prefix}_shipment_item_id" => 'bigint(20) unsigned NOT NULL',
				),
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_shipment_items" => array(),
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_shipments" => array(),
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_shipmentmeta" => array(
					"{$existing_prefix}_shipment_id" => 'bigint(20) unsigned NOT NULL',
				),
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_shipment_labels" => array(),
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_shipment_labelmeta" => array(
					"{$existing_prefix}_shipment_label_id" => 'bigint(20) unsigned NOT NULL',
				),
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_packaging" => array(),
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_packagingmeta" => array(
					"{$existing_prefix}_packaging_id" => 'bigint(20) unsigned NOT NULL',
				),
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_shipping_provider" => array(),
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_shipping_providermeta" => array(
					"{$existing_prefix}_shipping_provider_id" => 'bigint(20) unsigned NOT NULL',
				),
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_dhl_im_products" => array(),
				"{$wpdb->prefix}woocommerce_{$existing_prefix}_dhl_im_product_services" => array(),
			);

			foreach ( $tables as $table => $columns ) {
				$exists           = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
				$new_table_name   = str_replace( "woocommerce_{$existing_prefix}_", "woocommerce_{$new_prefix}_", $table );
				$new_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $new_table_name ) ) );

				if ( $exists && $exists === $table ) {
					/**
					 * Skip table if exists
					 */
					if ( ! $new_table_exists || $new_table_exists !== $new_table_name || $force_override ) {
						if ( $force_override && strstr( $table, "woocommerce_{$existing_prefix}_" ) ) {
							$db_updates[ $table ] = array(
								'main'       => array(
									"DROP TABLE IF EXISTS `{$new_table_name}`;",
									"RENAME TABLE `{$table}` TO `{$new_table_name}`;",
								),
								'additional' => array(),
							);
						} else {
							$db_updates[ $table ] = array(
								'main'       => array( "RENAME TABLE `{$table}` TO `{$new_table_name}`;" ),
								'additional' => array(),
							);
						}

						foreach ( $columns as $column => $column_data_type ) {
							$new_column_name = str_replace( "{$existing_prefix}_", "{$new_prefix}_", $column );

							$db_updates[ $table ]['additional'][] = "ALTER TABLE `{$new_table_name}` CHANGE `{$column}` `{$new_column_name}` {$column_data_type};";
						}

						if ( "{$wpdb->prefix}woocommerce_{$existing_prefix}_shipments" === $table ) {
							if ( ! $is_downgrade ) {
								$db_updates[ $table ]['additional'][] = $wpdb->prepare( "UPDATE {$new_table_name} SET shipment_status = REPLACE(shipment_status, %s, %s);", 'gzd-', '' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							} else {
								$db_updates[ $table ]['additional'][] = $wpdb->prepare( "UPDATE {$new_table_name} SET shipment_status = CONCAT(%s, shipment_status);", 'gzd-' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							}
						} elseif ( "{$wpdb->prefix}woocommerce_{$existing_prefix}_shipping_providermeta" === $table ) {
							/**
							 * Migrate provider status hooks
							 */
							$provider_status_meta = array(
								'_label_auto_shipment_status',
								'_label_return_auto_shipment_status',
							);

							foreach ( $provider_status_meta as $meta_key ) {
								if ( ! $is_downgrade ) {
									$db_updates[ $table ]['additional'][] = $wpdb->prepare( "UPDATE {$new_table_name} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key = %s;", 'gzd-', '', $meta_key ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								} else {
									$db_updates[ $table ]['additional'][] = $wpdb->prepare( "UPDATE {$new_table_name} SET meta_value = CONCAT(%s, meta_value) WHERE meta_key = %s;", 'gzd-', $meta_key ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								}
							}
						}
					}
				} elseif ( $force_override && $new_table_exists && $new_table_exists === $new_table_name ) {
					foreach ( $columns as $column => $column_data_type ) {
						$old_column_exists = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `{$new_table_name}` LIKE %s", $wpdb->esc_like( $column ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

						if ( $old_column_exists && $old_column_exists === $column ) {
							$new_column_name   = str_replace( "{$existing_prefix}_", "{$new_prefix}_", $column );
							$new_column_exists = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `{$new_table_name}` LIKE %s", $wpdb->esc_like( $new_column_name ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

							if ( ! isset( $db_updates[ $new_table_name ] ) ) {
								$db_updates[ $new_table_name ] = array(
									'main'       => array(),
									'additional' => array(),
								);
							}

							if ( $new_column_exists && $new_column_name === $new_column_exists ) {
								$db_updates[ $new_table_name ]['main'][] = "ALTER TABLE `{$new_table_name}` DROP COLUMN `{$new_column_name}`;";
							}

							$db_updates[ $new_table_name ]['additional'][] = "ALTER TABLE `{$new_table_name}` CHANGE `{$column}` `{$new_column_name}` {$column_data_type};";
						}
					}
				}
			}

			if ( ! $is_downgrade ) {
				/**
				 * Do only try to rename options in case legacy options do still exist
				 */
				$legacy_options      = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( 'woocommerce_gzd_shipments_' ) . '%', $wpdb->esc_like( 'woocommerce_gzd_dhl_' ) . '%' ) );
				$legacy_option_names = self::get_legacy_option_names( $legacy_options );

				if ( ! empty( $legacy_option_names ) ) {
					$legacy_options_in = "'" . implode( "','", $legacy_option_names ) . "'";

					$db_updates[ $wpdb->options ] = array(
						'main'       => array( "DELETE FROM {$wpdb->options} WHERE option_name IN ($legacy_options_in);" ),
						'additional' => array(
							$wpdb->prepare( "UPDATE {$wpdb->options} SET option_name = REPLACE(option_name, %s, %s);", 'woocommerce_gzd_dhl_', 'woocommerce_shiptastic_dhl_' ),
							$wpdb->prepare( "UPDATE {$wpdb->options} SET option_name = REPLACE(option_name, %s, %s);", 'woocommerce_gzd_shipments_', 'woocommerce_shiptastic_' ),
						),
					);

					$status_options = array(
						'woocommerce_shiptastic_auto_default_status',
					);

					foreach ( $status_options as $option ) {
						if ( in_array( $option, $legacy_option_names, true ) ) {
							$db_updates[ $wpdb->options ]['additional'][] = $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, %s, %s) WHERE option_name = %s;", 'gzd-', '', $option );
						}
					}
				}
			} else {
				/**
				 * Do only try to rename options in case legacy options do still exist
				 */
				$legacy_options      = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'woocommerce_shiptastic_' ) . '%' ) );
				$legacy_option_names = self::get_legacy_option_names( $legacy_options, true );

				if ( ! empty( $legacy_option_names ) ) {
					$legacy_options_in = "'" . implode( "','", $legacy_option_names ) . "'";

					$db_updates[ $wpdb->options ] = array(
						'main'       => array( "DELETE FROM {$wpdb->options} WHERE option_name IN ($legacy_options_in);" ),
						'additional' => array(
							$wpdb->prepare( "UPDATE {$wpdb->options} SET option_name = REPLACE(option_name, %s, %s);", 'woocommerce_shiptastic_dhl_', 'woocommerce_gzd_dhl_' ),
							$wpdb->prepare( "UPDATE {$wpdb->options} SET option_name = REPLACE(option_name, %s, %s);", 'woocommerce_shiptastic_', 'woocommerce_gzd_shipments_' ),
						),
					);

					$status_options = array(
						'woocommerce_gzd_shipments_auto_default_status',
					);

					foreach ( $status_options as $option ) {
						if ( in_array( $option, $legacy_option_names, true ) ) {
							$db_updates[ $wpdb->options ]['additional'][] = $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = CONCAT(%s, option_value) WHERE option_name = %s;", 'gzd-', $option );
						}
					}
				}
			}

			return $db_updates;
		}

		protected static function get_legacy_option_names( $legacy_options, $is_downgrade = false ) {
			$option_names = array();

			foreach ( $legacy_options as $legacy_row ) {
				if ( $is_downgrade ) {
					$new_option_name = str_replace( 'woocommerce_shiptastic_dhl_', 'woocommerce_gzd_dhl_', $legacy_row->option_name );
					$new_option_name = str_replace( 'woocommerce_shiptastic_', 'woocommerce_gzd_shipments_', $new_option_name );
				} else {
					$new_option_name = str_replace( 'woocommerce_gzd_dhl_', 'woocommerce_shiptastic_dhl_', $legacy_row->option_name );
					$new_option_name = str_replace( 'woocommerce_gzd_shipments_', 'woocommerce_shiptastic_', $new_option_name );
				}

				$option_names[] = $new_option_name;
			}

			return $option_names;
		}

		public static function get_shipments_legacy_upload_folder() {
			add_filter(
				'woocommerce_shiptastic_upload_dir_name',
				function ( $upload_dir ) {
					return str_replace( 'wc-shiptastic-', 'wc-gzd-shipments-', $upload_dir );
				},
				100
			);
			$legacy_dir = \Vendidero\Germanized\Shiptastic::get_upload_dir();
			remove_all_filters( 'woocommerce_shiptastic_upload_dir_name' );

			if ( @is_dir( $legacy_dir['basedir'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return $legacy_dir;
			}

			return false;
		}

		public static function migrate_shipments_to_shiptastic( $force_override = false, $is_initial_migration = false ) {
			global $wpdb;
			$error = new WP_Error();
			$wpdb->hide_errors();

			if ( $queue = WC()->queue() ) {
				$queue->cancel_all( 'woocommerce_gzd_shipments_daily_cleanup', array(), 'woocommerce_gzd_shipments' );
			}

			delete_option( 'woocommerce_gzd_shiptastic_migration_has_errors' );
			delete_option( 'woocommerce_gzd_shiptastic_migration_errors' );

			/**
			 * Force delete the upload dir suffix which may be created
			 * before running the check version hook.
			 */
			if ( get_option( 'woocommerce_gzd_shipments_upload_dir_suffix' ) ) {
				delete_option( 'woocommerce_shiptastic_upload_dir_suffix' );
			}

			$wpdb->flush();

			foreach ( self::get_shiptastic_db_updates( $force_override ) as $table => $db_updates ) {
				$db_updates = wp_parse_args(
					$db_updates,
					array(
						'main'       => array(),
						'additional' => array(),
					)
				);

				$exists         = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
				$has_main_error = false;

				if ( $exists && $exists === $table ) {
					if ( ! empty( $db_updates['main'] ) ) {
						foreach ( $db_updates['main'] as $main_query ) {
							$result = $wpdb->query( $main_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

							if ( false === $result ) {
								$last_error = $wpdb->last_error;
								$error->add( 'main_query', sprintf( _x( 'Error while querying %1$s: %2$s', 'shipments-migration', 'woocommerce-germanized' ), $table, $last_error ) );
								$has_main_error = true;
								break;
							}
						}
					}

					// Do not run additional queries in case of error within main query.
					if ( $has_main_error ) {
						continue;
					}

					$mute_errors = false;

					/**
					 * Mute duplicate wp_options key errors on subsequent migration requests
					 */
					if ( $wpdb->options === $table && ! $is_initial_migration ) {
						$mute_errors = true;
					}

					foreach ( $db_updates['additional'] as $db_query ) {
						$result = $wpdb->query( $db_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

						if ( false === $result && ! $mute_errors ) {
							$last_error = $wpdb->last_error;
							$error->add( 'additional_query', sprintf( _x( 'Error while running additional query on %1$s: %2$s', 'shipments-migration', 'woocommerce-germanized' ), $table, $last_error ) );
						}
					}
				}
			}

			/**
			 * Force reloading options, e.g. upload dir suffix
			 */
			$wpdb->flush();
			wp_cache_delete( 'alloptions', 'options' );
			wp_load_alloptions();

			/**
			 * Migrate packaging reports as this data is serialized
			 */
			if ( get_option( 'woocommerce_shiptastic_packaging_reports' ) ) {
				$report_data     = (array) get_option( 'woocommerce_shiptastic_packaging_reports', array() );
				$new_report_data = array();

				foreach ( $report_data as $type => $reports ) {
					$new_report_data[ $type ] = array();

					foreach ( (array) $reports as $report_name ) {
						$new_report_data[ $type ][] = str_replace( 'woocommerce_gzd_shipments_packaging_', 'woocommerce_shiptastic_packaging_', $report_name );
					}
				}

				update_option( 'woocommerce_shiptastic_packaging_reports', $new_report_data, false );
			}

			$new_dir    = \Vendidero\Germanized\Shiptastic::get_upload_dir();
			$legacy_dir = self::get_shipments_legacy_upload_folder();

			if ( false !== $legacy_dir ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( @is_dir( $new_dir['basedir'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					$tmp_new_name_folder = str_replace( 'wc-shiptastic', 'wc-shiptastic-' . wp_rand( 1, 1000 ), $new_dir['basedir'] );
					@rename( $new_dir['basedir'], $tmp_new_name_folder ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
				}

				/**
				 * Rename if new folder name does not yet exist
				 */
				if ( ! @is_dir( $new_dir['basedir'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					$rename_result = @rename( $legacy_dir['basedir'], $new_dir['basedir'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename

					if ( false === $rename_result ) {
						$error->add( 'folder_rename_failed', sprintf( _x( 'Could not rename %1$s to %2$s.', 'shipments-migration', 'woocommerce-germanized' ), $legacy_dir['basedir'], $new_dir['basedir'] ) );
					}
				}
			}

			if ( wc_gzd_wp_error_has_errors( $error ) ) {
				update_option( 'woocommerce_gzd_shiptastic_migration_has_errors', 'yes', true );
				update_option( 'woocommerce_gzd_shiptastic_migration_errors', $error->errors, false );
			}

			return wc_gzd_wp_error_has_errors( $error ) ? $error : true;
		}

		/**
		 * Install WC_Germanized
		 */
		public static function install() {
			if ( ! defined( 'WC_GZD_INSTALLING' ) ) {
				define( 'WC_GZD_INSTALLING', true );
			}

			$current_version    = get_option( 'woocommerce_gzd_version', null );
			$current_db_version = get_option( 'woocommerce_gzd_db_version', null );

			// Load Translation for default options
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-germanized' );
			$mofile = WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized.mo';

			if ( file_exists( WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized-' . $locale . '.mo' ) ) {
				$mofile = WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized-' . $locale . '.mo';
			}

			load_textdomain( 'woocommerce-germanized', $mofile );

			if ( ! \Vendidero\Germanized\PluginsHelper::is_woocommerce_plugin_active() || ! function_exists( 'WC' ) ) {
				if ( is_admin() ) {
					deactivate_plugins( WC_GERMANIZED_PLUGIN_FILE );
					wp_die( esc_html__( 'Please install WooCommerce before installing WooCommerce Germanized. Thank you!', 'woocommerce-germanized' ) );
				} else {
					return;
				}
			}

			// Register post types
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-post-types.php';
			WC_GZD_Post_types::register_taxonomies();

			self::create_cron_jobs();

			/**
			 * Enable logging in packages during installation
			 */
			add_filter( 'woocommerce_shiptastic_enable_logging', '__return_true', 5 );
			add_filter( 'oss_woocommerce_enable_extended_logging', '__return_true', 5 );

			if ( ! is_null( $current_db_version ) && version_compare( $current_db_version, '3.19.0', '<' ) ) {
				self::migrate_shipments_to_shiptastic( false, true );
			}

			self::install_packages();

			/**
			 * Do only import default units + label on first install
			 */
			if ( is_null( $current_version ) ) {
				self::create_units();
				self::create_labels();
				self::adjust_checkout_block();
			}

			self::create_options();

			// Delete plugin header data for dependency check
			delete_option( 'woocommerce_gzd_plugin_header_data' );

			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/class-wc-gzd-admin-notices.php';
			$notices = WC_GZD_Admin_Notices::instance();

			// Refresh notes
			foreach ( $notices->get_notes() as $note ) {
				$note->delete_note();
			}

			// Recheck outdated templates
			if ( $note = $notices->get_note( 'template_outdated' ) ) {
				$note->reset();
			}

			// Show the importer
			if ( $note = $notices->get_note( 'dhl_importer' ) ) {
				$note->reset();
			}

			// Show the importer
			if ( $note = $notices->get_note( 'internetmarke_importer' ) ) {
				$note->reset();
			}

			// Recheck Shiptastic install
			if ( $note = $notices->get_note( 'shiptastic_install' ) ) {
				$note->reset();
			}

			// Queue messages and notices
			if ( ! is_null( $current_version ) ) {
				$major_version     = \Vendidero\Germanized\PluginsHelper::get_major_version( $current_version );
				$new_major_version = \Vendidero\Germanized\PluginsHelper::get_major_version( WC_germanized()->version );

				// Only on major update
				if ( version_compare( $new_major_version, $major_version, '>' ) ) {
					if ( $note = $notices->get_note( 'pro' ) ) {
						$note->reset();
					}

					if ( $note = $notices->get_note( 'theme_supported' ) ) {
						$note->reset();
					}
				}

				if ( version_compare( $current_version, '3.14.0', '<' ) && ( wc_gzd_current_theme_is_fse_theme() || wc_gzd_has_checkout_block() ) ) {
					$notices->activate_blocks_note();
				}
			}

			/**
			 * Decides whether Germanized needs a database update.
			 *
			 * @param boolean Whether a database update is needed or not.
			 *
			 * @since 3.0.0
			 *
			 */
			if ( apply_filters( 'woocommerce_gzd_needs_db_update', self::needs_db_update() ) ) {
				if ( apply_filters( 'woocommerce_gzd_enable_auto_update_db', true ) ) {
					self::update();
				} else {
					if ( $note = $notices->get_note( 'update' ) ) {
						$note->reset();
					}

					// Update
					update_option( '_wc_gzd_needs_update', 1 );
				}
			} else {
				self::update_db_version();
			}

			self::update_wc_gzd_version();

			// Update activation date
			update_option( 'woocommerce_gzd_activation_date', date( 'Y-m-d' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

			// Flush rules after install
			flush_rewrite_rules();

			// Check if pages are needed - start setup
			if ( wc_get_page_id( 'revocation' ) < 1 ) {
				set_transient( '_wc_gzd_setup_wizard_redirect', 1, 60 * 60 );
			} elseif ( ! defined( 'DOING_AJAX' ) ) {
				// Redirect to welcome screen
				set_transient( '_wc_gzd_activation_redirect', 1, 60 * 60 );
			}

			/**
			 * Plugin installed.
			 *
			 * Germanized was installed successfully.
			 *
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_gzd_installed' );
		}

		protected static function install_packages() {
			foreach ( Packages::get_packages() as $package_slug => $namespace ) {
				if ( is_callable( array( $namespace, 'install_integration' ) ) ) {
					$namespace::install_integration();
				}
			}
		}

		public static function deactivate() {
			// Clear Woo sessions to remove WC_GZD_Shipping_Rate instance
			if ( class_exists( 'WC_REST_System_Status_Tools_Controller' ) ) {
				$tools_controller = new WC_REST_System_Status_Tools_Controller();
				$tools_controller->execute_tool( 'clear_sessions' );
			}

			/**
			 * Remove notices.
			 */
			$notices = WC_GZD_Admin_Notices::instance();

			foreach ( $notices->get_notes() as $note ) {
				$note->delete_note();
			}

			wp_clear_scheduled_hook( 'woocommerce_gzd_customer_cleanup' );

			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				$hooks = array(
					'woocommerce_shiptastic_daily_cleanup',
				);

				foreach ( $hooks as $hook ) {
					as_unschedule_all_actions( $hook );
				}
			}
		}

		/**
		 * Update WC version to current
		 */
		private static function update_wc_gzd_version() {
			delete_option( 'woocommerce_gzd_version' );
			add_option( 'woocommerce_gzd_version', WC_germanized()->version );
		}

		/**
		 * Update DB version to current
		 */
		public static function update_db_version( $version = null ) {
			delete_option( 'woocommerce_gzd_db_version' );
			add_option( 'woocommerce_gzd_db_version', is_null( $version ) ? WC_germanized()->version : $version );
		}

		public static function get_db_update_callbacks() {
			return self::$db_updates;
		}

		private static function needs_db_update() {
			$current_db_version = get_option( 'woocommerce_gzd_db_version', null );

			if ( ! is_null( $current_db_version ) && ! empty( $current_db_version ) ) {
				foreach ( self::$db_updates as $version => $updater ) {
					if ( version_compare( $current_db_version, $version, '<' ) ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Handle updates
		 */
		public static function update() {
			$current_db_version = get_option( 'woocommerce_gzd_db_version', null );

			if ( ! is_null( $current_db_version ) && ! empty( $current_db_version ) ) {
				foreach ( self::$db_updates as $version => $updater ) {
					if ( version_compare( $current_db_version, $version, '<' ) ) {
						include $updater;
						self::update_db_version( $version );
					}
				}
			}

			/**
			 * Runs as soon as a database update has been triggered by the user.
			 *
			 * @since 3.0.0
			 */
			do_action( 'woocommerce_gzd_db_update' );

			self::update_db_version();
		}

		/**
		 * Show plugin changes. Code adapted from W3 Total Cache.
		 */
		public static function in_plugin_update_message( $args ) {
			$transient_name = 'wc_gzd_upgrade_notice_' . $args['Version'];

			if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {
				$response = wp_safe_remote_get( 'https://plugins.svn.wordpress.org/woocommerce-germanized/trunk/readme.txt' );

				if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
					$upgrade_notice = self::parse_update_notice( $response['body'] );
					set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
				}
			}

			echo wp_kses_post( $upgrade_notice );
		}

		/**
		 * Parse update notice from readme file
		 *
		 * @param string $content
		 *
		 * @return string
		 */
		private static function parse_update_notice( $content ) {
			// Output Upgrade Notice
			$matches        = null;
			$regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( WC_GERMANIZED_VERSION ) . '\s*=|$)~Uis'; // phpcs:ignore WordPress.PHP.PregQuoteDelimiter.Missing
			$upgrade_notice = '';

			if ( preg_match( $regexp, $content, $matches ) ) {
				$version = trim( $matches[1] );
				$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

				if ( version_compare( WC_GERMANIZED_VERSION, $version, '<' ) ) {

					$upgrade_notice .= '<div class="wc_plugin_upgrade_notice">';

					foreach ( $notices as $index => $line ) {
						$upgrade_notice .= wp_kses_post( preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line ) );
					}

					$upgrade_notice .= '</div> ';
				}
			}

			return wp_kses_post( $upgrade_notice );
		}

		/**
		 * Create cron jobs (clear them first)
		 */
		private static function create_cron_jobs() {
			// Cron jobs
			wp_clear_scheduled_hook( 'woocommerce_gzd_customer_cleanup' );
			wp_schedule_event( time(), 'daily', 'woocommerce_gzd_customer_cleanup' );
		}

		public static function create_units() {
			$units = include WC_Germanized()->plugin_path() . '/i18n/units.php';

			if ( ! empty( $units ) ) {
				foreach ( $units as $slug => $unit ) {
					wp_insert_term( $unit, 'product_unit', array( 'slug' => $slug ) );
				}
			}
		}

		public static function create_labels() {
			$labels = include WC_Germanized()->plugin_path() . '/i18n/labels.php';

			if ( ! empty( $labels ) ) {
				foreach ( $labels as $slug => $unit ) {
					wp_insert_term( $unit, 'product_price_label', array( 'slug' => $slug ) );
				}
			}
		}

		/**
		 * Replace core term checkbox with Germanized checkboxes block.
		 *
		 * @return void
		 */
		public static function adjust_checkout_block() {
			$page_id = wc_get_page_id( 'checkout' );

			if ( $checkout_post = get_post( $page_id ) ) {
				if ( function_exists( 'has_block' ) && has_block( 'woocommerce/checkout', $checkout_post ) ) {
					$post_content = $checkout_post->post_content;
					$post_content = str_replace( 'woocommerce/checkout-terms-block', 'woocommerce-germanized/checkout-checkboxes', $post_content );
					$post_content = str_replace( 'wp-block-woocommerce-checkout-terms-block', 'wp-block-woocommerce-germanized-checkout-checkboxes', $post_content );

					wp_update_post(
						array(
							'ID'           => $page_id,
							'post_content' => $post_content,
						)
					);
				}
			}
		}

		public static function create_tax_rates() {
			\Vendidero\EUTaxHelper\Helper::import_tax_rates();
		}

		/**
		 * Updates WooCommerce Options if user chooses to automatically adapt german options
		 */
		public static function set_default_settings() {
			global $wpdb;

			$base_country = wc_gzd_get_base_country();
			$eu_countries = ( isset( WC()->countries ) ) ? WC()->countries->get_european_union_countries() : array( $base_country );

			/**
			 * Woo introduced state field for DE
			 */
			if ( version_compare( WC()->version, '6.3.1', '>=' ) ) {
				if ( 'DE' === $base_country ) {
					$base_country = 'DE:DE-BE';
				}
			}

			$options = array(
				'woocommerce_default_country'            => $base_country,
				'woocommerce_currency'                   => 'EUR',
				'woocommerce_currency_pos'               => 'right_space',
				'woocommerce_price_thousand_sep'         => '.',
				'woocommerce_price_decimal_sep'          => ',',
				'woocommerce_price_num_decimals'         => 2,
				'woocommerce_weight_unit'                => 'kg',
				'woocommerce_dimension_unit'             => 'cm',
				'woocommerce_calc_taxes'                 => 'yes',
				'woocommerce_prices_include_tax'         => 'yes',
				'woocommerce_tax_round_at_subtotal'      => 'yes',
				'woocommerce_tax_display_cart'           => 'incl',
				'woocommerce_tax_display_shop'           => 'incl',
				'woocommerce_tax_total_display'          => 'itemized',
				'woocommerce_tax_based_on'               => 'shipping',
				'woocommerce_ship_to_countries'          => 'specific',
				'woocommerce_specific_ship_to_countries' => $eu_countries,
				'woocommerce_allowed_countries'          => 'specific',
				'woocommerce_specific_allowed_countries' => array_merge( $eu_countries, array( 'NO', 'LI', 'IS' ) ), // EWR Geoblocking https://de.wikipedia.org/wiki/Verordnung_(EU)_2018/302_(Geoblocking)
				'woocommerce_default_customer_address'   => 'base',
				'woocommerce_gzd_hide_tax_rate_shop'     => \Vendidero\EUTaxHelper\Helper::oss_procedure_is_enabled() ? 'yes' : 'no',
			);

			if ( ! empty( $options ) ) {
				foreach ( $options as $key => $option ) {
					update_option( $key, $option );
				}
			}
		}

		/**
		 * Create pages that the plugin relies on, storing page id's in variables.
		 *
		 * @access public
		 * @return void
		 */
		public static function create_pages() {
			if ( ! function_exists( 'wc_create_page' ) ) {
				include_once WC()->plugin_path() . '/includes/admin/wc-admin-functions.php';
			}

			/**
			 * Filter to add/edit pages to be created on install.
			 *
			 * @param array $pages Array containing page data.
			 *
			 * @since 1.0.0
			 *
			 */
			$pages = apply_filters(
				'woocommerce_gzd_create_pages',
				array(
					'data_security'       => array(
						'name'    => _x( 'data-security', 'Page slug', 'woocommerce-germanized' ),
						'title'   => _x( 'Privacy Policy', 'Page title', 'woocommerce-germanized' ),
						'content' => '',
					),
					'imprint'             => array(
						'name'    => _x( 'imprint', 'Page slug', 'woocommerce-germanized' ),
						'title'   => _x( 'Imprint', 'Page title', 'woocommerce-germanized' ),
						'content' => '[gzd_complaints]',
					),
					'terms'               => array(
						'name'    => _x( 'terms', 'Page slug', 'woocommerce-germanized' ),
						'title'   => _x( 'Terms & Conditions', 'Page title', 'woocommerce-germanized' ),
						'content' => '',
					),
					'revocation'          => array(
						'name'    => _x( 'revocation', 'Page slug', 'woocommerce-germanized' ),
						'title'   => _x( 'Cancellation Policy', 'Page title', 'woocommerce-germanized' ),
						'content' => '',
					),
					'shipping_costs'      => array(
						'name'    => _x( 'shipping-methods', 'Page slug', 'woocommerce-germanized' ),
						'title'   => _x( 'Shipping Methods', 'Page title', 'woocommerce-germanized' ),
						'content' => '',
					),
					'payment_methods'     => array(
						'name'    => _x( 'payment-methods', 'Page slug', 'woocommerce-germanized' ),
						'title'   => _x( 'Payment Methods', 'Page title', 'woocommerce-germanized' ),
						'content' => '[payment_methods_info]',
					),
					'review_authenticity' => array(
						'name'    => _x( 'review-authenticity', 'Page slug', 'woocommerce-germanized' ),
						'title'   => _x( 'Review Authenticity', 'Page title', 'woocommerce-germanized' ),
						'content' => '',
					),
				)
			);

			/**
			 * During new WP installs, post_name (which wc_create_page uses by default) is not set for automatically created pages - check title instead.
			 */
			add_filter( 'woocommerce_create_page_id', array( __CLASS__, 'woo_page_detection_callback' ), 10, 3 );

			foreach ( $pages as $key => $page ) {
				$page_id = wc_create_page( esc_sql( $page['name'] ), 'woocommerce_' . $key . '_page_id', $page['title'], '', ! empty( $page['parent'] ) ? wc_get_page_id( $page['parent'] ) : '' );

				if ( $page_id && ! empty( $page['content'] ) ) {
					wc_gzd_update_page_content( $page_id, $page['content'] );
				}
			}
		}

		public static function woo_page_detection_callback( $valid_page_found, $slug, $page_content ) {
			if ( null === $valid_page_found && _x( 'data-security', 'Page slug', 'woocommerce-germanized' ) === $slug ) {
				global $wpdb;
				$valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title LIKE %s AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) LIMIT 1;", _x( 'Privacy Policy', 'Page title', 'woocommerce-germanized' ) ) );
			}

			return $valid_page_found;
		}

		/**
		 * Default options
		 *
		 * Sets up the default options used on the settings page
		 *
		 * @access public
		 */
		public static function create_options() {
			// Include settings so that we can run through defaults
			include_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';

			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/settings/abstract-wc-gzd-settings-tab.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/class-wc-gzd-admin-legal-checkboxes.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/settings/class-wc-gzd-settings-germanized.php';

			$settings = false;

			if ( is_admin() ) {
				include_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';

				foreach ( WC_Admin_Settings::get_settings_pages() as $page ) {
					if ( is_a( $page, 'WC_GZD_Settings_Germanized' ) ) {
						$settings = $page;
					}
				}
			}

			if ( ! $settings ) {
				$settings = new WC_GZD_Settings_Germanized();
			}

			/**
			 * Filter to adjust default options to be created on install.
			 *
			 * @param array $settings The settings to be added as wp_option on install.
			 *
			 * @since 1.0.0
			 */
			$options = apply_filters( 'woocommerce_gzd_installation_default_settings', $settings->get_settings_for_section_core( '' ) );

			$manager = WC_GZD_Legal_Checkbox_Manager::instance();
			$manager->do_register_action();

			$checkbox_options = $manager->get_options();

			foreach ( $manager->get_checkboxes( array( 'is_core' => true ) ) as $id => $checkbox ) {
				if ( ! isset( $checkbox_options[ $id ] ) ) {
					$checkbox_options[ $id ] = array();
				}

				foreach ( $checkbox->get_form_fields() as $field ) {
					if ( isset( $field['default'] ) && isset( $field['id'] ) ) {
						$field_id = str_replace( $checkbox->get_form_field_id_prefix(), '', $field['id'] );

						if ( ! isset( $checkbox_options[ $id ][ $field_id ] ) ) {
							$checkbox_options[ $id ][ $field_id ] = $field['default'];
						}
					}
				}
			}

			$manager->update_options( $checkbox_options );

			$current_version = get_option( 'woocommerce_gzd_version', null );

			foreach ( $options as $value ) {
				$value = wp_parse_args(
					$value,
					array(
						'id'           => '',
						'default'      => null,
						'skip_install' => false,
						'autoload'     => true,
					)
				);

				if ( $value['default'] && ! empty( $value['id'] ) && ! $value['skip_install'] ) {
					wp_cache_delete( $value['id'], 'options' );

					$autoload = (bool) $value['autoload'];

					/**
					 * Older versions of Germanized did not include a default field for email
					 * attachment options. Skip adding the option in updates (which would override the empty default)
					 */
					if ( ! empty( $current_version ) && strstr( $value['id'], 'woocommerce_gzd_mail_attach_' ) ) {
						continue;
					}

					add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
				}
			}

			add_option( 'woocommerce_gzd_disable_food_options', 'no' );
		}
	}

endif;

return new WC_GZD_Install();
