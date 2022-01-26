<?php

namespace Vendidero\OneStopShop;

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
		return apply_filters( "one_stop_shop_woocommerce_bop_export_default_columns", array(
			'bop_type'     => 'Satzart',
			'country'      => 'Land des Verbrauchs',
			'tax_type'     => 'Umsatzsteuertyp',
			'tax_rate'     => 'Umsatzsteuersatz',
			'taxable_base' => 'Steuerbemessungsgrundlage, Nettobetrag',
			'amount'       => 'Umsatzsteuerbetrag',
		) );
	}

	protected function get_column_value_bop_type( $country, $tax_rate ) {
		return apply_filters( "one_stop_shop_woocommerce_bop_export_type", 3 );
	}

	protected function get_column_value_tax_type( $country, $tax_rate ) {
		$tax_type        = Tax::get_tax_type_by_country_rate( $tax_rate, $country );
		$tax_return_type = 'STANDARD';

		switch( $tax_type ) {
			case "reduced":
			case "greater-reduced":
			case "super-reduced":
				$tax_return_type = 'REDUCED';
				break;
			default:
				$tax_return_type = strtoupper( $tax_type );
				break;
		}

		return $tax_return_type;
	}

	protected function export_column_headers() {
		$buffer = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		ob_start();
		fwrite( $buffer, "#v1.0" . PHP_EOL );
		$content = ob_get_clean();

		return $content . parent::export_column_headers();
	}

	protected function fputcsv( $buffer, $export_row ) {
		fputcsv( $buffer, $export_row, $this->get_delimiter(), "'", "\0" ); // @codingStandardsIgnoreLine
	}
}