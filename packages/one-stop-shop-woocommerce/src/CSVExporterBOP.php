<?php

namespace Vendidero\OneStopShop;

use Vendidero\EUTaxHelper\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * BOP export
 *
 * @see https://www.elster.de/bportal/helpGlobal?themaGlobal=osseust%5Fimport#beispielCSV
 */
class CSVExporterBOP extends CSVExporter {

	/**
	 * Return an array of columns to export.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	public function get_default_column_names() {
		return apply_filters(
			'one_stop_shop_woocommerce_bop_export_default_columns',
			array(
				'bop_type'     => 'Satzart',
				'country'      => 'Land des Verbrauchs',
				'tax_type'     => 'Umsatzsteuertyp',
				'tax_rate'     => 'Umsatzsteuersatz',
				'taxable_base' => 'Steuerbemessungsgrundlage, Nettobetrag',
				'amount'       => 'Umsatzsteuerbetrag',
			)
		);
	}

	protected function get_column_value_bop_type( $country, $tax_rate ) {
		return apply_filters( 'one_stop_shop_woocommerce_bop_export_type', 3 );
	}

	protected function get_column_value_tax_type( $country, $tax_rate ) {
		$tax_type        = Helper::get_tax_type_by_country_rate( $tax_rate, $country );
		$tax_return_type = 'STANDARD';

		switch ( $tax_type ) {
			case 'reduced':
			case 'greater-reduced':
			case 'super-reduced':
				$tax_return_type = 'REDUCED';
				break;
			default:
				$tax_return_type = strtoupper( $tax_type );
				break;
		}

		return $tax_return_type;
	}

	protected function format_country( $country ) {
		$country = parent::format_country( $country );

		if ( 'GR' === $country ) {
			$country = 'EL';
		}

		return $country;
	}

	/**
	 * Prepare data that will be exported.
	 */
	public function prepare_data_to_export() {
		$countries = $this->report->get_countries();

		if ( ! empty( $countries ) ) {
			foreach ( $countries as $country ) {
				$tax_rates = $this->report->get_tax_rates_by_country( $country );

				if ( ! empty( $tax_rates ) ) {
					$this->row_data[] = apply_filters(
						'one_stop_shop_woocommerce_export_bop_country_header_data',
						array(
							'country'  => $this->format_country( $country ),
							'bop_type' => 1,
						),
						$country,
						$this
					);

					foreach ( $tax_rates as $tax_rate ) {
						$this->row_data[] = apply_filters( 'one_stop_shop_woocommerce_bop_export_row_data', $this->get_row_data( $country, $tax_rate ), $country, $tax_rate, $this );
					}
				}
			}
		}
	}

	/**
	 * Do the export. Prevent Woo from prepending a BOM.
	 */
	public function export() {
		$this->prepare_data_to_export();
		$this->send_headers();

		$csv_data = $this->export_column_headers() . $this->get_csv_data();

		// Replace newlines with Windows-style.
		$csv_data = preg_replace( '~\R~u', "\r", $csv_data );

		$this->send_content( $csv_data );
		die();
	}

	protected function export_column_headers() {
		$buffer = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		ob_start();
		fwrite( $buffer, '#v1.1' . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		$content = ob_get_clean();

		return $content;
	}

	protected function fputcsv( $buffer, $export_row ) {
		fputcsv( $buffer, $export_row, $this->get_delimiter(), "'", "\0" ); // @codingStandardsIgnoreLine
	}
}
