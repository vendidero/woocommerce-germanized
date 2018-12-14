<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Trusted Shops implementation. This Class manages review collection if enabled.
 *
 * @class   WC_GZD_Trusted_Shops
 * @version  1.0.0
 * @author   Vendidero
 */
class WC_GZD_Trusted_Shops {

	/**
	 * TS ID
	 * @var mixed
	 */
	public $id;
	
	/**
	 * Trusted Shops URL Parameters 
	 * @var array
	 */
	public $et_params = array();

	/**
	 * Trusted Shops Signup URL Parameters
	 * @var array
	 */
	public $signup_params = array();

	/**
	 * Class Prefix for auto dependency loading
	 * @var string
	 */
	public $prefix = '';

	/**
	 * Option prefix for DB strings
	 * @var string
	 */
	public $option_prefix = '';

	/**
	 * Support functionality
	 * @var array
	 */
	public $supports = array();

	/**
	 * Admin signup url
	 * @var string
	 */
	public $signup_url = '';

	/**
	 * Parent Plugin
	 * @var null
	 */
	public $plugin = null;

	/**
	 * Trusted Shops Version
	 * @var string
	 */
	public $version = '1.1.0';

	/**
	 * API URL for review collection
	 * @var string
	 */
	public $api_url;

	public $path = '';

	public $options = array();

	/**
	 * Sets Trusted Shops payment gateways and load dependencies
	 */
	public function __construct( $plugin, $params = array() ) {
		$this->plugin = $plugin;

        $args = wp_parse_args( $params, array(
            'et_params' 	=> array(),
            'signup_params' => array(),
            'prefix' 		=> '',
            'signup_url' 	=> '',
            'supports' 		=> array( 'reminder' ),
            'path'          => dirname( __FILE__ ) . '/'
        ) );

        foreach ( $args as $arg => $val ) {
            $this->$arg = $val;
        }

        $this->option_prefix = strtolower( $this->prefix );

        // Setup after compatibilities e.g. multi-language-support was loaded
        add_action( 'plugins_loaded', array( $this, 'load' ), 10 );
	}

	public function load() {
        // Refresh TS ID + API URL
        $this->refresh();
        $this->duplicate_plugin_check();
        $this->includes();

        add_action( 'init', array( $this, 'refresh' ), 50 );

        if ( is_admin() ) {
            $this->get_dependency( 'admin' );
        }

        $this->get_dependency( 'schedule' );
        $this->get_dependency( 'shortcodes' );
        $this->get_dependency( 'widgets' );
        $this->get_dependency( 'template_hooks' );

        if ( $this->is_enabled() ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_assets' ), 50 );

            if ( is_admin() ) {
                add_filter( 'woocommerce_gzd_wpml_translatable_options', array( $this, 'register_wpml_options' ), 20, 1 );
                add_filter( 'woocommerce_gzd_wpml_remove_translation_empty_equal', array( $this, 'stop_wpml_options_string_deletions' ), 20, 4 );
            }
        }
    }

	public function duplicate_plugin_check() {
		if ( function_exists( 'wc_ts_get_crud_data' ) ) {
			add_action( 'admin_notices', array( $this, 'show_duplicate_plugin_notice' ), 10 );
		}
	}

	public function show_duplicate_plugin_notice() {
		include_once( $this->path . 'admin/views/html-duplicate-plugin-notice.php' );
	}

	public function register_wpml_options( $settings ) {
		$admin = $this->get_dependency( 'admin' );

		return array_merge( $settings, $admin->get_translatable_settings() );
	}

    /**
     * Make sure that other languages are not synced with main language e.g. option does not default to main language
     *
     * @param $allow
     * @param $option
     * @param $new_value
     * @param $old_value
     * @return bool
     */
	public function stop_wpml_options_string_deletions( $allow, $option, $new_value, $old_value ) {
        $admin = $this->get_dependency( 'admin' );

        if ( array_key_exists( $option, $admin->get_translatable_settings() ) ) {
            $allow = false;
        }

	    return $allow;
    }

	public function includes() {
		include_once( $this->path . 'wc-gzd-ts-core-functions.php' );
	}

	public function load_frontend_assets() {
		$suffix        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path   = $this->plugin->plugin_url() . '/assets/css';
		$script_prefix = str_replace( '_', '-', $this->option_prefix );

		wp_register_style( 'woocommerce-' . $script_prefix . 'trusted-shops', $assets_path . '/woocommerce-' . $script_prefix . 'trusted-shops' . $suffix . '.css', false, $this->plugin->version );
		wp_enqueue_style( 'woocommerce-' . $script_prefix . 'trusted-shops' );
	}

	public function get_dependency_name( $name ) {
		$classname = 'WC_' . $this->prefix . 'Trusted_Shops_' . ucwords( str_replace( '-', '_', strtolower( $name ) ) );
		return $classname;
	}

