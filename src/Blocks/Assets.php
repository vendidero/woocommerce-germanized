<?php
namespace Vendidero\Germanized\Blocks;

use Vendidero\Germanized\Package;

final class Assets {

	/**
	 * Contains registered data.
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Contains registered data.
	 *
	 * @var array
	 */
	private $lazy_data = array();

	public function __construct() {
		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( is_admin() ? 'admin_print_footer_scripts' : 'wp_print_footer_scripts', array( $this, 'enqueue_asset_data' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ), 1000 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_styles' ), 500 );
	}

	public function enqueue_block_styles() {
		if ( wp_style_is( 'wc-gzd-blocks-editor-style' ) ) {
			$custom_css = '
                .wc-gzd-nutri-score-value-a {
                    background: url(' . esc_url( Package::get_url( '/assets/images/nutri-score-a.svg' ) ) . ') no-repeat;
                }
                .wc-gzd-nutri-score-value-b {
                    background: url(' . esc_url( Package::get_url( '/assets/images/nutri-score-b.svg' ) ) . ') no-repeat;
                }
                .wc-gzd-nutri-score-value-c {
                    background: url(' . esc_url( Package::get_url( '/assets/images/nutri-score-c.svg' ) ) . ') no-repeat;
                }
                .wc-gzd-nutri-score-value-d {
                    background: url(' . esc_url( Package::get_url( '/assets/images/nutri-score-d.svg' ) ) . ') no-repeat;
                }
                .wc-gzd-nutri-score-value-e {
                    background: url(' . esc_url( Package::get_url( '/assets/images/nutri-score-e.svg' ) ) . ') no-repeat;
                }
            ';

			wp_add_inline_style( 'wc-gzd-blocks-editor-style', $custom_css );
		}
	}

	public function register_assets() {
		$this->register_script( 'wc-gzd-blocks', $this->get_block_asset_build_path( 'wc-gzd-blocks' ), array(), false );
		$this->register_script( 'wc-gzd-blocks-settings', $this->get_block_asset_build_path( 'wc-gzd-blocks-settings' ), array( 'wc-settings' ), false );

		$this->register_style( 'wc-gzd-blocks-style', $this->get_block_asset_build_path( 'wc-gzd-blocks', 'css' ), array(), 'all' );
		$this->register_style( 'wc-gzd-blocks-editor-style', $this->get_block_asset_build_path( 'wc-gzd-blocks-editor-style', 'css' ), array( 'wp-edit-blocks' ), 'all' );

		$this->register_data( 'isPro', Package::is_pro() );
	}

	public function enqueue_frontend_styles() {
		if ( wp_style_is( 'wc-blocks-style' ) ) {
			$custom_css = '
                .wc-gzd-nutri-score-value-a {
                    background: url(' . esc_url( Package::get_url( '/assets/images/nutri-score-a.svg' ) ) . ') no-repeat;
                }
                .wc-gzd-nutri-score-value-b {
                    background: url(' . esc_url( Package::get_url( '/assets/images/nutri-score-b.svg' ) ) . ') no-repeat;
                }
                .wc-gzd-nutri-score-value-c {
                    background: url(' . esc_url( Package::get_url( '/assets/images/nutri-score-c.svg' ) ) . ') no-repeat;
                }
                .wc-gzd-nutri-score-value-d {
                    background: url(' . esc_url( Package::get_url( '/assets/images/nutri-score-d.svg' ) ) . ') no-repeat;
                }
                .wc-gzd-nutri-score-value-e {
                    background: url(' . esc_url( Package::get_url( '/assets/images/nutri-score-e.svg' ) ) . ') no-repeat;
                }
            ';

			wp_add_inline_style( 'wc-gzd-blocks-style', $custom_css );
			wp_enqueue_style( 'wc-gzd-blocks-style' );
		}
	}

	public function data_exists( $key ) {
		return array_key_exists( $key, $this->data );
	}

	public function register_data( $key, $data ) {
		if ( ! $this->data_exists( $key ) ) {
			if ( \is_callable( $data ) ) {
				$this->lazy_data[ $key ] = $data;
			} else {
				$this->data[ $key ] = $data;
			}
		}
	}

	protected function execute_lazy_data() {
		foreach ( $this->lazy_data as $key => $callback ) {
			$this->data[ $key ] = $callback();
		}
	}

	public function enqueue_asset_data() {
		if ( wp_script_is( 'wc-gzd-blocks-settings', 'enqueued' ) ) {
			$this->execute_lazy_data();

			$data            = rawurlencode( wp_json_encode( $this->data ) );
			$settings_script = "var wcGzdBlocksSettings = wcGzdBlocksSettings || JSON.parse( decodeURIComponent( '" . esc_js( $data ) . "' ) );";

			wp_add_inline_script(
				'wc-gzd-blocks-settings',
				$settings_script,
				'before'
			);
		}
	}

	/**
	 * Get src, version and dependencies given a script relative src.
	 *
	 * @param string $relative_src Relative src to the script.
	 * @param array  $dependencies Optional. An array of registered script handles this script depends on. Default empty array.
	 *
	 * @return array src, version and dependencies of the script.
	 */
	public function get_script_data( $relative_src, $dependencies = array() ) {
		$src     = '';
		$version = '1';

		if ( $relative_src ) {
			$src        = $this->get_asset_url( $relative_src );
			$asset_path = Package::get_path( str_replace( '.js', '.asset.php', $relative_src ) );

			if ( file_exists( $asset_path ) ) {
				// The following require is safe because we are checking if the file exists and it is not a user input.
				// nosemgrep audit.php.lang.security.file.inclusion-arg.
				$asset        = require $asset_path;
				$dependencies = isset( $asset['dependencies'] ) ? array_merge( $asset['dependencies'], $dependencies ) : $dependencies;
				$version      = ! empty( $asset['version'] ) ? $asset['version'] : $this->get_file_version( $relative_src );
			} else {
				$version = $this->get_file_version( $relative_src );
			}
		}

		return array(
			'src'          => $src,
			'version'      => $version,
			'dependencies' => $dependencies,
		);
	}

	/**
	 * Retrieve the url to an asset for this plugin.
	 *
	 * @param string $relative_path An optional relative path appended to the
	 *                              returned url.
	 *
	 * @return string
	 */
	protected function get_asset_url( $relative_path = '' ) {
		return Package::get_url( $relative_path );
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file (relative to the plugin
	 *                     directory).
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( Package::get_path( $file ) ) ) {
			return filemtime( Package::get_path( trim( $file, '/' ) ) );
		}

		return Package::get_version();
	}

