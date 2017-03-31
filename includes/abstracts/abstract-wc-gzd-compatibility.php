<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * The 
 *
 * @class WC_GZD_Compatibility
 * @version  1.0.0
 * @author   Vendidero
 */
abstract class WC_GZD_Compatibility {

	private $plugin_name;
	private $plugin_file;
	private $version_data = array();

	public function __construct( $plugin_name, $plugin_file, $version_data = array() ) {
			
		$version_data = wp_parse_args( $version_data, array(
			'version' => '1.0.0',
			'requires_at_least' => '',
			'tested_up_to' => '',
		) );

		if ( empty( $version_data[ 'requires_at_least' ] ) && empty( $version_data[ 'tested_up_to' ] ) ) {
			$version_data[ 'requires_at_least' ] = $version_data[ 'version' ];
			$version_data[ 'tested_up_to' ] = $version_data[ 'version' ];
		} elseif ( empty( $version_data[ 'tested_up_to' ] ) ) {
			$version_data[ 'tested_up_to' ] = $version_data[ 'requires_at_least' ];
			if ( wc_gzd_get_dependencies()->compare_versions( $version_data[ 'version' ], $version_data[ 'requires_at_least' ], '>' ) )
				$version_data[ 'tested_up_to' ] = $version_data[ 'version' ];
		} elseif ( empty( $version_data[ 'requires_at_least' ] ) ) {
			$version_data[ 'requires_at_least' ] = $version_data[ 'tested_up_to' ];
			if ( wc_gzd_get_dependencies()->compare_versions( $version_data[ 'version' ], $version_data[ 'requires_at_least' ], '<' ) )
				$version_data[ 'requires_at_least' ] = $version_data[ 'version' ];
		}

		$this->version_data = $version_data;

		$this->plugin_name = $plugin_name;
		$this->plugin_file = $plugin_file;

		if ( ! $this->is_applicable() )
			return;

		$this->load();
	}

	public function is_applicable() {
		return $this->is_activated() && $this->is_supported();
	}

	public function is_activated() {
		return wc_gzd_get_dependencies()->is_plugin_activated( $this->plugin_file );
	}

	public function is_supported() {
		return
			wc_gzd_get_dependencies()->compare_versions( $this->version_data[ 'version' ], $this->version_data[ 'requires_at_least' ], '>=' ) &&
			wc_gzd_get_dependencies()->compare_versions( $this->version_data[ 'version' ], $this->version_data[ 'tested_up_to' ], '<=' );
	}

	public function get_name() {
		return $this->plugin_name;
	}

	public function get_version_data() {
		return $this->version_data;
	}

	abstract function load();

}
