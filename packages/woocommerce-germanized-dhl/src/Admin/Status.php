<?php

namespace Vendidero\Germanized\DHL\Admin;

use Vendidero\Germanized\DHL\Package;

defined( 'ABSPATH' ) || exit;

class Status {

	public static function init() {
		add_filter( 'woocommerce_admin_status_tabs', array( __CLASS__, 'register_tab' ), 10 );
		add_action( 'woocommerce_admin_status_content_dhl', array( __CLASS__, 'render' ) );
	}

	public static function render() {
		?>
		<table class="wc_status_table widefat" cellspacing="0" id="status">
			<thead>
				<tr>
					<th colspan="3" data-export-label="Post & DHL Ping status" style="">
						<h2><?php echo esc_html_x( 'Ping Check', 'dhl', 'woocommerce-germanized' ); ?></h2>
					</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ( self::get_urls_to_ping() as $url => $response_code ) :
				$result = self::test_url( $url, $response_code );
				?>
				<tr>
					<td style="width: 50%" data-export-label="<?php echo esc_attr( $url ); ?>"><code style=""><?php echo esc_url( $url ); ?></code></td>
					<td>
						<?php
						if ( $result ) {
							echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
						} else {
							echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html_x( 'Unable to connect to the URL. Please make sure that your webhost allows outgoing connections to that specific URL.', 'dhl', 'woocommerce-germanized' ) . '</mark>';
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	public static function register_tab( $tabs ) {
		$tabs['dhl'] = _x( 'DHL & Post', 'dhl', 'woocommerce-germanized' );

		return $tabs;
	}

	protected static function test_url( $url, $response_code = 200 ) {
		$transient_name    = 'woocommerce_gzd_dhl_test_remote_get_' . $url;
		$get_response_code = get_transient( $transient_name );

		if ( false === $get_response_code || is_wp_error( $get_response_code ) ) {
			$response = wp_remote_get( esc_url_raw( $url ) );

			if ( ! is_wp_error( $response ) ) {
				$get_response_code = $response['response']['code'];
			}

			set_transient( $transient_name, $get_response_code, HOUR_IN_SECONDS );
		}

		$get_response_successful = ! is_wp_error( $get_response_code ) && absint( $get_response_code ) === $response_code;

		return $get_response_successful;
	}

	public static function get_urls_to_ping() {
		$urls = array();

		if ( Package::is_dhl_enabled() ) {
			$urls = array_merge(
				$urls,
				array(
					Package::get_rest_url() => 401,
					Package::get_cig_url()  => 401,
				)
			);
		}

		if ( Package::is_deutsche_post_enabled() ) {
			$urls = array_merge(
				$urls,
				array(
					Package::get_internetmarke_main_url() => 200,
					Package::get_internetmarke_refund_url() => 200,
					Package::get_internetmarke_products_url() => 200,
				)
			);
		}

		return $urls;
	}
}
