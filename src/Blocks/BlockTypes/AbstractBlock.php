<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

use Vendidero\Germanized\Blocks\Assets;
use Vendidero\Germanized\Package;
use WP_Block;

/**
 * AbstractBlock class.
 */
abstract class AbstractBlock {

	/**
	 * @var Assets
	 */
	protected $assets = null;

	/**
	 * Block namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'woocommerce-germanized';

	/**
	 * Block name within this namespace.
	 *
	 * @var string
	 */
	protected $block_name = '';

	/**
	 * Tracks if assets have been enqueued.
	 *
	 * @var boolean
	 */
	protected $enqueued_assets = false;

	/**
	 * Constructor.
	 *
	 * @param Assets $assets
	 * @param string $block_name Optionally set block name during construct.
	 */
	public function __construct( $assets, $block_name = '' ) {
		$this->assets     = $assets;
		$this->block_name = $block_name ? $block_name : $this->block_name;
		$this->initialize();
	}

	/**
	 * The default render_callback for all blocks. This will ensure assets are enqueued just in time, then render
	 * the block (if applicable).
	 *
	 * @param array|WP_Block $attributes Block attributes, or an instance of a WP_Block. Defaults to an empty array.
	 * @param string         $content    Block content. Default empty string.
	 * @param WP_Block|null  $block      Block instance.
	 * @return string Rendered block type output.
	 */
	public function render_callback( $attributes = array(), $content = '', $block = null ) {
		$render_callback_attributes = $this->parse_render_callback_attributes( $attributes );

		if ( ! is_admin() && ! WC()->is_rest_api_request() ) {
			$this->enqueue_assets( $render_callback_attributes, $content, $block );
		}

		return $this->render( $render_callback_attributes, $content, $block );
	}

	/**
	 * Enqueue assets used for rendering the block in editor context.
	 *
	 * This is needed if a block is not yet within the post content--`render` and `enqueue_assets` may not have ran.
	 */
	public function enqueue_editor_assets() {
		if ( $this->enqueued_assets ) {
			return;
		}
		$this->enqueue_data();
	}

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		$this->register_block_type_assets();
		$this->register_block_type();
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Register script and style assets for the block type before it is registered.
	 *
	 * This registers the scripts; it does not enqueue them.
	 */
	protected function register_block_type_assets() {
		if ( null !== $this->get_block_type_editor_script() ) {
			$data     = $this->assets->get_script_data( $this->get_block_type_editor_script( 'path' ) );
			$has_i18n = in_array( 'wp-i18n', $data['dependencies'], true );

			$this->assets->register_script(
				$this->get_block_type_editor_script( 'handle' ),
				$this->get_block_type_editor_script( 'path' ),
				$this->get_block_type_editor_script( 'dependencies' ),
				$has_i18n
			);
		}
		if ( null !== $this->get_block_type_script() ) {
			$data     = $this->assets->get_script_data( $this->get_block_type_script( 'path' ) );
			$has_i18n = in_array( 'wp-i18n', $data['dependencies'], true );

			$this->assets->register_script(
				$this->get_block_type_script( 'handle' ),
				$this->get_block_type_script( 'path' ),
				$this->get_block_type_script( 'dependencies' ),
				$has_i18n
			);
		}
	}

	/**
	 * Injects Chunk Translations into the page so translations work for lazy loaded components.
	 *
	 * The chunk names are defined when creating lazy loaded components using webpackChunkName.
	 *
	 * @param string[] $chunks Array of chunk names.
	 */
	protected function register_chunk_translations( $chunks ) {
		foreach ( $chunks as $chunk ) {
			$handle = 'wc-gzd-blocks-' . $chunk . '-chunk';
			$this->assets->register_script( $handle, $this->assets->get_block_asset_build_path( $chunk ), array(), true );
			wp_add_inline_script(
				$this->get_block_type_script( 'handle' ),
				wp_scripts()->print_translations( $handle, false ),
				'before'
			);
			wp_deregister_script( $handle );
		}
	}