	/**
	 * Registers a script according to `wp_register_script`, adding the correct prefix, and additionally loading translations.
	 *
	 * When creating script assets, the following rules should be followed:
	 *   1. All asset handles should have a `wc-` prefix.
	 *   2. If the asset handle is for a Block (in editor context) use the `-block` suffix.
	 *   3. If the asset handle is for a Block (in frontend context) use the `-block-frontend` suffix.
	 *   4. If the asset is for any other script being consumed or enqueued by the blocks plugin, use the `wc-blocks-` prefix.
	 *
	 * @param string $handle        Unique name of the script.
	 * @param string $relative_src  Relative url for the script to the path from plugin root.
	 * @param array  $dependencies  Optional. An array of registered script handles this script depends on. Default empty array.
	 * @param bool   $has_i18n      Optional. Whether to add a script translation call to this file. Default: true.
	 */
	public function register_script( $handle, $relative_src, $dependencies = array(), $has_i18n = true ) {
		$script_data         = $this->get_script_data( $relative_src, $dependencies );
		$script_dependencies = $script_data['dependencies'];

		wp_register_script( $handle, $script_data['src'], $script_dependencies, $script_data['version'], true );

		if ( $has_i18n && function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $handle, 'woocommerce-germanized', Package::get_language_path() );
		}
	}

	/**
	 * Returns the appropriate asset path for current builds.
	 *
	 * @param   string $filename  Filename for asset path (without extension).
	 * @param   string $type      File type (.css or .js).
	 * @return  string             The generated path.
	 */
	public function get_block_asset_build_path( $filename, $type = 'js' ) {
		return "build/$filename.$type";
	}

	/**
	 * Get the path to a block's metadata
	 *
	 * @param string $block_name The block to get metadata for.
	 * @param string $path Optional. The path to the metadata file inside the 'build' folder.
	 *
	 * @return string|boolean False if metadata file is not found for the block.
	 */
	public function get_block_metadata_path( $block_name, $path = '' ) {
		$path_to_metadata_from_plugin_root = Package::get_path( 'build/' . $path . $block_name . '/block.json' );

		if ( ! file_exists( $path_to_metadata_from_plugin_root ) ) {
			return false;
		}

		return $path_to_metadata_from_plugin_root;
	}

	/**
	 * Registers a style according to `wp_register_style`.
	 *
	 * @since 2.5.0
	 * @since 2.6.0 Change src to be relative source.
	 *
	 * @param string  $handle       Name of the stylesheet. Should be unique.
	 * @param string  $relative_src Relative source of the stylesheet to the plugin path.
	 * @param array   $deps         Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string  $media        Optional. The media for which this stylesheet has been defined. Default 'all'. Accepts media types like
	 *                              'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 * @param boolean $rtl   Optional. Whether or not to register RTL styles.
	 */
	public function register_style( $handle, $relative_src, $deps = array(), $media = 'all', $rtl = false ) {
		$filename = str_replace( plugins_url( '/', __DIR__ ), '', $relative_src );
		$src      = $this->get_asset_url( $relative_src );
		$ver      = $this->get_file_version( $filename );
		wp_register_style( $handle, $src, $deps, $ver, $media );

		if ( $rtl ) {
			wp_style_add_data( $handle, 'rtl', 'replace' );
		}
	}
}
