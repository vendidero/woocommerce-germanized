<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZD_Dependencies {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public $loadable = true;

	public $plugin = null;

	public $prefix = 'gzd';
	
	public $plugins = array();

	public $wc_supports_crud = false;

	public $plugins_header = array(
		'woocommerce' => array( 
			'name' 					=> 'WooCommerce',
			'tested' 				=> '',
			'requires' 				=> '',
			'version' 				=> '',
			'version_prefix' 		=> 'woocommerce',
		),
	);

	public $plugins_result = array(
		'outdated' 		   => array(),
		'unactivated'	   => array(),
		'untested'		   => array(),
	);

	public static function instance( $plugin = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $plugin );
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}
	
	public function __construct( $plugin = null ) {

		if ( ! $plugin ) {
			$plugin = WC_germanized();
		}

		$this->plugin = $plugin;

		// Set whether current WooCommerce Version supports CRUD
		$this->set_wc_supports_crud();

		if ( $plugin->version != get_option( 'woocommerce_' . $this->prefix . '_version' ) ) {
			$this->delete_cached_plugin_header_data();
		}

		$this->plugins = (array) get_option( 'active_plugins', array() );
		
		if ( is_multisite() )
			$this->plugins = array_merge( $this->plugins, get_site_option( 'active_sitewide_plugins', array() ) );
	
		$this->parse_plugin_header_data();

		// Set Plugin versions
		foreach ( $this->plugins_header as $plugin => $data ) {
			$this->plugins_header[ $plugin ][ 'version' ] = $this->get_plugin_version( $data[ 'version_prefix' ] );
		}

		foreach ( $this->plugins_header as $plugin => $data ) {

			if ( ! $this->is_plugin_activated( $plugin ) ) {
				$this->plugins_result[ 'unactivated' ][ $plugin ] = $data;
			} elseif ( $this->is_plugin_outdated( $plugin ) ) {
				$this->plugins_result[ 'outdated' ][ $plugin ] = $data;
			} elseif ( ! $this->is_plugin_tested( $plugin ) ) {
				$this->plugins_result[ 'untested' ][ $plugin ] = $data;
			}

		}

		if ( ! empty( $this->plugins_result[ 'unactivated' ] ) || ! empty( $this->plugins_result[ 'outdated' ] ) ) {
			$this->loadable = false;
			remove_all_actions( 'admin_notices' );
			add_action( 'admin_notices', array( $this, 'dependencies_notice' ) );
		} elseif ( ! empty( $this->plugins_result[ 'untested' ] ) ) {
			remove_all_actions( 'admin_notices' );
			add_action( 'admin_notices', array( $this, 'dependencies_notice' ) );
		}

	}

	public function set_wc_supports_crud() {
        $this->wc_supports_crud = ( $this->compare_versions( $this->get_plugin_version( 'woocommerce' ), '2.7', '>=' ) );
    }

    public function delete_cached_plugin_header_data() {
		delete_option( 'woocommerce_' . $this->prefix . '_plugin_header_data' );
    }

	protected function get_current_plugin_path() {
		return $this->plugin->plugin_path() . '/woocommerce-germanized.php';
	}

	protected function parse_plugin_header_data() {

		$plugin_header_data = get_option( 'woocommerce_' . $this->prefix . '_plugin_header_data', array() );

		if ( ! empty( $plugin_header_data ) ) {
			$this->plugins_header = $plugin_header_data;
			return;
		}

		$plugin_header_check = array();

		foreach ( $this->plugins_header as $plugin => $data ) {

			$plugin_header_check[ 'requires_' . $plugin ] = 'Requires at least ' . $data[ 'name' ];
			$plugin_header_check[ 'tested_' . $plugin ] = 'Tested up to ' . $data[ 'name' ];

		}

		if ( ! empty( $plugin_header_check ) ) {

			$plugin_data = get_file_data( $this->get_current_plugin_path(), $plugin_header_check );

			foreach ( $plugin_data as $key => $value ) {
				if ( strpos( $key, 'requires' ) !== false ) {
					$this->plugins_header[ str_replace( 'requires_', '', $key ) ][ 'requires' ] = $value;
				} elseif ( strpos( $key, 'tested' ) !== false ) {
					$this->plugins_header[ str_replace( 'tested_', '', $key ) ][ 'tested' ] = $value;
				}
			}
		}

		update_option( 'woocommerce_' . $this->prefix . '_plugin_header_data', $this->plugins_header );
	}

	public function get_plugin_version( $plugin_slug ) {
		$version = preg_replace( '#(\.0+)+($|-)#', '', get_option( $plugin_slug . '_version', '1.0' ) );
		return $version;
	}

	public function is_plugin_outdated( $plugin ) {
		
		$plugin_data = ( isset( $this->plugins_header[ $plugin ] ) ? $this->plugins_header[ $plugin ] : false );
		
		if ( ! $plugin_data || ! isset( $plugin_data[ 'requires' ] ) || empty( $plugin_data[ 'requires' ] ) )
			return false;
		
		if ( $this->compare_versions( $plugin_data[ 'requires' ], $this->get_plugin_version( $plugin_data[ 'version_prefix' ] ), ">" ) )
			return true;

		return false;
	}

	public function get_plugin_path( $plugin ) {

		if ( strpos( $plugin, '.php' ) === false ) {
			$plugin = trailingslashit( $plugin ) . $plugin . '.php';
		}

		return $plugin;
	}

	public function is_plugin_activated( $plugin ) {
		
		if ( isset( $this->plugins_header[ $plugin ][ 'constant' ] ) ) {
			
			if ( ! defined( $this->plugins_header[ $plugin ][ 'constant' ] ) )
				return false;
		}

		if ( strpos( $plugin, '.php' ) === false ) {
			$plugin = trailingslashit( $plugin ) . $plugin . '.php';
		}
		
		return ( in_array( $plugin, $this->plugins ) || array_key_exists( $plugin, $this->plugins ) );
	}

	public function is_plugin_tested( $plugin ) {

		$plugin_data = ( isset( $this->plugins_header[ $plugin ] ) ? $this->plugins_header[ $plugin ] : false );

		if ( ! $plugin_data || ! isset( $plugin_data[ 'tested' ] ) || empty( $plugin_data[ 'tested' ] ) )
			return true;

		if ( $this->compare_versions( $plugin_data[ 'tested' ], $this->get_plugin_version( $plugin_data[ 'version_prefix' ] ), ">=" ) )
			return true;

		return false;

	}

	/**
	 * This method removes accuration from $ver2 if this version is more accurate than $main_ver
	 */
	public function compare_versions( $main_ver, $ver2, $operator ) {

		$expl_main_ver = explode( '.', $main_ver );
		$expl_ver2 = explode( '.', $ver2 );

		// Check if ver2 string is more accurate than main_ver
		if ( sizeof( $expl_main_ver ) == 2 && sizeof( $expl_ver2 ) > 2 ) {
			$new_ver_2 = array_slice( $expl_ver2, 0, 2 );
			$ver2 = implode( '.', $new_ver_2 );
		}

		return version_compare( $main_ver, $ver2, $operator );
	}

	public function woocommerce_version_supports_crud() {
		return $this->wc_supports_crud;
	}

	/**
	 * Checks if WooCommerce is activated
	 *  
	 * @return boolean true if WooCommerce is activated
	 */
	public function is_woocommerce_activated() {
		return $this->is_plugin_activated( 'woocommerce/woocommerce.php' );
	}

	public function is_loadable() {
		return $this->loadable;
	}

	public function dependencies_notice() {
		global $dependencies;
		$dependencies = $this;

		include_once( 'admin/views/html-notice-dependencies.php' );
	}

}
