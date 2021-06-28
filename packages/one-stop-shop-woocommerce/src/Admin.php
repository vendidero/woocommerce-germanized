<?php

namespace Vendidero\OneStopShop;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Admin {

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles' ), 15 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ), 15 );

		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'register_settings' ) );

		add_action( 'load-woocommerce_page_oss-reports', array( __CLASS__, 'setup_table' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 25 );

		add_action( 'admin_post_oss_create_report', array( __CLASS__, 'create_report' ) );

		foreach( array( 'delete', 'refresh', 'cancel', 'export' ) as $action ) {
			add_action( 'admin_post_oss_' .  $action. '_report', array( __CLASS__, $action . '_report' ) );
        }

		add_action( 'admin_post_oss_switch_procedure', array( __CLASS__, 'switch_procedure' ) );
		add_action( 'admin_post_oss_init_observer', array( __CLASS__, 'init_observer' ) );

		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		add_action( 'admin_post_oss_hide_notice', array( __CLASS__, 'hide_notice' ) );

		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'add_table_view' ), 10 );

		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		add_filter( 'set_screen_option_woocommerce_page_wc_gzd_shipments_per_page', array( __CLASS__, 'set_screen_option' ), 10, 3 );

		if ( ! has_action( 'woocommerce_admin_field_html' ) ) {
			add_action( 'woocommerce_admin_field_html', array( __CLASS__, 'html_field' ), 10, 1 );
        }

		add_filter( 'woocommerce_debug_tools', array( __CLASS__, 'register_tax_rate_refresh_tool' ), 10, 1 );
	}

	public static function on_wc_admin_note_update( $note_id ) {
	    try {
		    if ( self::supports_wc_admin() ) {
			    $note = new Note( $note_id );

			    foreach( self::get_notes() as $oss_note ) {
				    $wc_admin_note_name = self::get_wc_admin_note_name( $oss_note::get_id() );

				    if ( $note->get_name() === $wc_admin_note_name ) {
					    /**
					     * Update notice hide in case note has been actioned (e.g. button click by user)
					     */
				        if ( Note::E_WC_ADMIN_NOTE_ACTIONED === $note->get_status() ) {
					        update_option( 'oss_hide_notice_' . sanitize_key( $oss_note::get_id() ), 'yes' );
                        }

					    break;
                    }
			    }
		    }
        } catch( \Exception $e ) {}
    }

	public static function register_tax_rate_refresh_tool( $tools ) {
	    $tools['refresh_oss_tax_rates'] = array(
			'name'   => _x( 'Refresh VAT rates (OSS)', 'oss', 'woocommerce-germanized' ),
			'button' => _x( 'Refresh VAT rates (OSS)', 'oss', 'woocommerce-germanized' ),
			'callback' => array( __CLASS__, 'refresh_vat_rates' ),
			'desc'   => sprintf(
				'<strong class="red">%1$s</strong> %2$s',
				_x( 'Note:', 'oss', 'woocommerce-germanized' ),
				sprintf( _x( 'This option will delete all of your current EU VAT rates and re-import them based on your current <a href="%s">OSS status</a>.', 'oss', 'woocommerce-germanized' ), Settings::get_settings_url() )
			),
		);

	    return $tools;
    }

    public static function refresh_vat_rates() {
	    if ( Package::oss_procedure_is_enabled() ) {
		    Tax::import_oss_tax_rates();
	    } else {
		    Tax::import_default_tax_rates();
	    }
    }

	public static function html_field( $value ) {
		?>
        <tr valign="top">
            <th class="forminp forminp-html" id="<?php echo esc_attr( $value['id'] ); ?>">
                <label><?php echo esc_attr( $value['title'] ); ?><?php echo( isset( $value['desc_tip'] ) && ! empty( $value['desc_tip'] ) ? wc_help_tip( $value['desc_tip'] ) : '' ); // WPCS: XSS ok. ?></label>
            </th>
            <td class="forminp"><?php echo $value['html']; ?></td>
        </tr>
		<?php
	}

	public static function add_table_view( $screen_ids ) {
		$screen_ids[] = 'woocommerce_page_oss-reports';

		return $screen_ids;
	}

	public static function set_screen_option( $new_value, $option, $value ) {
		if ( in_array( $option, array( 'woocommerce_page_oss_reports_per_page' ) ) ) {
			return absint( $value );
		}

		return $new_value;
	}

	public static function admin_notices() {
		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$supports_notes = self::supports_wc_admin();

		if ( ! $supports_notes || in_array( $screen_id, array( 'dashboard', 'plugins' ) ) ) {
			foreach( self::get_notes() as $note ) {
			    if ( $note::is_enabled() ) {
			        $note::render();
                }
            }
		}
    }

	/**
	 * @return AdminNote[]
	 */
    public static function get_notes() {
	    $notes = array( 'Vendidero\OneStopShop\DeliveryThresholdWarning' );

	    if ( ! Package::enable_auto_observer() ) {
	        $notes = array();
	    }

	    return $notes;
    }

    public static function supports_wc_admin() {
	    $supports_notes = class_exists( 'Automattic\WooCommerce\Admin\Notes\Note' );

	    try {
		    $data_store = \WC_Data_Store::load( 'admin-note' );
	    } catch( \Exception $e ) {
		    $supports_notes = false;
	    }

	    return $supports_notes;
    }

    protected static function get_wc_admin_note_name( $oss_note_id ) {
        return 'oss_' . $oss_note_id;
    }

    protected static function get_wc_admin_note( $oss_note_id ) {
	    $note_name  = self::get_wc_admin_note_name( $oss_note_id );
	    $data_store = \WC_Data_Store::load( 'admin-note' );
	    $note_ids   = $data_store->get_notes_with_name( $note_name );

	    if ( ! empty( $note_ids ) && ( $note = Notes::get_note( $note_ids[0] ) ) ) {
	        return $note;
	    }

	    return false;
    }

	public static function queue_wc_admin_notes() {
	    if ( self::supports_wc_admin() ) {
		    foreach( self::get_notes() as $oss_note ) {
			    $note = self::get_wc_admin_note( $oss_note::get_id() );

			    if ( ! $note && $oss_note::is_enabled() ) {
				    $note = new Note();
				    $note->set_title( $oss_note::get_title() );
				    $note->set_content( $oss_note::get_content() );
				    $note->set_content_data( (object) array() );
				    $note->set_type( 'update' );
				    $note->set_name( self::get_wc_admin_note_name( $oss_note::get_id() ) );
				    $note->set_source( 'oss-woocommerce' );
				    $note->set_status( Note::E_WC_ADMIN_NOTE_UNACTIONED );

				    foreach ( $oss_note::get_actions() as $action ) {
					    $note->add_action(
						    'oss_' . sanitize_key( $action['title'] ),
						    $action['title'],
						    $action['url'],
						    Note::E_WC_ADMIN_NOTE_ACTIONED,
						    $action['is_primary'] ? true : false
					    );
				    }

				    $note->save();
			    } elseif ( $oss_note::is_enabled() && $note ) {
                    $note->set_status( Note::E_WC_ADMIN_NOTE_UNACTIONED );
                    $note->save();
			    }
		    }
        }
    }

    public static function get_threshold_notice_content() {
	    return sprintf( _x( 'Seems like you have reached (or are close to reaching) the delivery threshold for the current year. Please make sure to check the <a href="%s" target="_blank">report details</a> and take action in case necessary.', 'oss', 'woocommerce-germanized' ), esc_url( Package::get_observer_report()->get_url() ) );
    }

	public static function get_threshold_notice_title() {
        return _x( 'Delivery threshold reached (OSS)', 'oss', 'woocommerce-germanized' );
	}

	public static function init_observer() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'oss_init_observer' ) ) {
			wp_die();
		}

		if ( ! Queue::get_running_observer() ) {
			Package::update_observer_report();
		}

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	public static function switch_procedure() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'oss_switch_procedure' ) ) {
			wp_die();
		}

		if ( Package::oss_procedure_is_enabled() ) {
			update_option( 'oss_use_oss_procedure', 'no' );

			Tax::import_default_tax_rates();

			do_action( 'woocommerce_oss_disabled_oss_procedure' );
		} else {
		    update_option( 'woocommerce_tax_based_on', 'shipping' );
            update_option( 'oss_use_oss_procedure', 'yes' );

            Tax::import_oss_tax_rates();

            do_action( 'woocommerce_oss_enabled_oss_procedure' );
		}

		do_action( 'woocommerce_oss_switched_oss_procedure_status' );

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	public static function hide_notice() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'oss_hide_notice' ) ) {
			wp_die();
		}

		$notice_id = isset( $_GET['notice'] ) ? wc_clean( $_GET['notice'] ) : '';

		foreach( self::get_notes() as $oss_note ) {
		    if ( $oss_note::get_id() == $notice_id ) {
		        update_option( 'oss_hide_notice_' . sanitize_key( $oss_note::get_id() ), 'yes' );

		        if ( self::supports_wc_admin() ) {
			        self::delete_wc_admin_note( $oss_note );
		        }

		        break;
		    }
        }

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * @param AdminNote $oss_note
	 */
	public static function delete_wc_admin_note( $oss_note ) {
	    if ( ! self::supports_wc_admin() ) {
	        return false;
	    }

	    try {
            if ( $note = self::get_wc_admin_note( $oss_note::get_id() ) ) {
                $note->delete( true );
                return true;
            }

		    return false;
	    } catch( \Exception $e ) {
	        return false;
	    }
	}

	public static function delete_report() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'oss_delete_report' ) ) {
			wp_die();
		}

		$report_id = isset( $_GET['report_id'] ) ? wc_clean( $_GET['report_id'] ) : '';

		if ( ! empty( $report_id ) && ( $report = Package::get_report( $report_id ) ) ) {
		    $report->delete();

		    $referer = self::get_clean_referer();

			/**
			 * Do not redirect deleted, refreshed reports back to report details page
			 */
			if ( strstr( $referer, '&report=' ) ) {
				$referer = admin_url( 'admin.php?page=oss-reports' );
			}

		    wp_safe_redirect( add_query_arg( array( 'report_deleted' => $report_id ), $referer ) );
		    exit();
        }

		wp_safe_redirect( wp_get_referer() );
		exit();
    }

    protected static function get_clean_referer() {
	    $referer = wp_get_referer();

	    return remove_query_arg( array( 'report_created', 'report_deleted', 'report_restarted', 'report_cancelled' ), $referer );
    }

	public static function export_report() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'oss_export_report' ) ) {
			wp_die();
		}

		$report_id = isset( $_GET['report_id'] ) ? wc_clean( $_GET['report_id'] ) : '';
        $decimals  = isset( $_GET['decimals'] ) ? absint( $_GET['decimals'] ) : wc_get_price_decimals();

		if ( ! empty( $report_id ) && ( $report = Package::get_report( $report_id ) ) ) {
			$csv = new CSVExporter( $report_id, $decimals );
			$csv->export();
		} else {
			wp_safe_redirect( wp_get_referer() );
			exit();
        }
	}

	public static function refresh_report() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'oss_refresh_report' ) ) {
			wp_die();
		}

		$report_id = isset( $_GET['report_id'] ) ? wc_clean( $_GET['report_id'] ) : '';

		if ( ! empty( $report_id ) && ( $report = Package::get_report( $report_id ) ) ) {
			Queue::start( $report->get_type(), $report->get_date_start(), $report->get_date_end() );

			wp_safe_redirect( add_query_arg( array( 'report_restarted' => $report_id ), self::get_clean_referer() ) );
			exit();
		}

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	public static function cancel_report() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'oss_cancel_report' ) ) {
			wp_die();
		}

		$report_id = isset( $_GET['report_id'] ) ? wc_clean( $_GET['report_id'] ) : '';

		if ( ! empty( $report_id ) && Queue::is_running( $report_id ) ) {
			Queue::cancel( $report_id );

			$referer = self::get_clean_referer();

			/**
			 * Do not redirect deleted, refreshed reports back to report details page
			 */
			if ( strstr( $referer, '&report=' ) ) {
				$referer = admin_url( 'admin.php?page=oss-reports' );
			}

			wp_safe_redirect( add_query_arg( array( 'report_cancelled' => $report_id ), $referer ) );
			exit();
		}

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	public static function create_report() {
	    if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '', 'oss_create_report' ) ) {
	        wp_die();
        }

	    $report_type = ! empty( $_POST['report_type'] ) ? wc_clean( $_POST['report_type'] ) : 'yearly';
	    $report_type = array_key_exists( $report_type, Package::get_available_report_types() ) ? $report_type : 'yearly';
	    $start_date  = null;
		$end_date    = null;

	    if ( 'quarterly' === $report_type ) {
	        $start_date = ! empty( $_POST['report_quarter'] ) ? wc_clean( $_POST['report_quarter'] ) : null;
        } elseif ( 'yearly' === $report_type ) {
			$start_date = ! empty( $_POST['report_year'] ) ? wc_clean( $_POST['report_year'] ) : null;
		} elseif ( 'monthly' === $report_type ) {
		    $start_date = ! empty( $_POST['report_month'] ) ? wc_clean( $_POST['report_month'] ) : null;
	    } elseif ( 'custom' === $report_type ) {
		    $start_date = ! empty( $_POST['date_start'] ) ? wc_clean( $_POST['date_start'] ) : null;
		    $end_date   = ! empty( $_POST['date_end'] ) ? wc_clean( $_POST['date_end'] ) : null;
	    }

	    if ( ! is_null( $start_date ) ) {
	        $start_date = Package::string_to_datetime( $start_date );
        }

		if ( ! is_null( $end_date ) ) {
			$end_date = Package::string_to_datetime( $end_date );
		}

	    $generator_id = Queue::start( $report_type, $start_date, $end_date );

		wp_safe_redirect( admin_url( 'admin.php?page=oss-reports&report_created=' . $generator_id ) );
		exit();
	}

	public static function add_menu() {
		add_submenu_page( 'woocommerce', _x( 'OSS', 'oss', 'woocommerce-germanized' ), _x( 'One Stop Shop', 'oss', 'woocommerce-germanized' ), 'manage_woocommerce', 'oss-reports', array( __CLASS__, 'render_report_page' ) );
	}

	protected static function render_create_report() {
	    $years   = array();
		$years[] = date( 'Y' );
		$years[] = date( 'Y', strtotime("-1 year" ) );

		$quarters_selectable = array();
		$years_selectable    = array();
		$months_selectable   = array();

		foreach( $years as $year ) {
			$start_day                      = date( 'Y-m-d', strtotime( $year . '-01-01' ) );
		    $years_selectable[ $start_day ] = $year;

		    for ( $i = 4; $i>=1; $i-- ) {
		        $start_month = ( $i - 1 ) * 3 + 1;
		        $start_day   = date( 'Y-m-d', strtotime( $year . '-' . $start_month . '-01' ) );

		        if ( date( 'Y-m-d' ) >= $start_day ) {
		            $quarters_selectable[ $start_day ] = sprintf( _x( 'Q%1$s/%2$s', 'oss', 'woocommerce-germanized' ), $i, $year );
                }
            }

			for ( $i = 12; $i>=1; $i-- ) {
				$start_day = date( 'Y-m-d', strtotime( $year . '-' . $i . '-01' ) );
				$month     = date( 'm', strtotime( $year . '-' . $i . '-01' ) );

				if ( date( 'Y-m-d' ) >= $start_day ) {
					$months_selectable[ $start_day ] = sprintf( _x( '%1$s/%2$s', 'oss', 'woocommerce-germanized' ), $month, $year );
				}
			}
        }
        ?>
        <div class="wrap oss-reports create-oss-reports">
            <form class="create-oss-report" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <header>
                    <h2><?php _ex( 'New Report', 'oss', 'woocommerce-germanized' ); ?></h2>
                </header>
                <section>
                    <table class="form-table oss-report-options">
                        <tbody>
                        <tr id="oss-report-type-wrapper">
                            <th scope="row">
                                <label for="oss-report-type"><?php echo esc_html_x( 'Type', 'oss', 'woocommerce-germanized' ); ?></label>
                            </th>
                            <td id="oss-report-type-data">
                                <select name="report_type" id="oss-report-type" class="wc-enhanced-select">
                                    <?php foreach( Package::get_available_report_types() as $type => $title ) : ?>
                                        <option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $title ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr id="oss-report-year-wrapper" class="oss-report-hidden oss-report-yearly">
                            <th scope="row">
                                <label for="oss-report-year"><?php echo esc_html_x( 'Year', 'storeabill-core', 'storeabill' ); ?></label>
                            </th>
                            <td id="oss-report-year-data">
                                <select name="report_year" id="oss-report-year" class="wc-enhanced-select">
			                        <?php foreach( $years_selectable as $value => $title ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $title ); ?></option>
			                        <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr id="oss-report-quarter-wrapper" class="oss-report-hidden oss-report-quarterly">
                            <th scope="row">
                                <label for="oss-report-quarter"><?php echo esc_html_x( 'Quarter', 'storeabill-core', 'storeabill' ); ?></label>
                            </th>
                            <td id="oss-report-quarter-data">
                                <select name="report_quarter" id="oss-report-quarter" class="wc-enhanced-select">
	                                <?php foreach( $quarters_selectable as $value => $title ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $title ); ?></option>
	                                <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr id="oss-report-month-wrapper" class="oss-report-hidden oss-report-monthly">
                            <th scope="row">
                                <label for="oss-report-month"><?php echo esc_html_x( 'Month', 'storeabill-core', 'storeabill' ); ?></label>
                            </th>
                            <td id="oss-report-month-data">
                                <select name="report_month" id="oss-report-month" class="wc-enhanced-select">
			                        <?php foreach( $months_selectable as $value => $title ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $title ); ?></option>
			                        <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr id="oss-report-timeframe-wrapper" class="oss-report-hidden oss-report-custom">
                            <th scope="row">
                                <label for="oss-report-date-start"><?php echo esc_html_x( 'Date range', 'storeabill-core', 'storeabill' ); ?></label>
                            </th>
                            <td id="oss-report-custom-data">
                                <input type="text" size="11" placeholder="yyyy-mm-dd" value="" id="oss-report-date-start" name="date_start" class="oss_range_datepicker from" autocomplete="off" /><?php //@codingStandardsIgnoreLine ?>
                                <span>&ndash;</span>
                                <input type="text" size="11" placeholder="yyyy-mm-dd" value="" name="date_end" class="oss_range_datepicker to" autocomplete="off" /><?php //@codingStandardsIgnoreLine ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </section>
                <div class="oss-actions">
                    <button type="submit" class="oss-new-report-button button button-primary" value="<?php echo esc_attr_x( 'Start report', 'oss', 'woocommerce-germanized' ); ?>"><?php echo esc_attr_x( 'Start report', 'oss', 'woocommerce-germanized' ); ?></button>
                </div>
                <?php wp_nonce_field( 'oss_create_report' ); ?>
                <input type="hidden" name="action" value="oss_create_report" />
            </form>
        </div>
        <?php
    }

	protected static function render_reports() {
		global $wp_list_table;
		?>
        <div class="wrap oss-reports">
            <h1 class="wp-heading-inline"><?php echo _x( 'One Stop Shop', 'oss', 'woocommerce-germanized' ); ?></h1>
            <a href="<?php echo add_query_arg( array( 'new' => 'yes' ), admin_url( 'admin.php?page=oss-reports' ) ); ?>" class="page-title-action"><?php _ex( 'New report', 'oss', 'woocommerce-germanized' ); ?></a>

            <hr class="wp-header-end" />

			<?php
			$wp_list_table->output_notices();
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'changed', 'deleted', 'trashed', 'untrashed' ), $_SERVER['REQUEST_URI'] );
			?>

			<?php $wp_list_table->views(); ?>

            <form id="posts-filter" method="get">
                <input type="hidden" name="page" value="oss-reports" />

				<?php $wp_list_table->display(); ?>
            </form>

            <div id="ajax-response"></div>
            <br class="clear" />
        </div>
		<?php
	}

	public static function render_actions( $actions ) {
		foreach ( $actions as $action_name => $action ) {
			if ( isset( $action['url'] ) ) {
				$target = isset( $action['target'] ) ? $action['target'] : '_self';

				printf( '<a class="button oss-woo-action-button oss-woo-action-button-%1$s %1$s" href="%2$s" aria-label="%3$s" title="%3$s" target="%4$s">%5$s</a>', esc_attr( $action_name ), esc_url( $action['url'] ), esc_attr( isset( $action['title'] ) ? $action['title'] : $action_name ), $target, esc_html( isset( $action['title'] ) ? $action['title'] : $action_name ) );
			}
		}
	}

	/**
	 * @param Report $report
	 *
	 * @return array[]
	 */
	public static function get_report_actions( $report ) {
		$actions = array(
			'view' => array(
				'url' => $report->get_url(),
				'title' => _x( 'View', 'oss', 'woocommerce-germanized' )
			),
			'export' => array(
				'url' => $report->get_export_link(),
				'title' => _x( 'Export', 'oss', 'woocommerce-germanized' )
			),
			'refresh' => array(
				'url' => $report->get_refresh_link(),
				'title' => _x( 'Refresh', 'oss', 'woocommerce-germanized' )
			),
			'delete' => array(
				'url' => $report->get_delete_link(),
				'title' => _x( 'Delete', 'oss', 'woocommerce-germanized' )
			),
		);

		if ( 'completed' !== $report->get_status() ) {
			$actions['cancel'] = $actions['delete'];
			$actions['cancel']['title'] = _x( 'Cancel', 'oss', 'woocommerce-germanized' );

			unset( $actions['view'] );
			unset( $actions['refresh'] );
			unset( $actions['delete'] );
			unset( $actions['export'] );
		}

		if ( 'observer' === $report->get_type() ) {
		    unset( $actions['refresh'] );
			unset( $actions['cancel'] );
		}

		return $actions;
	}

	public static function render_report_details() {
	    global $wp_list_table;

	    $report_id = wc_clean( $_GET['report'] );

	    if ( ! $report = Package::get_report( $report_id ) ) {
	        return;
	    }

	    $actions = self::get_report_actions( $report );
	    unset( $actions['view'] );

		$columns = array(
			'country'   => _x( 'Country', 'oss', 'woocommerce-germanized' ),
			'tax_rate'  => _x( 'Tax Rate', 'oss', 'woocommerce-germanized' ),
			'net_total' => _x( 'Net Total', 'oss', 'woocommerce-germanized' ),
			'tax_total' => _x( 'Tax Total', 'oss', 'woocommerce-germanized' ),
		);

		$countries = $report->get_countries();
	    ?>
        <div class="wrap oss-reports oss-report-<?php echo esc_attr( $report->get_id() ); ?>">
            <h1 class="wp-heading-inline"><?php echo $report->get_title(); ?></h1>

            <?php foreach( $actions as $action_type => $action ) : ?>
                <a class="page-title-action button-<?php echo esc_attr( $action_type ); ?>" href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['title'] ); ?></a>
            <?php endforeach; ?>

            <?php if ( 'completed' === $report->get_status() ) : ?>
                <p class="summary"><?php echo $report->get_date_start()->date_i18n( wc_date_format() ); ?> &ndash; <?php echo $report->get_date_end()->date_i18n( wc_date_format() ); ?>: <?php echo wc_price( $report->get_net_total() ); ?> (<?php echo wc_price( $report->get_tax_total() ); ?>)</p>
                <hr class="wp-header-end" />
                <?php if ( ! empty( $countries ) ) : ?>
                    <table class="wp-list-table widefat fixed striped posts oss-report-details" cellspacing="0">
                        <thead>
                        <tr>
                            <?php foreach ( $columns as $key => $column ) : ?>
                               <th class="oss-report-table-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $column ); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ( $countries as $country ) :
                            foreach( $report->get_tax_rates_by_country( $country ) as $tax_rate ) :
                            ?>
                            <tr>
                                <td class="oss-report-table-country"><?php echo esc_html( $country ); ?></td>
                                <td class="oss-report-table-tax_rate"><?php echo esc_html( sprintf( _x( '%1$s %%', 'oss', 'woocommerce-germanized' ), $tax_rate ) ); ?></td>
                                <td class="oss-report-table-net_total"><?php echo wc_price( $report->get_country_net_total( $country, $tax_rate ) ); ?></td>
                                <td class="oss-report-table-tax_total"><?php echo wc_price( $report->get_country_tax_total( $country, $tax_rate ) ); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php else :
	            $details = Queue::get_queue_details( $report_id );
                ?>
                <p class="summary"><?php printf( _x( 'Currently processed %1$s orders. Next iteration is scheduled for %2$s. <a href="%3$s">Find pending actions</a>', 'oss', 'woocommerce-germanized' ), $details['order_count'], $details['next_date'] ? $details['next_date']->date_i18n( wc_date_format() . ' @ ' . wc_time_format() ) : _x( 'Not yet known', 'oss', 'woocommerce-germanized' ), esc_url( $details['link'] ) ); ?></p>
            <?php endif; ?>
        </div>
        <?php
	}

	public static function render_report_page() {
		global $wp_list_table;

		if ( current_user_can( 'manage_woocommerce' ) ) {
			if ( isset( $_GET['new'] ) ) {
				self::render_create_report();
			} elseif ( isset( $_GET['report'] ) ) {
				self::render_report_details();
			} else {
				self::render_reports();
			}
		}
	}

	public static function setup_table() {
        global $wp_list_table;

        $wp_list_table = new ReportTable();
        $doaction      = $wp_list_table->current_action();

        if ( $doaction ) {
            /**
             * This nonce is dynamically constructed by WP_List_Table and uses
             * the normalized plural argument.
             */
            check_admin_referer( 'bulk-' . sanitize_key( _x( 'Reports', 'oss', 'woocommerce-germanized' ) ) );

            $pagenum       = $wp_list_table->get_pagenum();
            $parent_file   = $wp_list_table->get_main_page();
            $sendback      = remove_query_arg( array( 'deleted', 'ids', 'changed', 'bulk_action' ), wp_get_referer() );

            if ( ! $sendback ) {
                $sendback = admin_url( $parent_file );
            }

            $sendback   = add_query_arg( 'paged', $pagenum, $sendback );
            $report_ids = array();

            if ( isset( $_REQUEST['ids'] ) ) {
                $report_ids = explode( ',', wc_clean( $_REQUEST['ids'] ) );
            } elseif ( ! empty( $_REQUEST['report'] ) ) {
                $report_ids = wc_clean( $_REQUEST['report'] );
            }

            if ( ! empty( $report_ids ) ) {
                $sendback = $wp_list_table->handle_bulk_actions( $doaction, $report_ids, $sendback );
            }

            $sendback = remove_query_arg( array( 'action', 'action2', '_status', 'bulk_edit', 'report', 'report_created' ), $sendback );

            wp_redirect( $sendback );
            exit();
        } elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
            wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
            exit;
        }

        $wp_list_table->set_bulk_notice();
        $wp_list_table->prepare_items();

        add_screen_option( 'per_page' );
	}

	public static function register_settings( $settings ) {
	    if ( ! Package::is_integration() ) {
		    $settings[] = new SettingsPage();
	    }

	    return $settings;
	}

	public static function get_screen_ids() {
		$screen_ids = array( "woocommerce_page_wc-settings", "woocommerce_page_oss-reports", "product" );

		return $screen_ids;
	}

	public static function admin_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_style( 'oss_woo', Package::get_url() . '/assets/css/admin' . $suffix . '.css', array(), Package::get_version() );

		// Admin styles for WC pages only.
		if ( in_array( $screen_id, self::get_screen_ids() ) ) {
			wp_enqueue_style( 'oss_woo' );
		}
	}

	public static function admin_scripts() {
		global $post;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$deps      = array( 'jquery', 'woocommerce_admin' );

		if ( in_array( $screen_id, array( 'woocommerce_page_oss-reports' ) ) ) {
		    $deps[] = 'jquery-ui-datepicker';
		}

		wp_register_script( 'oss-admin', Package::get_assets_url() . '/js/admin' . $suffix . '.js', $deps, Package::get_version() );

		if ( in_array( $screen_id, self::get_screen_ids() ) ) {
			wp_enqueue_script( 'oss-admin' );

			wp_localize_script(
				'oss-admin',
				'oss_admin_params',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);
		}
	}
}