	public function get_dependency( $name ) {
		$classname = $this->get_dependency_name( $name );
		return call_user_func_array( array( $classname, 'instance' ), array( $this ) );
	}

	public function refresh() {
		$this->id      = $this->__get( 'id' );
		$this->api_url = 'http://api.trustedshops.com/rest/public/v2/shops/'. $this->id .'/quality.json';
	}

	public function get_multi_language_compatibility() {
	    return apply_filters( 'woocommerce_trusted_shops_multi_language_compatibility', $this->plugin->get_compatibility( 'wpml-string-translation' ) );
    }

	public function is_multi_language_setup() {
	    $compatibility = $this->get_multi_language_compatibility();

	    return $compatibility->is_activated() ? true : false;
    }

	/**
	 * Get Trusted Shops Options
	 *
	 * @param string  $key
	 * @return mixed
	 */
	public function __get( $key ) {
	    $option_name = 'woocommerce_' . $this->option_prefix . 'trusted_shops_' . $key;
		$value       = get_option( $option_name );

        /**
         * By default WPML does not allow empty strings to override default translations.
         * This snippet manually checks for translations and allows to override default WPML translations.
         */
		if ( ! is_admin() && $this->is_multi_language_setup() ) {

		    $compatibility = $this->get_multi_language_compatibility();

		    $default_language = $compatibility->get_default_language();
		    $current_language = $compatibility->get_current_language();

		    if ( $current_language !== $default_language ) {
                if ( isset( $this->options[ $current_language ][ $key ] ) ) {
                    return $this->options[ $current_language ][ $key ];
                } else {
                    if ( $string_id = $compatibility->get_string_id( $option_name ) ) {
                        $translation = $compatibility->get_string_translation( $string_id, $current_language );

                        if ( false !== $translation ) {
                            $this->options[ $current_language ][ $key ] = $translation;
                            $value = $translation;
                        }
                    }
                }
            }
        }

        return $value;
	}

	/**
	 * Checks whether a certain Trusted Shops Option isset
	 *
	 * @param string  $key
	 * @return boolean
	 */
	public function __isset( $key ) {
		return ( ! get_option( 'woocommerce_' . $this->option_prefix . 'trusted_shops_' . $key ) ) ? false : true;
	}

	/**
	 * Checks whether Trusted Shops is enabled
	 *
	 * @return boolean
	 */
	public function is_enabled() {
		return ( $this->id ) ? true : false;
	}

	public function get_rich_snippets_locations() {
		$locations = array();

		if ( $this->rich_snippets_category === 'yes' ) {
			$locations[] = 'category';
		}

		if ( $this->rich_snippets_product === 'yes' ) {
			$locations[] = 'product';
		}

		if ( $this->rich_snippets_home === 'yes' ) {
			$locations[] = 'home';
		}

		return $locations;
	}

	public function is_trustbadge_enabled() {
		return ( $this->trustbadge_enable === 'yes' && $this->id !== '' ? true : false );
	}

	/**
	 * Checks whether Trusted Shops Rich Snippets are enabled
	 * 
	 * @return boolean
	 */
	public function is_rich_snippets_enabled() {
		return ( $this->rich_snippets_enable === 'yes' && $this->is_enabled() ? true : false );
	}

	/**
	 * Checks whether review widget is enabled
	 *  
	 * @return boolean
	 */
	public function is_review_widget_enabled() {
		return ( $this->review_widget_enable === 'yes' && $this->is_enabled() ? true : false );
	}

	public function is_review_reminder_enabled() {
		return ( $this->review_reminder_enable === 'yes' && $this->supports( 'reminder' ) && $this->is_enabled() ? true : false );
	}

	public function is_review_reminder_checkbox_enabled() {
		return ( $this->review_reminder_checkbox === 'yes' && $this->is_review_reminder_enabled() ? true : false );
	}

	public function is_product_reviews_enabled() {
		return ( $this->reviews_enable === 'yes' && $this->is_enabled() ? true : false );
	}

	public function is_product_sticker_enabled() {
		return ( $this->is_product_reviews_enabled() && $this->product_sticker_enable === 'yes' ? true : false );
	}

	public function is_review_sticker_enabled() {
		return ( $this->is_product_reviews_enabled() && $this->review_sticker_enable === 'yes' ? true : false );
	}

	public function is_product_widget_enabled() {
		return ( $this->is_product_reviews_enabled() && $this->product_widget_enable === 'yes' ? true : false );
	}

	public function supports( $type ) {
		return ( in_array( $type, $this->supports ) ? true : false );
	}

	/**
	 * Gets Trusted Shops payment gateway by woocommerce payment id
	 *
	 * @param integer $payment_method_id
	 * @return string
	 */
	public function get_payment_gateway( $payment_method_id ) {
		return 'wcOther';
	}