	/**
	 * Generate an array of chunks paths for loading translation.
	 *
	 * @param string $chunks_folder The folder to iterate over.
	 * @return string[] $chunks list of chunks to load.
	 */
	protected function get_chunks_paths( $chunks_folder ) {
		$build_path = Package::get_path( 'build/' );
		$blocks     = array();
		if ( ! is_dir( $build_path . $chunks_folder ) ) {
			return array();
		}
		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $build_path . $chunks_folder ) ) as $block_name ) {
			$blocks[] = str_replace( $build_path, '', $block_name );
		}

		$chunks = preg_filter( '/.js/', '', $blocks );
		return $chunks;
	}
	/**
	 * Registers the block type with WordPress.
	 *
	 * @return string[] Chunks paths.
	 */
	protected function register_block_type() {
		$block_settings = array(
			'render_callback' => $this->get_block_type_render_callback(),
			'editor_script'   => $this->get_block_type_editor_script( 'handle' ),
			'editor_style'    => $this->get_block_type_editor_style(),
			'style'           => $this->get_block_type_style(),
		);

		if ( isset( $this->api_version ) && '2' === $this->api_version ) {
			$block_settings['api_version'] = 2;
		}

		$metadata_path = $this->assets->get_block_metadata_path( $this->block_name );

		/**
		 * We always want to load block styles separately, for every theme.
		 * When the core assets are loaded separately, other blocks' styles get
		 * enqueued separately too. Thus we only need to handle the remaining
		 * case.
		 */
		if (
			! is_admin() &&
			! wc_current_theme_is_fse_theme() &&
			$block_settings['style'] &&
			(
				! function_exists( 'wp_should_load_separate_core_block_assets' ) ||
				! wp_should_load_separate_core_block_assets()
			)
		) {
			$style_handles           = $block_settings['style'];
			$block_settings['style'] = null;
			add_filter(
				'render_block',
				function ( $html, $block ) use ( $style_handles ) {
					if ( $block['blockName'] === $this->get_block_type() ) {
						array_map( 'wp_enqueue_style', $style_handles );
					}
					return $html;
				},
				10,
				2
			);
		}

		// Prefer to register with metadata if the path is set in the block's class.
		if ( ! empty( $metadata_path ) ) {
			register_block_type_from_metadata(
				$metadata_path,
				$block_settings
			);
			return;
		}

		/*
		 * Insert attributes and supports if we're not registering the block using metadata.
		 * These are left unset until now and only added here because if they were set when registering with metadata,
		 * the attributes and supports from $block_settings would override the values from metadata.
		 */
		$block_settings['attributes']   = $this->get_block_type_attributes();
		$block_settings['supports']     = $this->get_block_type_supports();
		$block_settings['uses_context'] = $this->get_block_type_uses_context();

		register_block_type(
			$this->get_block_type(),
			$block_settings
		);
	}

	/**
	 * Get the block type.
	 *
	 * @return string
	 */
	protected function get_block_type() {
		return $this->namespace . '/' . $this->block_name;
	}

	/**
	 * Get the render callback for this block type.
	 *
	 * Dynamic blocks should return a callback, for example, `return [ $this, 'render' ];`
	 *
	 * @see $this->register_block_type()
	 * @return callable|null;
	 */
	protected function get_block_type_render_callback() {
		return array( $this, 'render_callback' );
	}

	/**
	 * Get the editor script data for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = array(
			'handle'       => 'wc-gzd-' . $this->block_name . '-block',
			'path'         => $this->assets->get_block_asset_build_path( $this->block_name ),
			'dependencies' => array( 'wc-blocks', 'wc-gzd-blocks' ),
		);
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get the editor style handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @return string|null
	 */
	protected function get_block_type_editor_style() {
		return 'wc-gzd-blocks-editor-style';
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		$script = array(
			'handle'       => 'wc-gzd-' . $this->block_name . '-block-frontend',
			'path'         => $this->assets->get_block_asset_build_path( $this->block_name . '-frontend' ),
			'dependencies' => array(),
		);
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]|null
	 */
	protected function get_block_type_style() {
		$this->assets->register_style( 'wc-gzd-blocks-style-' . $this->block_name, $this->assets->get_block_asset_build_path( $this->block_name, 'css' ), array(), 'all', true );

		return array( 'wc-blocks-style', 'wc-gzd-blocks-style', 'wc-gzd-blocks-style-' . $this->block_name );
	}

	/**
	 * Get the supports array for this block type.
	 *
	 * @see $this->register_block_type()
	 * @return string;
	 */
	protected function get_block_type_supports() {
		return array();
	}

	/**
	 * Get block attributes.
	 *
	 * @return array;
	 */
	protected function get_block_type_attributes() {
		return array();
	}

	/**
	 * Get block usesContext.
	 *
	 * @return array;
	 */
	protected function get_block_type_uses_context() {
		return array();
	}

	/**
	 * Parses block attributes from the render_callback.
	 *
	 * @param array|WP_Block $attributes Block attributes, or an instance of a WP_Block. Defaults to an empty array.
	 * @return array
	 */
	protected function parse_render_callback_attributes( $attributes ) {
		return is_a( $attributes, 'WP_Block' ) ? $attributes->attributes : $attributes;
	}

	/**
	 * Render the block. Extended by children.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		return $content;
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @internal This prevents the block script being enqueued on all pages. It is only enqueued as needed. Note that
	 * we intentionally do not pass 'script' to register_block_type.
	 *
	 * @param array    $attributes  Any attributes that currently are available from the block.
	 * @param string   $content    The block content.
	 * @param WP_Block $block    The block object.
	 */
	protected function enqueue_assets( array $attributes, $content, $block ) {
		if ( $this->enqueued_assets ) {
			return;
		}
		$this->enqueue_data( $attributes );
		$this->enqueue_scripts( $attributes );
		$this->enqueued_assets = true;
	}

	/**
	 * Data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = array() ) {
		if ( ! $this->assets->data_exists( 'wcBlocksConfig' ) ) {
			$this->assets->register_data(
				'wcGzdBlocksConfig',
				array(
					'pluginUrl' => Package::get_url(),
				)
			);
		}
	}

	/**
	 * Register/enqueue scripts used for this block on the frontend, during render.
	 *
	 * @param array $attributes Any attributes that currently are available from the block.
	 */
	protected function enqueue_scripts( array $attributes = array() ) {
		if ( null !== $this->get_block_type_script() ) {
			wp_enqueue_script( $this->get_block_type_script( 'handle' ) );
		}
	}
}
