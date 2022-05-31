<?php

namespace Vendidero\OneStopShop;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '1.2.3';

	/**
	 * Init the package
	 */
	public static function init() {
		if ( ! self::has_dependencies() ) {
			if ( ! self::is_integration() ) {
				add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
			}

			return;
		}

		self::init_hooks();

		if ( is_admin() ) {
			Admin::init();
		}

		Tax::init();
	}

	protected static function init_hooks() {
		if ( ! self::is_integration() ) {
			add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );
		}

		/**
		 * Listen to action scheduler hooks for report generation
		 */
		foreach( Queue::get_reports_running() as $id ) {
			$data = Package::get_report_data( $id );
			$type = $data['type'];

			// Skip open observer queue in case disabled
			if ( 'observer' === $type && ! Package::enable_auto_observer() ) {
				continue;
			}

			add_action( 'oss_woocommerce_' . $id, function( $args ) use ( $type ) {
				Queue::next( $type, $args );
			}, 10, 1 );
		}

		// Setup or cancel recurring observer task
		add_action( 'init', array( __CLASS__, 'setup_recurring_actions' ), 10 );
		add_action( 'oss_woocommerce_daily_cleanup', array( __CLASS__, 'cleanup' ), 10 );

		if ( Package::enable_auto_observer() ) {
			add_action( 'oss_woocommerce_daily_observer', array( __CLASS__, 'update_observer_report' ), 10 );
			add_action( 'oss_woocommerce_updated_observer', array( __CLASS__, 'maybe_send_notification' ), 10 );

			add_action( 'woocommerce_email_classes', array( __CLASS__, 'register_emails' ), 10 );
		}

		add_action( 'wc_admin_daily', array( '\Vendidero\OneStopShop\Admin', 'queue_wc_admin_notes' ) );
		add_action( 'woocommerce_note_updated', array( '\Vendidero\OneStopShop\Admin', 'on_wc_admin_note_update' ) );
	}

	public static function cleanup() {
	    $running              = array();
		$has_running_observer = false;
		$running_observers    = array();

		/**
		 * Remove reports from running Queue in case they are not queued any longer.
		 */
	    foreach( Queue::get_reports_running() as $report_id ) {
	        $details = Queue::get_queue_details( $report_id );

	        if ( $details['has_action'] && ! $details['is_finished'] ) {
		        if ( strstr( $report_id, 'observer_' ) ) {
		            $running_observers[]  = $report_id;
                    $has_running_observer = $report_id;
		        }

		        $running[] = $report_id;
            } else {
	            if ( $report = self::get_report( $report_id ) ) {
	                if ( 'completed' !== $report->get_status() ) {
		                $report->delete();
                    }
                }
            }
        }

		/**
		 * Make sure there is only one observer running at a time.
		 */
	    foreach( $running as $k => $report_id ) {
	        if ( in_array( $report_id, $running_observers ) && $report_id !== $has_running_observer ) {
	            if ( $report = self::get_report( $report_id ) ) {
	                $report->delete();
                }

	            unset( $running[ $k ] );
            }
        }

	    $running = array_values( $running );

		update_option( 'oss_woocommerce_reports_running', $running, false );
		Queue::clear_cache();

	    $observer_reports = self::get_reports( array(
            'type'             => 'observer',
            'include_observer' => true
        ) );

		foreach( $observer_reports as $observer ) {
		    if ( ! self::enable_auto_observer() ) {
			    /**
			     * Delete observers in case observing was disabled.
			     */
			    $observer->delete();
            } else {
			    /*
			     * Do not delete running observers (which are orphans by design)
			     */
			    if ( $observer->get_id() === $has_running_observer ) {
				    continue;
			    }

			    $year = $observer->get_date_start()->format( 'Y' );

			    /**
			     * Delete orphan observer reports (reports not linked as a main observer for a certain year).
			     */
			    if ( get_option( 'oss_woocommerce_observer_report_' . $year ) !== $observer->get_id() ) {
				    $observer->delete();
			    }
            }
		}

		/**
		 * In case the current observer report does not exist - delete the option
		 */
		if ( self::enable_auto_observer() ) {
		    $year      = date( 'Y' );
			$report_id = get_option( 'oss_woocommerce_observer_report_' . $year );

			if ( ! empty( $report_id ) ) {
				if ( ! Package::get_report( $report_id ) ) {
				    delete_option( 'oss_woocommerce_observer_report_' . $year );
                }
			}
        }
    }

	public static function dependency_notice() {
		?>
		<div class="error notice notice-error"><p><?php _ex( 'To use the OSS for WooCommerce plugin please make sure that WooCommerce is installed and activated.', 'oss', 'woocommerce-germanized' ); ?></p></div>
		<?php
	}

	public static function oss_procedure_is_enabled() {
		return 'yes' === get_option( 'oss_use_oss_procedure' );
	}

	public static function enable_auto_observer() {
		return 'yes' === get_option( 'oss_enable_auto_observation' );
	}

	public static function get_report_ids( $include_observer = true ) {
		$reports = (array) get_option( 'oss_woocommerce_reports', array() );

		foreach( array_keys( Package::get_available_report_types( $include_observer ) ) as $type ) {
			if ( ! array_key_exists( $type, $reports ) ) {
				$reports[ $type ] = array();
			}
		}

		if ( ! $include_observer ) {
			$reports['observer'] = array();
		}

		return $reports;
	}

	public static function get_delivery_threshold() {
		return apply_filters( 'oss_woocommerce_delivery_threshold', 10000 );
	}

	public static function get_delivery_notification_threshold() {
		return apply_filters( 'oss_woocommerce_delivery_notification_threshold', self::get_delivery_threshold() * 0.95 );
	}

	public static function get_delivery_threshold_left() {
		$net_total = 0;

		if ( $observer_report = self::get_observer_report() ) {
			$net_total = $observer_report->get_net_total();
		}

		$total_left = self::get_delivery_threshold() - $net_total;

		if ( $total_left <= 0 ) {
			$total_left = 0;
		}

		return $total_left;
	}

	/**
	 * @param null $year
	 *
	 * @return false|Report
	 */
	public static function get_completed_observer_report( $year = null ) {
		$observer_report = self::get_observer_report( $year );

		if ( ! $observer_report || 'completed' !== $observer_report->get_status() ) {
			return false;
		}

		return $observer_report;
	}

	/**
	 * @param null $year
	 *
	 * @return false|Report
	 */
	public static function get_observer_report( $year = null ) {
		if ( is_null( $year ) ) {
			$year = date( 'Y' );
		}

		$report_id = get_option( 'oss_woocommerce_observer_report_' . $year );
		$report    = false;

		if ( ! empty( $report_id ) ) {
			$report = Package::get_report( $report_id );
		}

		return $report;
	}

	public static function observer_report_is_outdated() {
		$is_outdated = true;

		if ( $observer = self::get_observer_report() ) {
			$date_end = $observer->get_date_end();
			$now      = new \WC_DateTime();

			$diff = $now->diff( $date_end );

			if ( $diff->days <= 1 ) {
				$is_outdated = false;
			}
		}

		return $is_outdated;
	}

	public static function string_to_datetime( $time_string ) {
		if ( is_string( $time_string ) && ! is_numeric( $time_string ) ) {
			$time_string = strtotime( $time_string );
		}

		$date_time = $time_string;

		if ( is_numeric( $date_time ) ) {
			$date_time = new \WC_DateTime( "@{$date_time}", new \DateTimeZone( 'UTC' ) );
		}

		if ( ! is_a( $date_time, 'WC_DateTime' ) ) {
			return null;
		}

		return $date_time;
	}

	/**
	 * @param $id
	 *
	 * @return false|Report
	 */
	public static function get_report( $id ) {
		$report = new Report( $id );

		if ( $report->exists() ) {
			return $report;
		}

		return false;
	}

	public static function get_report_id( $parts ) {
		$parts = wp_parse_args( $parts, array(
			'type'       => 'daily',
			'date_start' => date( 'Y-m-d' ),
			'date_end'   => date( 'Y-m-d' ),
		) );

		if ( is_a( $parts['date_start'], 'WC_DateTime' ) ) {
			$parts['date_start'] = $parts['date_start']->format( 'Y-m-d' );
		}

		if ( is_a( $parts['date_end'], 'WC_DateTime' ) ) {
			$parts['date_end'] = $parts['date_end']->format( 'Y-m-d' );
		}

		return 'oss_' . $parts['type'] . '_report_' . $parts['date_start'] . '_' . $parts['date_end'];
	}

	public static function get_report_data( $id ) {
		$id_parts = explode( '_', $id );
		$data     = array(
			'id'         => $id,
			'type'       => $id_parts[1],
			'date_start' => self::string_to_datetime( $id_parts[3] ),
			'date_end'   => self::string_to_datetime( $id_parts[4] ),
		);

		return $data;
	}

	public static function get_report_title( $id ) {
		$args  = self::get_report_data( $id );
		$title = _x( 'Report', 'oss', 'woocommerce-germanized' );

		if ( 'quarterly' === $args['type'] ) {
			$date_start = $args['date_start'];
			$quarter    = 1;
			$month_num  = $date_start->date_i18n( 'n' );

			if ( 4 == $month_num ) {
				$quarter = 2;
			} elseif ( 7 == $month_num ) {
				$quarter = 3;
			} elseif ( 10 == $month_num ) {
				$quarter = 4;
			}

			$title = sprintf( _x( 'Q%1$s/%2$s', 'oss', 'woocommerce-germanized' ), $quarter, $date_start->date_i18n( 'Y' ) );
		} elseif( 'monthly' === $args['type'] ) {
			$date_start = $args['date_start'];
			$month_num  = $date_start->date_i18n( 'm' );

			$title = sprintf( _x( '%1$s/%2$s', 'oss', 'woocommerce-germanized' ), $month_num, $date_start->date_i18n( 'Y' ) );
		} elseif( 'yearly' === $args['type'] ) {
			$date_start = $args['date_start'];

			$title = sprintf( _x( '%1$s', 'oss', 'woocommerce-germanized' ), $date_start->date_i18n( 'Y' ) );
		} elseif( 'custom' === $args['type'] ) {
			$date_start = $args['date_start'];
			$date_end   = $args['date_end'];

			$title = sprintf( _x( '%1$s - %2$s', 'oss', 'woocommerce-germanized' ), $date_start->date_i18n( 'Y-m-d' ), $date_end->date_i18n( 'Y-m-d' ) );
		}  elseif( 'observer' === $args['type'] ) {
			$date_start = $args['date_start'];
			$date_end   = $args['date_end'];

			$title = sprintf( _x( 'Observer %1$s', 'oss', 'woocommerce-germanized' ), $date_start->date_i18n( 'Y' ) );
		}

		return $title;
	}

	/**
	 * @param Report $report
	 */
	public static function remove_report( $report ) {
		$reports_available = self::get_report_ids();

		if ( in_array( $report->get_id(), $reports_available[ $report->get_type() ] ) ) {
			$reports_available[ $report->get_type() ] = array_diff( $reports_available[  $report->get_type() ], array( $report->get_id() ) );

			update_option( 'oss_woocommerce_reports', $reports_available, false );

			/**
			 * Force non-cached option
			 */
			wp_cache_delete( 'oss_woocommerce_reports', 'options' );
		}
	}

	/**
	 * @param array $args
	 *
	 * @return Report[]
	 */
	public static function get_reports( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'type'             => '',
			'limit'            => -1,
			'offset'           => 0,
			'orderby'          => 'date_start',
			'include_observer' => false,
		) );

		$ids = self::get_report_ids( $args['include_observer'] );

		if ( ! empty( $args['type'] ) ) {
			$report_ids = array_key_exists( $args['type'], $ids ) ? $ids[ $args['type'] ] : array();
		} else {
			$report_ids = array_merge( ...array_values( $ids ) );
		}

		$reports_sorted = array();

		foreach( $report_ids as $id ) {
			$reports_sorted[] = self::get_report_data( $id );
		}

		if ( array_key_exists( $args['orderby'], array( 'date_start', 'date_end' ) ) ) {
			usort($reports_sorted, function( $a, $b ) use ( $args ) {
				if ( $a[ $args['orderby'] ] == $b[ $args['orderby'] ] ) {
					return 0;
				}

				return $a[ $args['orderby'] ] < $b[ $args['orderby'] ] ? -1 : 1;
			} );
		}

		if ( -1 !== $args['limit'] ) {
			$reports_sorted = array_slice( $reports_sorted, $args['offset'], $args['limit'] );
		}

		$reports = array();

		foreach( $reports_sorted as $data ) {
			if ( $report = Package::get_report( $data['id'] ) ) {
				$reports[] = $report;
			}
		}

		return $reports;
 	}

 	public static function clear_caches() {
		delete_transient( 'oss_reports_counts' );
		wp_cache_delete( 'oss_woocommerce_reports', 'options' );
    }

 	public static function get_report_counts() {
	    $types     = array_keys( Package::get_available_report_types( true ) );
	    $cache_key = 'oss_reports_counts';
	    $counts    = get_transient( $cache_key );

	    if ( false === $counts ) {
		    $counts = array();

		    foreach( $types as $type ) {
			    $counts[ $type ] = 0;
		    }

		    foreach( self::get_reports( array( 'include_observer' => true ) ) as $report ) {
		    	if ( ! array_key_exists( $report->get_type(), $counts ) ) {
		    		continue;
			    }

			    $counts[ $report->get_type() ] += 1;
		    }

		    set_transient( $cache_key, $counts );
	    }

	    return (array) $counts;
    }

	public static function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			// @todo Remove when start supporting WP 5.0 or later.
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'woocommerce-germanized' );

		unload_textdomain( 'oss-woocommerce' );
		load_textdomain( 'oss-woocommerce', trailingslashit( WP_LANG_DIR ) . 'oss-woocommerce/oss-woocommerce-' . $locale . '.mo' );
		load_plugin_textdomain( 'oss-woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
	}

	public static function register_emails( $emails ) {
		$mails = array(
			'\Vendidero\OneStopShop\DeliveryThresholdEmailNotification'
		);

		foreach( $mails as $mail ) {
			$emails[ self::sanitize_email_class( $mail ) ] = new $mail();
		}

		return $emails;
	}

	protected static function sanitize_email_class( $class ) {
		return 'oss_woocommerce_' . sanitize_key( str_replace( __NAMESPACE__ . '\\', '', $class ) );
	}

	public static function observer_report_needs_notification() {
		$needs_notification = false;

		if ( $report = Package::get_observer_report() ) {
			$net_total = $report->get_net_total();
			$threshold = Package::get_delivery_notification_threshold();

			if ( $net_total >= $threshold ) {
				$needs_notification = true;
			}
		}

		return apply_filters( 'oss_woocommerce_observer_report_needs_notification', $needs_notification );
	}

	/**
	 * @param Report $observer_report
	 */
	public static function maybe_send_notification( $observer_report ) {
		if ( Package::observer_report_needs_notification() ) {
			if ( 'yes' !== get_option( 'oss_woocommerce_notification_sent_' . $observer_report->get_date_start()->format( 'Y' ) ) ) {
				$mails = WC()->mailer()->get_emails();
				$mail  = self::sanitize_email_class( '\Vendidero\OneStopShop\DeliveryThresholdEmailNotification' );

				if ( isset( $mails[ $mail ] ) ) {
					$mails[ $mail ]->trigger( $observer_report );
				}
			}
		}
	}

	/**
     * Let the observer date back 7 days to make sure most of the orders
     * have already been processed (e.g. received payment etc) to reduce the chance of missing out on orders.
     *
	 * @return int
	 */
	public static function get_observer_backdating_days() {
	    return 7;
	}

	public static function update_observer_report() {
		if ( Package::enable_auto_observer() ) {
			/**
			 * Delete observer reports with missing versions to make sure the report
             * is re-created with the new backdating functionality.
			 */
		    if ( $report = self::get_observer_report() ) {
		        if ( '' === $report->get_version() ) {
		            $report->delete();
		        }
 		    }

		    $days = (int) self::get_observer_backdating_days();

			$date_start = new \WC_DateTime();
			$date_start->modify( "-{$days} day" . ( $days > 1 ? 's' : '' ) );

			Queue::start( 'observer', $date_start );
		}
	}

	public static function setup_recurring_actions() {
		if ( $queue = Queue::get_queue() ) {

		    // Schedule once per day at 2:00
			if ( null === $queue->get_next( 'oss_woocommerce_daily_cleanup', array(), 'oss_woocommerce' ) ) {
				$timestamp = strtotime('tomorrow midnight' );
				$date      = new \WC_DateTime();

				$date->setTimestamp( $timestamp );
				$date->modify( '+2 hours' );

				$queue->cancel_all( 'oss_woocommerce_daily_cleanup', array(), 'oss_woocommerce' );
				$queue->schedule_recurring( $date->getTimestamp(), DAY_IN_SECONDS, 'oss_woocommerce_daily_cleanup', array(), 'oss_woocommerce' );
			}

			if ( Package::enable_auto_observer() ) {
			    // Schedule once per day at 3:00
				if ( null === $queue->get_next( 'oss_woocommerce_daily_observer', array(), 'oss_woocommerce' ) ) {
					$timestamp = strtotime('tomorrow midnight' );
					$date      = new \WC_DateTime();

					$date->setTimestamp( $timestamp );
					$date->modify( '+3 hours' );

					$queue->cancel_all( 'oss_woocommerce_daily_observer', array(), 'oss_woocommerce' );
					$queue->schedule_recurring( $date->getTimestamp(), DAY_IN_SECONDS, 'oss_woocommerce_daily_observer', array(), 'oss_woocommerce' );
				}
			} else {
				$queue->cancel( 'oss_woocommerce_daily_observer', array(), 'oss_woocommerce' );
			}
		}
	}

	public static function get_available_report_types( $include_observer = false ) {
		$types = array(
			'quarterly' => _x( 'Quarterly', 'oss', 'woocommerce-germanized' ),
			'yearly'    => _x( 'Yearly', 'oss', 'woocommerce-germanized' ),
			'monthly'   => _x( 'Monthly', 'oss', 'woocommerce-germanized' ),
			'custom'    => _x( 'Custom', 'oss', 'woocommerce-germanized' ),
		);

		if ( $include_observer ) {
			$types['observer'] = _x( 'Observer', 'oss', 'woocommerce-germanized' );
		}

		return $types;
	}

	public static function get_type_title( $type ) {
		$types = Package::get_available_report_types( true );

		return array_key_exists( $type, $types ) ? $types[ $type ] : '';
	}

	public static function get_report_statuses() {
		return array(
			'pending'   => _x( 'Pending', 'oss', 'woocommerce-germanized' ),
			'completed' => _x( 'Completed', 'oss', 'woocommerce-germanized' ),
			'failed'    => _x( 'Failed', 'oss', 'woocommerce-germanized' )
		);
	}

	public static function get_report_status_title( $status ) {
		$statuses = Package::get_report_statuses();

		return array_key_exists( $status, $statuses ) ? $statuses[ $status ] : '';
	}

	public static function has_dependencies() {
		return ( class_exists( 'WooCommerce' ) );
	}

    public static function get_base_country() {
        if ( WC()->countries ) {
            return WC()->countries->get_base_country();
        } else {
            return wc_get_base_location()['country'];
        }
    }

	/**
	 * Returns a list of EU countries except base country.
	 *
	 * @return string[]
	 */
	public static function get_non_base_eu_countries( $include_gb = false ) {
		$countries = WC()->countries->get_european_union_countries( 'eu_vat' );

		/**
		 * Include GB to allow Northern Ireland
		 */
		if ( $include_gb && ! in_array( 'GB', $countries ) ) {
			$countries = array_merge( $countries, array( 'GB' ) );
		}

		$base_country = Package::get_base_country();
		$countries    = array_diff( $countries, array( $base_country ) );

		return $countries;
	}

	public static function country_supports_eu_vat( $country, $postcode = '' ) {
	    $supports_vat = in_array( $country, self::get_non_base_eu_countries() );
	    $exemptions   = Tax::get_vat_postcode_exemptions_by_country( $country );
		$postcode     = wc_normalize_postcode( $postcode );
		$wildcards    = wc_get_wildcard_postcodes( $postcode, $country );

		if ( 'GB' === $country && in_array( 'BT*', $wildcards ) ) {
			$supports_vat = true;
		} elseif( 'IX' === $country ) {
		    $supports_vat = true;
		}

		/**
		 * Check whether the country + postcode is a VAT exemption.
		 */
		if ( ! empty( $exemptions ) ) {
			foreach( $exemptions as $exempt_postcode ) {
				if ( in_array( $exempt_postcode, $wildcards, true ) ) {
					$supports_vat = false;
					break;
				}
			}
		}

		return $supports_vat;
	}

	public static function install() {
        self::init();
        Install::install();
	}

	public static function deactivate() {
		if ( self::has_dependencies() && Admin::supports_wc_admin() ) {
			foreach( Admin::get_notes() as $oss_note ) {
			    Admin::delete_wc_admin_note( $oss_note );
			}
		}
	}

	public static function install_integration() {
		self::install();
	}

	public static function is_integration() {
	    $gzd_installed = class_exists( 'WooCommerce_Germanized' );
	    $gzd_version   = get_option( 'woocommerce_gzd_version', '1.0' );

		return $gzd_installed && version_compare( $gzd_version, '3.5.0', '>=' ) ? true : false;
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url() {
		return plugins_url( '', __DIR__ );
	}

	public static function get_assets_url() {
		return self::get_url() . '/assets';
	}

	private static function define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	public static function log( $message, $type = 'info' ) {
		$logger = wc_get_logger();

		if ( ! $logger || ! apply_filters( 'oss_woocommerce_enable_logging', true ) ) {
			return;
		}

		if ( ! is_callable( array( $logger, $type ) ) ) {
			$type = 'info';
		}

		$logger->{$type}( $message, array( 'source' => 'one-stop-shop-woocommerce' ) );
	}

	public static function extended_log( $message, $type = 'info' ) {
		if ( apply_filters( 'oss_woocommerce_enable_extended_logging', ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) ) {
			self::log( $message, $type );
		}
	}
}