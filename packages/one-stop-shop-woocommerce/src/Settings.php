<?php

namespace Vendidero\OneStopShop;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Settings {

    public static function get_sections() {
        return array(
            '' => _x( 'General', 'oss', 'woocommerce-oss' )
        );
    }

    public static function get_description() {
        return sprintf( _x( 'Find useful options regarding the <a href="https://ec.europa.eu/taxation_customs/business/vat/oss_en" target="_blank" rel="noopener">One Stop Shop procedure</a> here.', 'oss', 'woocommerce-germanized' ) );
    }

    public static function get_help_url() {
        return '';
    }

	public static function get_settings( $current_section = '' ) {
		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'oss_options', 'desc' => Package::is_integration() ? '' : self::get_description() ),

			array(
				'title'    => _x( 'OSS status', 'oss', 'woocommerce-germanized' ),
				'desc'     => _x( 'Yes, I\'m currently participating in the OSS procedure.', 'oss', 'woocommerce-germanized' ),
				'id'       => 'oss_use_oss_procedure',
				'type'     => Package::is_integration() ? 'gzd_toggle' : 'checkbox',
				'default'  => 'no',
			),

			array(
				'title'    => _x( 'Observation', 'oss', 'woocommerce-germanized' ),
				'desc'     => _x( 'Automatically observe the delivery threshold of the current year.', 'oss', 'woocommerce-germanized' ) . '<p class="oss-woocommerce-additional-desc wc-gzd-additional-desc">' . _x( 'This option will automatically calculate the amount applicable for the OSS procedure delivery threshold once per day for the current year. The report will only recalculated for the days which are not yet subject to the observation to save processing time.', 'oss', 'woocommerce-germanized' ) . '</p>',
				'id'       => 'oss_enable_auto_observation',
				'type'     => Package::is_integration() ? 'gzd_toggle' : 'checkbox',
				'default'  => 'yes',
			)
		);

		if ( Package::enable_auto_observer() ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => sprintf( _x( 'Delivery threshold', 'oss', 'woocommerce-germanized' ) ),
					'id'       => 'oss_delivery_threshold',
					'type'     => 'html',
					'html'     => self::get_observer_report_html(),
				),
			) );
        }

		$settings = array_merge( $settings, array(
			array(
				'title'    => _x( 'Participation', 'oss', 'woocommerce-germanized' ),
				'id'       => 'oss_switch',
				'type'     => 'html',
				'html'     => self::get_oss_switch_html(),
			),

			array( 'type' => 'sectionend', 'id' => 'oss_options' ),
        ) );

		return $settings;
	}

	public static function get_oss_switch_link() {
		return add_query_arg( array( 'action' => 'oss_switch_procedure' ), wp_nonce_url( admin_url( 'admin-post.php' ), 'oss_switch_procedure' ) );
	}

	protected static function get_oss_switch_html() {
		ob_start();
		?>
		<p>
			<a class="button button-secondary" onclick="return confirm('<?php echo esc_html( _x( 'Are you sure? Please backup your tax rates before proceeding.', 'oss', 'woocommerce-germanized' ) ); ?>');" href="<?php echo self::get_oss_switch_link(); ?>"><?php echo ( Package::oss_procedure_is_enabled() ? _x( 'End OSS participation', 'oss', 'woocommerce-germanized' ) : _x( 'Start OSS participation', 'oss', 'woocommerce-germanized' ) ); ?></a>
			<a class="oss-settings-learn-more" href="https://vendidero.github.io/one-stop-shop-woocommerce/tax-adjustments"><?php _ex( 'learn more', 'oss', 'woocommerce-germanized' ); ?></a>
		</p>
			<p class="oss-woocommerce-additional-desc wc-gzd-additional-desc"><?php _ex( 'Use this option to automatically adjust tax-related options in WooCommerce. Warning: This option will delete your current tax rates and add new tax rates based on your OSS participation status.', 'oss', 'woocommerce-germanized' ); ?></p>
		<?php

		return ob_get_clean();
	}

	public static function before_save() {
		/**
		 * In case observer is switched on and the current report is outdated - queue the observer report now.
		 */
        if ( ! Package::enable_auto_observer() && isset( $_POST['oss_enable_auto_observation'] ) && Package::observer_report_is_outdated() ) {
            update_option( 'oss_enable_auto_observation', 'yes' );
            Package::update_observer_report();
        }

        if ( Package::oss_procedure_is_enabled() && ( ! isset( $_POST['oss_use_oss_procedure'] ) || 'no' === wc_bool_to_string( $_POST['oss_use_oss_procedure'] ) ) ) {
            do_action( 'woocommerce_oss_disabled_oss_procedure' );
        } elseif ( ! Package::oss_procedure_is_enabled() && isset( $_POST['oss_use_oss_procedure'] ) && 'yes' === wc_bool_to_string( $_POST['oss_use_oss_procedure'] ) ) {
	        do_action( 'woocommerce_oss_enabled_oss_procedure' );
        }
	}

	public static function after_save() {

	}

	protected static function get_observer_report_html() {
		$observer_report = Package::get_completed_observer_report();

		if ( ! $observer_report || Queue::get_running_observer() ) {
		    $running = Package::get_observer_report() ? Package::get_observer_report() : Queue::get_running_observer();

		    $status_link = $running ? '<a href="' . $running->get_url() . '">' . _x( 'See status', 'oss', 'woocommerce-germanized' ) . '</a>' : '<a href="' . add_query_arg( array( 'action' => 'oss_init_observer' ), wp_nonce_url( admin_url( 'admin-post.php' ), 'oss_init_observer' ) ) . '">' . _x( 'Start initial report', 'oss', 'woocommerce-germanized' ) . '</a>';
			$status_text = sprintf( ( $running ? _x( 'Report not yet completed. %s', 'oss', 'woocommerce-germanized' ) : _x( 'Report not yet started. %s', 'oss', 'woocommerce-germanized' ) ), $status_link );
		    ob_start();
			?>
            <p class="oss-observer-details"><?php echo $status_text; ?></p>
            <?php
			return ob_get_clean();
		}

		$total_class = 'observer-total-green';

		if ( $observer_report->get_net_total() >= Package::get_delivery_threshold() ) {
			$total_class = 'observer-total-red';
		} elseif ( $observer_report->get_net_total() >= Package::get_delivery_notification_threshold() ) {
			$total_class = 'observer-total-orange';
		}

		ob_start();
		?>
			<p class="oss-observer-details"><span class="oss-observer-total <?php echo esc_attr( $total_class ); ?>"><?php echo wc_price( $observer_report->get_net_total() ); ?></span> <?php _ex( 'of', 'oss-amounts', 'woocommerce-germanized' ); ?> <span class="oss-observer-delivery-threshold"><?php echo wc_price( Package::get_delivery_threshold() ); ?></span> <a class="oss-settings-learn-more" href="<?php echo esc_url( $observer_report->get_url() ); ?>"><?php _ex( 'see details', 'oss', 'woocommerce-germanized' ); ?></a></p>
            <p class="oss-woocommerce-additional-desc wc-gzd-additional-desc"><?php printf( _x( 'This value indicates your current net total amount applicable for the One Stop Shop procedure delivery threshold of the current year. You should take action in case the delivery threshold is or is close to being exceeded. <a href="%s">Find out more</a> about the calculation.', 'oss', 'woocommerce-germanized' ), 'https://vendidero.github.io/one-stop-shop-woocommerce/report-calculation' ); ?></p>
		<?php

		return ob_get_clean();
	}

	public static function get_settings_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=oss' );
	}
}