	/**
	 * Returns the average rating by grabbing the rating from the current languages' cache.
	 *
	 * @return array
	 */
	public function get_average_rating() {
	    $reviews = ( $this->reviews_cache ? $this->reviews_cache : array() );

	    if ( $this->is_multi_language_setup() ) {
	        $default_language = $this->get_multi_language_compatibility()->get_default_language();
            $current_language = $this->get_multi_language_compatibility()->get_current_language();

            if ( $current_language != $default_language ) {
                $reviews = ( $this->{"reviews_cache_{$current_language}"} ? $this->{"reviews_cache_{$current_language}"} : array() );
            }
        }

		return $reviews;
	}

	/**
	 * Returns the certificate link
	 *
	 * @return string
	 */
	public function get_certificate_link() {
		return 'https://www.trustedshops.com/shop/certificate.php?shop_id=' . $this->id;
	}

	/**
	 * Returns add new rating link
	 * 
	 * @return string
	 */
	public function get_new_review_link( $email, $order_id ) {
		return 'https://www.trustedshops.de/bewertung/bewerten_' . $this->id . '.html&buyerEmail=' . urlencode( base64_encode( $email ) ) . '&shopOrderID=' . urlencode( base64_encode( $order_id ) );
	}

	/**
	 * Returns the rating link
	 *
	 * @return string
	 */
	public function get_rating_link() {
		return 'https://www.trustedshops.de/bewertung/info_' . $this->id . '.html';
	}

	/**
	 * Gets the attachment id of review widget graphic
	 *  
	 * @return mixed
	 */
	public function get_review_widget_attachment() {
		return ( ! $this->review_widget_attachment ? false : $this->review_widget_attachment );
	}

	protected function get_product_shopping_data( $id, $attribute ) {
		$product = is_numeric( $id ) ? wc_get_product( $id ) : $id;

		if ( ! $product ) {
			return false;
		}

		$data    = wc_ts_get_crud_data( $product, $attribute );

		if ( 'variation' === $product->get_type() ) {
			if ( empty( $data ) ) {
				$parent = wc_get_product( wc_ts_get_crud_data( $product, 'parent' ) );
				$data   = wc_ts_get_crud_data( $parent, $attribute );
			}
		}

		return $data;
	}

	public function get_product_image( $id ) {
		$product = is_numeric( $id ) ? wc_get_product( $id ) : $id;

		if ( ! $product ) {
			return false;
		}

		$image = '';

		if ( is_callable( array( $product, 'get_image_id' ) ) ) {
			$image_id = $product->get_image_id();
			$images   = wp_get_attachment_image_src( $image_id, 'shop_single' );

			if ( ! empty( $images ) ) {
				$image = $images[0];
			}
		} else {
			if ( has_post_thumbnail( wc_ts_get_crud_data( $product, 'id' ) ) ) {
				$images = wp_get_attachment_image_src( get_post_thumbnail_id( wc_ts_get_crud_data( $product, 'id' ) ), 'shop_single' );

				if ( ! empty( $images ) ) {
					$image = $images[0];
				}
			}
		}

		return $image;
	}

	public function get_product_brand( $id ) {
		$product = is_numeric( $id ) ? wc_get_product( $id ) : $id;

		if ( ! $product ) {
			return false;
		}

		return $product->get_attribute( $this->brand_attribute );
	}

	public function get_product_mpn( $id ) {
		return $this->get_product_shopping_data( $id, '_ts_mpn' );
	}

	public function get_product_gtin( $id ) {
		return $this->get_product_shopping_data( $id, '_ts_gtin' );
	}

	public function get_product_skus( $id ) {
		$product = is_numeric( $id ) ? wc_get_product( $id ) : $id;
		$skus    = array();
		$skus[]  = ( $product->get_sku() ) ? $product->get_sku() : wc_ts_get_crud_data( $product, 'id' );

		if ( 'grouped' === $product->get_type() ) {
			foreach( $product->get_children() as $child ) {
				if ( $child_product = wc_get_product( $child ) ) {
					$skus[] = ( $child_product->get_sku() ) ? $child_product->get_sku() : wc_ts_get_crud_data( $child_product, 'id' );
				}
			}
		}

		return $skus;
	}

	public function get_template( $name ) {
		$html = "";

		ob_start();
		wc_get_template( 'trusted-shops/' . str_replace( '_', '-', $name ) . '-tpl.php', array( 'plugin' => $this ) );
		$html = ob_get_clean();

		return preg_replace('/^\h*\v+/m', '', strip_tags( $html ) );
	}

	public function get_script( $name, $replace = true, $args = array() ) {
		$script = $this->get_template( $name );

		if ( $this->integration_mode === 'expert' ) {
            $option_script = $this->{$name . "_code"};

            if ( $option_script ) {
                $script = $option_script;
            }
        }

		if ( $replace ) {
			$args = wp_parse_args( $args, array(
				'id'     => $this->id,
				'locale' => $this->get_locale(),
			) );

			foreach ( $args as $key => $arg ) {
				$search  = '{' . $key . '}';
				$replace = $arg;

				if ( is_array( $arg ) ) {
					$search = "'{" . $key . "}'";

					foreach( $arg as $k => $v ) {
						$arg[$k] = "'$v'";
					}

					$replace = implode( ',', $arg );
				}

				$script = str_replace( $search, $replace, $script );
			}
		}

		return $script;
	}

	public function get_selector_attribute( $type, $selector = '' ) {
		$element  = $this->get_selector_raw( $type, $selector );

		if ( substr( $element, 0, 1 ) === '.' ) {
			$element  = substr( $element, 1 );
		} elseif( substr( $element, 0, 1 ) === '#' ) {
			$element  = substr( $element, 1 );
		}

		return $element;
	}

	public function get_selector_raw( $type, $selector = '' ) {
		$element  = $this->{$type . "_selector"};

		if ( empty( $element ) ) {
			$element = "#ts_{$type}";
		}

		if ( ! empty( $selector ) ) {
			$element = $selector;
		}

		return $element;
	}

	public function get_selector( $type, $selector = '' ) {
		$element   = $this->get_selector_raw( $type, $selector );
		$attribute = $this->get_selector_attribute( $type, $selector );
		$is_class  = false;

		if ( substr( $element, 0, 1 ) === '.' ) {
			$is_class = true;
		}

		return $is_class ? 'class="' . esc_attr( $attribute ) . '"' : 'id="' . esc_attr( $attribute ) . '"';
	}

	public function get_product_sticker_code( $replace = true, $args = array() ) {
		if ( $replace ) {
			$args = wp_parse_args( $args, array(
				'element'      => $this->product_sticker_selector,
				'border_color' => $this->product_sticker_border_color,
				'star_color'   => $this->product_sticker_star_color,
				'star_size'    => $this->product_sticker_star_size,
			) );
		}

		return $this->get_script( 'product_sticker', $replace, $args );
	}

	public function get_review_sticker_code( $replace = true, $args = array() ) {
		if ( $replace ) {
			$args = wp_parse_args( $args, array(
				'element'      => '#ts_review_sticker',
				'bg_color'     => $this->review_sticker_bg_color,
				'font'         => $this->review_sticker_font,
				'number'       => $this->review_sticker_number,
				'better_than'  => $this->review_sticker_better_than
			) );
		}

		return $this->get_script( 'review_sticker', $replace, $args );
	}

	public function get_product_widget_code( $replace = true, $args = array() ) {
		if ( $replace ) {

			$args = wp_parse_args( $args, array(
				'element'    => $this->product_widget_selector,
				'star_color' => $this->product_widget_star_color,
				'star_size'  => $this->product_widget_star_size,
				'font_size'  => $this->product_widget_font_size,
			) );

		}

		return $this->get_script( 'product_widget', $replace, $args );
	}

	public function get_trustbadge_code( $replace = true, $args = array() ) {
		if ( $replace ) {
			$args = wp_parse_args( $args, array(
				'offset'  => $this->trustbadge_y,
				'variant' => $this->trustbadge_variant === 'standard' ? 'reviews' : 'default',
				'disable' => $this->is_trustbadge_enabled() ? 'false' : 'true',
			) );
		}

		return $this->get_script( 'trustbadge', $replace, $args );
	}

	public function get_rich_snippets_code( $replace = true, $args = array() ) {
		if ( $replace ) {
			$rating = $this->get_average_rating();

			$args = apply_filters( 'woocommerce_trusted_shops_rich_snippets_args', wp_parse_args( $args, array(
				'average'     => $rating['avg'],
				'count'       => $rating['count'],
				'maximum'     => $rating['max'],
				'rating'      => $rating,
				'name'        => get_bloginfo( 'name' ),
			) ), $this );
		}

		return $this->get_script( 'rich_snippets', $replace, $args );
	}

	public function get_supported_languages() {
		return array_keys( $this->get_locale_mapping() );
	}

	protected function get_locale_mapping() {
		$supported = array(
			'de' => 'de_DE',
			'en' => 'en_GB',
			'fr' => 'fr_FR',
		);

		return $supported;
	}

	public function get_language() {
		$locale = $this->get_locale();

		return substr( $locale, 0, 2 );
	}

	public function get_locale() {
		$supported = $this->get_locale_mapping();

		$locale = 'en_GB';
		$base   = substr( function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(), 0, 2 );

		if ( isset( $supported[ $base ] ) )
			$locale = $supported[ $base ];

		return $locale;
	}
}

?>
