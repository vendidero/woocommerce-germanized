<?php
namespace Vendidero\Shiptastic\ShippingMethod;

use Vendidero\Shiptastic\Admin\Admin;
use Vendidero\Shiptastic\Admin\PackagingSettings;
use Vendidero\Shiptastic\Admin\Settings;
use Vendidero\Shiptastic\Interfaces\ShippingProvider;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\Packing\Helper;
use Vendidero\Shiptastic\Packing\PackagingList;

defined( 'ABSPATH' ) || exit;

class ShippingMethod extends \WC_Shipping_Method {

	protected $shipping_provider = null;

	protected $zone = null;

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping method instance ID.
	 * @param ShippingProvider|null $shipping_provider
	 */
	public function __construct( $instance_id = 0, $shipping_provider = null ) {
		if ( is_null( $shipping_provider ) ) {
			if ( ! empty( $instance_id ) ) {
				$raw_method = \WC_Data_Store::load( 'shipping-zone' )->get_method( $instance_id );

				if ( ! empty( $raw_method ) ) {
					$method_id               = str_replace( 'shipping_provider_', '', $raw_method->method_id );
					$this->shipping_provider = wc_stc_get_shipping_provider( $method_id );
				}
			}
		} else {
			$this->shipping_provider = is_a( $shipping_provider, 'Vendidero\Shiptastic\Interfaces\ShippingProvider' ) ? $shipping_provider : wc_stc_get_shipping_provider( $shipping_provider );
		}

		if ( ! is_a( $this->shipping_provider, 'Vendidero\Shiptastic\Interfaces\ShippingProvider' ) ) {
			return;
		}

		$this->id                 = 'shipping_provider_' . $this->shipping_provider->get_name();
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = $this->shipping_provider->get_title();
		$this->method_description = sprintf( _x( 'Apply rule-based shipping costs for shipments handled by %1$s based on your available packaging options. Learn <a href="https://vendidero.com/doc/shiptastic/manage-shipping-rules">how to configure →</a>', 'shipments', 'woocommerce-germanized' ), $this->shipping_provider->get_title() );
		$this->title              = $this->method_title;
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
		);

		$this->init();
	}

	/**
	 * Init user set variables.
	 */
	public function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title      = $this->get_option( 'title' );
		$this->tax_status = $this->get_option( 'tax_status' );

		// Actions.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * @return ShippingProvider
	 */
	public function get_shipping_provider() {
		return $this->shipping_provider;
	}

	public function get_all_shipping_rules() {
		return array_merge( ...array_values( $this->get_shipping_rules() ) );
	}

	public function get_shipping_rules() {
		return $this->get_option( 'shipping_rules', array() );
	}

	public function get_shipping_rule_by_id( $rule_id, $packaging_id ) {
		$rules    = $this->get_shipping_rules_by_packaging( $packaging_id );
		$rule_key = "rule_{$rule_id}";

		if ( array_key_exists( $rule_key, $rules ) ) {
			return $rules[ $rule_key ];
		}

		return false;
	}

	public function get_shipping_rules_by_packaging( $packaging ) {
		$shipping_rules  = $this->get_shipping_rules();
		$packaging_rules = array();

		if ( array_key_exists( $packaging, $shipping_rules ) ) {
			$packaging_rules = $shipping_rules[ $packaging ];
		}

		return $packaging_rules;
	}

	public function get_fallback_shipping_rules() {
		$shipping_rules  = $this->get_shipping_rules();
		$packaging_rules = array();

		if ( array_key_exists( 'all', $shipping_rules ) ) {
			$packaging_rules = $shipping_rules['all'];
		}

		return $packaging_rules;
	}

	public function admin_options() {
		$locale        = localeconv();
		$decimal_point = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';
		$decimal       = ( ! empty( wc_get_price_decimal_separator() ) ) ? wc_get_price_decimal_separator() : $decimal_point;

		wp_localize_script(
			'wc-shiptastic-admin-shipping-rules',
			'wc_shiptastic_admin_shipping_rules_params',
			array(
				'rules'                   => $this->get_option( 'shipping_rules', array() ),
				'decimal_separator'       => $decimal,
				'price_decimal_separator' => wc_get_price_decimal_separator(),
				'default_shipping_rule'   => array(
					'rule_id'    => 0,
					'packaging'  => '',
					'costs'      => '',
					'conditions' => array(
						array(
							'rule_id'      => 0,
							'condition_id' => 0,
							'type'         => 'always',
							'operator'     => '',
						),
					),
				),
				'strings'                 => array(
					'unload_confirmation_msg' => _x( 'Your changed data will be lost if you leave this page without saving.', 'shipments', 'woocommerce-germanized' ),
				),
			)
		);
		wp_enqueue_script( 'wc-shiptastic-admin-shipping-rules' );

		parent::admin_options();
	}

	/**
	 * @return false|\WC_Shipping_Zone
	 */
	public function get_zone() {
		if ( $this->get_instance_id() > 0 ) {
			if ( is_null( $this->zone ) ) {
				$this->zone = \WC_Shipping_Zones::get_zone_by( 'instance_id', $this->get_instance_id() );
			}

			return $this->zone;
		}

		return false;
	}

	/**
	 * Return admin options as a html string.
	 *
	 * @return string
	 */
	public function get_admin_options_html() {
		if ( $this->instance_id ) {
			$settings_html = $this->generate_settings_html( $this->get_instance_form_fields(), false );
		} else {
			$settings_html = $this->generate_settings_html( $this->get_form_fields(), false );
		}

		return '<table class="form-table">' . $settings_html . '</table>';
	}

	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title'                                    => array(
				'title'       => _x( 'Title', 'shipments', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => _x( 'This controls the title which the user sees during checkout.', 'shipments', 'woocommerce-germanized' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
			'free_title'                               => array(
				'title'       => _x( 'Title (free shipping)', 'shipments', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => _x( 'This controls the title which the user sees during checkout in case a free shipping option is available.', 'shipments', 'woocommerce-germanized' ),
				'default'     => sprintf( _x( 'Free shipping (via %1$s)', 'shipments', 'woocommerce-germanized' ), $this->method_title ),
				'desc_tip'    => true,
			),
			'tax_status'                               => array(
				'title'   => _x( 'Tax status', 'shipments', 'woocommerce-germanized' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => _x( 'Taxable', 'shipments', 'woocommerce-germanized' ),
					'none'    => _x( 'None', 'shipments-tax-status', 'woocommerce-germanized' ),
				),
			),
			'shipping_rules_title'                     => array(
				'title'       => _x( 'Shipping Rules', 'shipments', 'woocommerce-germanized' ),
				'type'        => 'title',
				'id'          => 'shipping_rules_title',
				'default'     => '',
				'description' => sprintf( _x( 'Configure shipping costs per packaging option. Within cart, a rucksack algorithm will automatically fit the items in the packaging option(s) available and calculate it\'s cost.<br/> Some important hints on the calculation logic: <ol><li>The <i>from</i> value (e.g. 3) is expected to be inclusive (greater or equal 3). The <i>to</i> value (e.g. 5) is expected to be exclusive (smaller than 5).</li><li>Leave the <i>to</i> value empty for your last packaging rule to match all subsequent values.</li><li>All conditions must be met for the shipping rule to apply.</li><li>In case a free shipping rule is available, the conditional logic automatically stops.</li><li>The <i>all remaining packaging</i> rules will be used for available packaging options without custom rules and serve as fallback in case no applicable rule was found.</li><li>In case no <i>all remaining packaging</i> rule exists, only packaging options with custom rules will be used for packing.</li></ol>', 'shipments', 'woocommerce-germanized' ) ),
			),
			'multiple_shipments_cost_calculation_mode' => array(
				'title'    => _x( 'Multiple packages', 'shipments', 'woocommerce-germanized' ),
				'type'     => 'select',
				'default'  => 'sum',
				'options'  => array(
					'sum' => _x( 'Sum all costs', 'shipments', 'woocommerce-germanized' ),
					'max' => _x( 'Apply the maximum cost only', 'shipments', 'woocommerce-germanized' ),
					'min' => _x( 'Apply the minimum cost only', 'shipments', 'woocommerce-germanized' ),
				),
				'desc_tip' => _x( 'The algorithm may detect that multiple packages, with possibly different packaging, for the current cart may be needed. Choose how to calculate costs.', 'shipments', 'woocommerce-germanized' ),
			),
			'multiple_rules_cost_calculation_mode'     => array(
				'title'    => _x( 'Multiple matching rules', 'shipments', 'woocommerce-germanized' ),
				'type'     => 'select',
				'default'  => 'max',
				'options'  => array(
					'sum' => _x( 'Sum all costs', 'shipments', 'woocommerce-germanized' ),
					'max' => _x( 'Apply the maximum cost only', 'shipments', 'woocommerce-germanized' ),
					'min' => _x( 'Apply the minimum cost only', 'shipments', 'woocommerce-germanized' ),
				),
				'desc_tip' => _x( 'Decide how costs should add up in case multiple rules per packaging option match the current cart.', 'shipments', 'woocommerce-germanized' ),
			),
			'shipping_rules'                           => array(
				'title'   => _x( 'Rules', 'shipments', 'woocommerce-germanized' ),
				'type'    => 'shipping_rules',
				'default' => array(),
			),
			'cache'                                    => array(
				'type'    => 'cache',
				'default' => array(),
			),
		);
	}

	public function get_rule_conditional_operators() {
		return apply_filters(
			'woocommerce_shiptastic_shipping_method_rule_condition_operators',
			array(
				'is'      => array(
					'label'       => _x( 'is', 'shipments', 'woocommerce-germanized' ),
					'is_negation' => false,
				),
				'is_not'  => array(
					'label'       => _x( 'is not', 'shipments', 'woocommerce-germanized' ),
					'is_negation' => true,
				),
				'any_of'  => array(
					'label'       => _x( 'any of', 'shipments', 'woocommerce-germanized' ),
					'is_negation' => false,
				),
				'none_of' => array(
					'label'       => _x( 'none of', 'shipments', 'woocommerce-germanized' ),
					'is_negation' => true,
				),
				'exactly' => array(
					'label'       => _x( 'Exactly', 'shipments', 'woocommerce-germanized' ),
					'is_negation' => false,
				),
			)
		);
	}

	public function get_condition_types() {
		return apply_filters(
			'woocommerce_shiptastic_shipping_method_rule_condition_types',
			array(
				'always'                   => array(
					'label'     => _x( 'Always', 'shipments', 'woocommerce-germanized' ),
					'fields'    => array(),
					'operators' => array(),
					'is_global' => true,
				),
				'package_weight'           => array(
					'label'     => _x( 'Package weight', 'shipments', 'woocommerce-germanized' ),
					'fields'    => array(
						'weight_from' => array(
							'type'            => 'text',
							'data_type'       => 'decimal',
							'data_validation' => 'weight',
							'label'           => _x( 'from', 'shipments', 'woocommerce-germanized' ),
						),
						'weight_to'   => array(
							'type'            => 'text',
							'data_type'       => 'decimal',
							'data_validation' => 'weight',
							'label'           => _x( 'to', 'shipments', 'woocommerce-germanized' ),
							'description'     => class_exists( '\Automattic\WooCommerce\Utilities\I18nUtil' ) ? \Automattic\WooCommerce\Utilities\I18nUtil::get_weight_unit_label( get_option( 'woocommerce_weight_unit', 'kg' ) ) : get_option( 'woocommerce_weight_unit', 'kg' ),
						),
					),
					'operators' => array( 'is', 'is_not' ),
				),
				'weight'                   => array(
					'label'     => _x( 'Cart weight', 'shipments', 'woocommerce-germanized' ),
					'fields'    => array(
						'weight_from' => array(
							'type'            => 'text',
							'data_type'       => 'decimal',
							'data_validation' => 'weight',
							'label'           => _x( 'from', 'shipments', 'woocommerce-germanized' ),
						),
						'weight_to'   => array(
							'type'            => 'text',
							'data_type'       => 'decimal',
							'data_validation' => 'weight',
							'label'           => _x( 'to', 'shipments', 'woocommerce-germanized' ),
							'description'     => class_exists( '\Automattic\WooCommerce\Utilities\I18nUtil' ) ? \Automattic\WooCommerce\Utilities\I18nUtil::get_weight_unit_label( get_option( 'woocommerce_weight_unit', 'kg' ) ) : get_option( 'woocommerce_weight_unit', 'kg' ),
						),
					),
					'operators' => array( 'is', 'is_not' ),
					'is_global' => true,
				),
				'package_total'            => array(
					'label'     => _x( 'Package total', 'shipments', 'woocommerce-germanized' ),
					'fields'    => array(
						'total_from' => array(
							'type'      => 'text',
							'data_type' => 'price',
							'label'     => _x( 'from', 'shipments', 'woocommerce-germanized' ),
						),
						'total_to'   => array(
							'type'        => 'text',
							'data_type'   => 'price',
							'label'       => _x( 'to', 'shipments', 'woocommerce-germanized' ),
							'description' => get_woocommerce_currency_symbol(),
						),
					),
					'operators' => array( 'is', 'is_not' ),
				),
				'total'                    => array(
					'label'     => _x( 'Cart total', 'shipments', 'woocommerce-germanized' ),
					'fields'    => array(
						'total_from' => array(
							'type'      => 'text',
							'data_type' => 'price',
							'label'     => _x( 'from', 'shipments', 'woocommerce-germanized' ),
						),
						'total_to'   => array(
							'type'        => 'text',
							'data_type'   => 'price',
							'label'       => _x( 'to', 'shipments', 'woocommerce-germanized' ),
							'description' => get_woocommerce_currency_symbol(),
						),
					),
					'operators' => array( 'is', 'is_not' ),
					'is_global' => true,
				),
				'shipping_classes'         => array(
					'label'     => _x( 'Cart shipping class', 'shipments', 'woocommerce-germanized' ),
					'fields'    => array(
						'classes' => array(
							'type'      => 'multiselect',
							'data_type' => 'array',
							'class'     => 'wc-enhanced-select',
							'label'     => _x( 'Class', 'shipments', 'woocommerce-germanized' ),
							'options'   => function () {
								return Package::get_shipping_classes();
							},
						),
					),
					'operators' => array( 'any_of', 'none_of', 'exactly' ),
					'is_global' => true,
				),
				'package_shipping_classes' => array(
					'label'     => _x( 'Package shipping class', 'shipments', 'woocommerce-germanized' ),
					'fields'    => array(
						'classes' => array(
							'type'      => 'multiselect',
							'data_type' => 'array',
							'class'     => 'wc-enhanced-select',
							'label'     => _x( 'Class', 'shipments', 'woocommerce-germanized' ),
							'options'   => function () {
								return Package::get_shipping_classes();
							},
						),
					),
					'operators' => array( 'any_of', 'none_of', 'exactly' ),
				),
			)
		);
	}

	public function get_conditional_operator( $operator ) {
		$operators = $this->get_rule_conditional_operators();

		if ( array_key_exists( $operator, $operators ) ) {
			return $operators[ $operator ];
		}

		return false;
	}

	public function get_condition_type( $type ) {
		$condition_types = $this->get_condition_types();

		if ( array_key_exists( $type, $condition_types ) ) {
			return wp_parse_args(
				$condition_types[ $type ],
				array(
					'label'     => '',
					'fields'    => array(),
					'operators' => array(),
					'is_global' => false,
				)
			);
		}

		return false;
	}

	public function get_rate_label( $costs ) {
		$label = $this->get_title();

		if ( 0.0 === $costs ) {
			$label = $this->get_instance_option( 'free_title', sprintf( _x( 'Free shipping (via %1$s)', 'shipments', 'woocommerce-germanized' ), $this->get_method_title() ) );
		}

		return $label;
	}

	public function get_cache( $property = null, $default_value = null ) {
		$cache = wp_parse_args(
			$this->get_option( 'cache', array() ),
			array(
				'packaging_ids' => array(),
				'costs'         => null,
				'global_rules'  => null,
			)
		);

		if ( is_null( $cache['global_rules'] ) || is_null( $cache['costs'] ) ) {
			$cache = $this->get_updated_cache();
			$this->update_option( 'cache', $cache );
		}

		if ( ! is_null( $property ) ) {
			if ( array_key_exists( $property, $cache ) ) {
				return $cache[ $property ];
			} else {
				return $default_value;
			}
		}

		return $cache;
	}

	public function get_multiple_shipments_cost_calculation_mode() {
		return $this->get_instance_option( 'multiple_shipments_cost_calculation_mode', 'sum' );
	}

	public function get_multiple_rules_cost_calculation_mode() {
		return $this->get_instance_option( 'multiple_rules_cost_calculation_mode', 'max' );
	}

	public function get_available_packaging_boxes( $package_data = array() ) {
		$cache         = $this->get_cache();
		$packaging_ids = $cache['packaging_ids'];
		$global_rules  = $cache['global_rules'];
		$costs         = $cache['costs'];

		if ( in_array( 'all', $packaging_ids, true ) || empty( $packaging_ids ) ) {
			$packaging_boxes = Helper::get_packaging_boxes();
			$packaging_ids   = array_diff( $packaging_ids, array( 'all' ) );

			foreach ( $packaging_boxes as $id => $box ) {
				if ( in_array( $id, $packaging_ids, true ) ) {
					continue;
				}

				if ( $box->get_packaging()->supports_shipping_provider( $this->get_shipping_provider() ) ) {
					$packaging_ids[] = $id;
				}
			}
		}

		/**
		 * Filter available packaging based on global rules, e.g. weight/total/shipping classes
		 * and do only allow applicable packaging options to be chosen for actual packing process.
		 */
		if ( ! empty( $package_data ) && count( $global_rules ) > 0 ) {
			$has_fallback_global_rules    = array_key_exists( 'all', $global_rules );
			$is_global_fallback_available = true;

			if ( $has_fallback_global_rules ) {
				$fallback_rules = $this->get_fallback_shipping_rules();

				if ( count( $fallback_rules ) === count( $global_rules['all'] ) ) {
					$is_global_fallback_available = false;

					foreach ( array_reverse( $global_rules['all'] ) as $rule_id ) {
						if ( $rule = $this->get_shipping_rule_by_id( $rule_id, 'all' ) ) {
							$rule         = $this->parse_rule( $rule );
							$rule_applies = $this->rule_applies( $rule, $package_data, true );

							if ( $rule_applies ) {
								$is_global_fallback_available = true;
								break;
							}
						}
					}
				}
			}

			foreach ( $packaging_ids as $packaging_id ) {
				$global_packaging_rules      = array_key_exists( $packaging_id, $global_rules ) ? $global_rules[ $packaging_id ] : array();
				$global_packaging_rule_count = count( $global_packaging_rules );
				$packaging_rule_count        = count( $this->get_shipping_rules_by_packaging( $packaging_id ) );
				$has_rules                   = $packaging_rule_count > 0;
				$packaging_available         = true;

				if ( $global_packaging_rule_count > 0 && $packaging_rule_count === $global_packaging_rule_count ) {
					$packaging_available = false;

					foreach ( array_reverse( $global_packaging_rules ) as $rule_id ) {
						if ( $rule = $this->get_shipping_rule_by_id( $rule_id, $packaging_id ) ) {
							$rule         = $this->parse_rule( $rule );
							$rule_applies = $this->rule_applies( $rule, $package_data, true );

							if ( $rule_applies ) {
								$packaging_available = true;
								break;
							}
						}
					}
				} elseif ( ! $has_rules && $has_fallback_global_rules ) {
					$packaging_available = $is_global_fallback_available;
				}

				if ( ! $packaging_available ) {
					$packaging_ids = array_diff( $packaging_ids, array( $packaging_id ) );
				}
			}
		}

		$packaging_ids = array_unique( array_values( $packaging_ids ) );
		$boxes         = Helper::get_packaging_boxes( apply_filters( 'woocommerce_shiptastic_shipping_method_available_packaging_ids', $packaging_ids, $this ) );

		foreach ( $costs as $packaging_id => $cost ) {
			if ( array_key_exists( $packaging_id, $boxes ) ) {
				$boxes[ $packaging_id ]->set_costs( $cost['avg'] );
			}
		}

		return $boxes;
	}

	public function calculate_shipping( $package = array() ) {
		$applied_rules = array();
		$debug_notices = array();
		$is_debug_mode = Package::is_shipping_debug_mode();

		if ( isset( $package['items_to_pack'], $package['package_data'] ) ) {
			$cart_data       = (array) $package['package_data'];
			$available_boxes = $this->get_available_packaging_boxes( $cart_data );
			$boxes           = PackagingList::fromArray( $available_boxes );

			$cost_calculation_mode           = $this->get_multiple_shipments_cost_calculation_mode();
			$multiple_rules_calculation_mode = $this->get_multiple_rules_cost_calculation_mode();

			$total_cost            = 0.0;
			$rule_ids              = array();
			$packaging_ids         = array();
			$total_packed_item_map = array();
			$total_packed_items    = 0;
			$packed_boxes          = Helper::pack( $package['items_to_pack'], $boxes, 'cart' );
			$unpacked_items        = Helper::get_last_unpacked_items();

			if ( 0 === count( $unpacked_items ) ) {
				foreach ( $packed_boxes as $box ) {
					$packaging                    = $box->getBox();
					$items                        = $box->getItems();
					$total_weight                 = wc_get_weight( $items->getWeight(), strtolower( get_option( 'woocommerce_weight_unit' ) ), 'g' );
					$volume                       = wc_get_dimension( $items->getVolume(), strtolower( get_option( 'woocommerce_dimension_unit' ) ), 'mm' );
					$item_count                   = $items->count();
					$total                        = 0;
					$subtotal                     = 0;
					$products                     = array();
					$shipping_classes             = array();
					$has_missing_shipping_classes = false;

					foreach ( $items as $item ) {
						$cart_item = $item->getItem();
						$total    += $cart_item->get_total();
						$subtotal += $cart_item->get_subtotal();
						$product   = $cart_item->get_product();

						if ( $product && ! array_key_exists( $product->get_id(), $products ) ) {
							$products[ $product->get_id() ] = $product;

							if ( ! empty( $product->get_shipping_class_id() ) ) {
								$shipping_classes[] = $product->get_shipping_class_id();
							} else {
								$has_missing_shipping_classes = true;
							}
						}
					}

					$total            = wc_remove_number_precision( $total );
					$subtotal         = wc_remove_number_precision( $subtotal );
					$shipping_classes = array_unique( $shipping_classes );
					$package_data     = array_merge(
						$cart_data,
						array(
							'package_total'            => $total,
							'package_subtotal'         => $subtotal,
							'package_weight'           => $total_weight,
							'package_volume'           => $volume,
							'package_item_count'       => $item_count,
							'packaging_id'             => $packaging->get_id(),
							'package_products'         => $products,
							'package_shipping_classes' => $shipping_classes,
							'package_has_missing_shipping_classes' => $has_missing_shipping_classes,
						)
					);

					$package_applied_rules = array();
					$applicable_rule_costs = array();

					foreach ( array_reverse( $this->get_shipping_rules_by_packaging( $packaging->get_id() ) ) as $rule ) {
						$rule         = $this->parse_rule( $rule );
						$rule_applies = $this->rule_applies( $rule, $package_data );

						if ( $rule_applies ) {
							$applicable_rule_costs[] = $rule['costs'];
							$package_applied_rules[] = $rule['rule_id'];
						}

						/**
						 * In case a free shipping option is detected, stop + reset.
						 */
						if ( $rule_applies && 0.0 === $rule['costs'] ) {
							$applicable_rule_costs = array(
								$rule['costs'],
							);

							$package_applied_rules = array(
								$rule['rule_id'],
							);

							break;
						}
					}

					/**
					 * In case no applicable rule has been found, parse fallback rules.
					 */
					if ( empty( $package_applied_rules ) ) {
						foreach ( array_reverse( $this->get_fallback_shipping_rules() ) as $rule ) {
							$rule         = $this->parse_rule( $rule );
							$rule_applies = $this->rule_applies( $rule, $package_data );

							if ( $rule_applies ) {
								$applicable_rule_costs[] = $rule['costs'];
								$package_applied_rules[] = $rule['rule_id'];
							}

							/**
							 * In case a free shipping option is detected, stop + reset.
							 */
							if ( $rule_applies && 0.0 === $rule['costs'] ) {
								$applicable_rule_costs = array(
									$rule['costs'],
								);

								$package_applied_rules = array(
									$rule['rule_id'],
								);

								break;
							}
						}
					}

					if ( ! empty( $package_applied_rules ) ) {
						$applicable_rules_total_cost = 0.0;

						if ( 'sum' === $multiple_rules_calculation_mode ) {
							$applicable_rules_total_cost = array_sum( $applicable_rule_costs );
						} elseif ( 'min' === $multiple_rules_calculation_mode ) {
							$applicable_rules_total_cost = min( $applicable_rule_costs );
						} elseif ( 'max' === $multiple_rules_calculation_mode ) {
							$applicable_rules_total_cost = max( $applicable_rule_costs );
						}

						if ( 'min' === $cost_calculation_mode ) {
							if ( $applicable_rules_total_cost <= $total_cost || 0.0 === $total_cost ) {
								$total_cost = $applicable_rules_total_cost;
							}
						} elseif ( 'max' === $cost_calculation_mode ) {
							if ( $applicable_rules_total_cost >= $total_cost ) {
								$total_cost = $applicable_rules_total_cost;
							}
						} else {
							$total_cost += $applicable_rules_total_cost;
						}

						/**
						 * Build an item map which contains a map of the cart items
						 * included within the package.
						 */
						$item_map = array();
						$weight   = 0.0;

						foreach ( $items as $item ) {
							$cart_item_wrapper = $item->getItem();
							$product           = $cart_item_wrapper->get_product();
							$product_key       = $product->get_parent_id() . '_' . $product->get_id();
							$weight           += $cart_item_wrapper->getWeight();

							if ( array_key_exists( $product_key, $item_map ) ) {
								++$item_map[ $product_key ];
							} else {
								$item_map[ $product_key ] = 1;
							}

							if ( array_key_exists( $product_key, $total_packed_item_map ) ) {
								++$total_packed_item_map[ $product_key ];
							} else {
								$total_packed_item_map[ $product_key ] = 1;
							}

							++$total_packed_items;
						}

						$applied_rules[] = array(
							'packaging_id' => $packaging->get_id(),
							'rules'        => $package_applied_rules,
							'items'        => $item_map,
							'weight'       => $weight + $packaging->getEmptyWeight(),
						);

						$rule_ids      = array_unique( array_merge( $rule_ids, $package_applied_rules ) );
						$packaging_ids = array_unique( array_merge( $packaging_ids, array( $packaging->get_id() ) ) );
					}
				}

				if ( ! empty( $applied_rules ) ) {
					if ( $is_debug_mode ) {
						$package_count = 0;

						foreach ( $applied_rules as $applied_rule ) {
							if ( $packaging = wc_stc_get_packaging( $applied_rule['packaging_id'] ) ) {
								++$package_count;
								$debug_notices[] = sprintf( _x( '## Package %1$d/%2$d: %3$s: ', 'shipments', 'woocommerce-germanized' ), $package_count, count( $applied_rules ), $packaging->get_title() );

								foreach ( $applied_rule['rules'] as $rule ) {
									if ( $the_rule = $this->get_shipping_rule_by_id( $rule, $applied_rule['packaging_id'] ) ) {
										$debug_notices[] = sprintf( _x( 'Rule %1$d: %2$s', 'shipments', 'woocommerce-germanized' ), $rule, wc_price( $the_rule['costs'] ) );
									}
								}

								foreach ( $applied_rule['items'] as $item_product_key => $quantity ) {
									$product_ids   = explode( '_', $item_product_key );
									$product_title = $product_ids[0];

									if ( $product = wc_get_product( $product_ids[1] ) ) {
										$product_title = $product->get_title();
									}

									$product_desc    = ! empty( $product_ids[0] ) ? sprintf( _x( '%1$s (Parent: %2$s)', 'shipments', 'woocommerce-germanized' ), $product_title, $product_ids[0] ) : $product_title;
									$debug_notices[] = sprintf( _x( '%1$s x %2$s', 'shipments', 'woocommerce-germanized' ), $quantity, $product_desc );
								}
							}
						}

						$debug_notices[] = sprintf( _x( '## Total: %1$s (%2$s, %3$s)', 'shipments', 'woocommerce-germanized' ), wc_price( $total_cost ), $cost_calculation_mode, $multiple_rules_calculation_mode );
					}

					$this->add_rate(
						array(
							'cost'      => $total_cost,
							'label'     => $this->get_rate_label( $total_cost ),
							'package'   => $package,
							'meta_data' => array(
								'_packed_items'    => $total_packed_items,
								'_packed_item_map' => $total_packed_item_map,
								'_packaging_ids'   => $packaging_ids,
								'_rule_ids'        => $rule_ids,
								'_packages'        => $applied_rules,
							),
						)
					);
				} elseif ( $is_debug_mode ) {
					$debug_notices[] = _x( 'None of the available rules applied.', 'shipments', 'woocommerce-germanized' );
				}
			} elseif ( $is_debug_mode ) {
				foreach ( $unpacked_items as $item ) {
					$product_desc = $item->get_id();

					if ( $product = $item->get_product() ) {
						$product_desc = $product->get_title();
					}

					$debug_notices[] = sprintf( _x( '%1$s does not fit the available packaging options', 'shipments', 'woocommerce-germanized' ), $product_desc );
				}
			}

			if ( $is_debug_mode && ! Package::is_constant_defined( 'WOOCOMMERCE_CHECKOUT' ) && ! Package::is_constant_defined( 'WC_DOING_AJAX' ) && ! empty( $debug_notices ) ) {
				$the_notice         = '';
				$cart_wide_notice   = '';
				$available_box_list = array();
				$cart_wide_notices  = array();

				$cart_wide_notices[] = _x( '### Items available to pack:', 'shipments', 'woocommerce-germanized' );

				foreach ( $package['items_to_pack'] as $item_to_pack ) {
					$cart_wide_notices[] = $item_to_pack->getDescription() . ' (' . wc_stc_format_shipment_dimensions( $item_to_pack->get_dimensions(), 'mm' ) . ', ' . wc_stc_format_shipment_weight( $item_to_pack->getWeight(), 'g' ) . ')';
				}

				foreach ( $cart_wide_notices as $notice ) {
					$cart_wide_notice .= $notice . '<br/>';
				}

				if ( ! wc_has_notice( $cart_wide_notice ) ) {
					wc_add_notice( $cart_wide_notice );
				}

				foreach ( $available_boxes as $box ) {
					$available_box_list[] = $box->get_packaging()->get_title();
				}

				$general_debug_notices = array(
					sprintf( _x( '### Debug information for %1$s:', 'shipments', 'woocommerce-germanized' ), $this->get_title() ),
					sprintf( _x( 'Available packaging options: %1$s', 'shipments', 'woocommerce-germanized' ), implode( ', ', $available_box_list ) ),
				);

				if ( empty( $applied_rules ) ) {
					foreach ( $packed_boxes as $packed_box_index => $box ) {
						$packaging               = $box->getBox();
						$general_debug_notices[] = sprintf( _x( '## Packed box %1$d/%2$d: %3$s', 'shipments', 'woocommerce-germanized' ), ++$packed_box_index, count( $packed_boxes ), $packaging->getReference() );
					}
				}

				$debug_notices = array_merge( $general_debug_notices, $debug_notices );

				foreach ( $debug_notices as $notice ) {
					$the_notice .= $notice . '<br/>';
				}

				if ( ! wc_has_notice( $the_notice ) ) {
					wc_add_notice( $the_notice );
				}
			}
		}
	}

	protected function parse_rule( $rule ) {
		$rule = wp_parse_args(
			$rule,
			array(
				'rule_id'    => '',
				'packaging'  => '',
				'conditions' => array(),
				'costs'      => 0.0,
				'meta'       => array(),
			)
		);

		$rule['costs'] = (float) wc_format_decimal( $rule['costs'] );

		return $rule;
	}

	protected function parse_rule_condition( $condition ) {
		$condition = wp_parse_args(
			$condition,
			array(
				'rule_id'      => '',
				'condition_id' => '',
				'type'         => '',
				'operator'     => '',
			)
		);

		$condition['type']     = sanitize_key( $condition['type'] );
		$condition['operator'] = sanitize_key( $condition['operator'] );

		return $condition;
	}

	protected function rule_applies( $rule, $package_data, $global_only = false ) {
		$rule_applies = true;
		$rule         = $this->parse_rule( $rule );
		$package_data = wp_parse_args(
			$package_data,
			array(
				'package_weight'                       => 0.0,
				'package_volume'                       => 0.0,
				'package_total'                        => 0.0,
				'package_subtotal'                     => 0.0,
				'package_products'                     => array(),
				'package_shipping_classes'             => array(),
				'package_has_missing_shipping_classes' => false,
				'weight'                               => 0.0,
				'volume'                               => 0.0,
				'total'                                => 0.0,
				'subtotal'                             => 0.0,
				'products'                             => array(),
				'shipping_classes'                     => array(),
			)
		);

		foreach ( $rule['conditions'] as $condition ) {
			$condition         = $this->parse_rule_condition( $condition );
			$condition_applies = false;

			if ( $condition_type = $this->get_condition_type( $condition['type'] ) ) {
				$condition_type_name = $condition['type'];
				$operator_name       = $condition['operator'];

				/**
				 * Skip non-global conditions, e.g. packaging conditions in case set.
				 */
				if ( $global_only && ! $condition_type['is_global'] ) {
					continue;
				}

				if ( $operator = $this->get_conditional_operator( $operator_name ) ) {
					if ( $operator['is_negation'] ) {
						$condition_applies = true;
					}
				}

				if ( has_filter( "woocommerce_shiptastic_shipping_method_rule_condition_{$condition_type_name}_applies" ) ) {
					$condition_applies = apply_filters( "woocommerce_shiptastic_shipping_method_rule_condition_{$condition_type_name}_applies", $package_data, $rule, $condition, $this );
				} elseif ( 'always' === $condition_type_name ) {
					$condition_applies = true;
				} elseif ( 'weight' === $condition_type_name || 'package_weight' === $condition_type_name ) {
					$from = isset( $condition['weight_from'] ) && ! empty( $condition['weight_from'] ) ? (float) wc_format_decimal( $condition['weight_from'] ) : 0.0;
					$to   = isset( $condition['weight_to'] ) && ! empty( $condition['weight_to'] ) ? (float) wc_format_decimal( $condition['weight_to'] ) : 0.0;

					if ( $package_data[ $condition_type_name ] >= $from && ( $package_data[ $condition_type_name ] < $to || 0.0 === $to ) ) {
						if ( 'is' === $operator_name ) {
							$condition_applies = true;
						} elseif ( 'is_not' === $operator_name ) {
							$condition_applies = false;
						}
					}
				} elseif ( 'total' === $condition_type_name || 'package_total' === $condition_type_name ) {
					$from = isset( $condition['total_from'] ) && ! empty( $condition['total_from'] ) ? (float) wc_format_decimal( $condition['total_from'] ) : 0.0;
					$to   = isset( $condition['total_to'] ) && ! empty( $condition['total_to'] ) ? (float) wc_format_decimal( $condition['total_to'] ) : 0.0;

					if ( $package_data[ $condition_type_name ] >= $from && ( $package_data[ $condition_type_name ] < $to || 0.0 === $to ) ) {
						if ( 'is' === $operator_name ) {
							$condition_applies = true;
						} elseif ( 'is_not' === $operator_name ) {
							$condition_applies = false;
						}
					}
				} elseif ( 'shipping_classes' === $condition_type_name || 'package_shipping_classes' === $condition_type_name ) {
					$classes = isset( $condition['classes'] ) && ! empty( $condition['classes'] ) ? apply_filters( 'woocommerce_shiptastic_shipping_method_shipping_classes', array_map( 'absint', (array) $condition['classes'] ) ) : array();

					if ( 'exactly' === $operator_name ) {
						$has_missing_shipping_classes = 'package_shipping_classes' === $condition_type_name ? $package_data['package_has_missing_shipping_classes'] : $package_data['has_missing_shipping_classes'];
						$condition_applies            = ! $has_missing_shipping_classes && $package_data[ $condition_type_name ] === $classes;
					} elseif ( array_intersect( $package_data[ $condition_type_name ], $classes ) ) {
						if ( 'any_of' === $operator_name ) {
							$condition_applies = true;
						} elseif ( 'none_of' === $operator_name ) {
							$condition_applies = false;
						}
					}
				}
			}

			if ( ! $condition_applies ) {
				$rule_applies = false;
				break;
			}
		}

		return $rule_applies;
	}

	protected function get_packaging_list( $add_all_option = true ) {
		$packaging_select = array();

		foreach ( wc_stc_get_packaging_list( array( 'shipping_provider' => $this->get_shipping_provider()->get_name() ) ) as $packaging ) {
			$packaging_select[ $packaging->get_id() ] = $packaging->get_title();
		}

		if ( $add_all_option ) {
			$packaging_select['all'] = _x( 'All remaining packaging', 'shipments', 'woocommerce-germanized' );
		}

		return $packaging_select;
	}

	protected function get_packaging_edit_url( $packaging ) {
		$url = PackagingSettings::get_settings_url( $packaging );

		if ( 'all' === $packaging ) {
			$url = Settings::get_settings_url( 'packaging' );
		}

		return $url;
	}

	protected function get_packaging_help_tip( $packaging ) {
		$help_tip = '';

		if ( 'all' === $packaging ) {
			$help_tip = _x( 'These rules will be parsed for all remaining, available packaging without rules and/or in case no rules matched.', 'shipments', 'woocommerce-germanized' );
		}

		return $help_tip;
	}

	protected function generate_cache_html() {
		return '';
	}

	protected function validate_cache_field() {
		return $this->get_updated_cache();
	}

	protected function get_updated_cache() {
		$rules        = $this->get_option( 'shipping_rules', array() );
		$global_rules = array();
		$costs        = array();

		foreach ( $rules as $packaging_id => $packaging_rules ) {
			$costs[ $packaging_id ] = array(
				'min' => 0.0,
				'max' => 0.0,
				'avg' => 0.0,
			);

			foreach ( $packaging_rules as $packaging_rule ) {
				/**
				 * Global rules
				 */
				$is_global = true;

				foreach ( $packaging_rule['conditions'] as $condition ) {
					if ( $condition_type = $this->get_condition_type( $condition['type'] ) ) {
						if ( ! $condition_type['is_global'] ) {
							$is_global = false;
							break;
						}
					}
				}

				if ( $is_global ) {
					if ( ! array_key_exists( $packaging_id, $global_rules ) ) {
						$global_rules[ $packaging_id ] = array();
					}

					$global_rules[ $packaging_id ][] = $packaging_rule['rule_id'];
				}

				/**
				 * Min, max costs
				 */
				$cost = (float) wc_format_decimal( $packaging_rule['costs'] );

				if ( $cost >= $costs[ $packaging_id ]['max'] ) {
					$costs[ $packaging_id ]['max'] = $cost;
				}

				if ( $cost <= $costs[ $packaging_id ]['min'] || 0.0 === $costs[ $packaging_id ]['min'] ) {
					$costs[ $packaging_id ]['min'] = $cost;
				}

				$costs[ $packaging_id ]['avg'] += $cost;
			}

			if ( count( $packaging_rules ) > 0 ) {
				$costs[ $packaging_id ]['avg'] = $costs[ $packaging_id ]['avg'] / count( $packaging_rules );
			}
		}

		$cache = array(
			'packaging_ids' => array_keys( $rules ),
			'global_rules'  => $global_rules,
			'costs'         => $costs,
		);

		return $cache;
	}

	protected function validate_shipping_rules_field( $option_name, $option_value ) {
		$option_value = stripslashes_deep( $option_value );

		if ( is_null( $option_value ) ) {
			return $option_value;
		}

		$ids             = array_keys( $option_value['costs'] );
		$rules           = array();
		$condition_types = $this->get_condition_types();
		$index           = 0;

		foreach ( $ids as $id ) {
			$rule_id   = $index++;
			$packaging = 'all' === $option_value['packaging'][ $id ] ? 'all' : absint( $option_value['packaging'][ $id ] );
			$costs     = (float) wc_format_decimal( isset( $option_value['costs'][ $id ] ) ? wc_clean( $option_value['costs'][ $id ] ) : 0, false, true );

			$rule = array(
				'rule_id'    => $rule_id,
				'packaging'  => $packaging,
				'conditions' => array(),
				'costs'      => $costs,
				'meta'       => array(),
			);

			$conditions      = (array) $option_value['conditions'][ $id ];
			$condition_index = 0;

			foreach ( $conditions as $condition ) {
				$condition_type = isset( $condition['type'] ) ? wc_clean( $condition['type'] ) : '';

				if ( ! array_key_exists( $condition_type, $condition_types ) ) {
					continue;
				}

				$condition_type_data = $condition_types[ $condition_type ];
				$available_operators = $condition_type_data['operators'];
				$operator            = isset( $condition['operator'][ $condition_type ] ) ? wc_clean( $condition['operator'][ $condition_type ] ) : '';
				$condition_id        = $condition_index++;
				$default_operator    = empty( $available_operators ) ? '' : $available_operators[0];

				$new_condition = array(
					'rule_id'      => $rule_id,
					'type'         => $condition_type,
					'condition_id' => $condition_id,
					'operator'     => in_array( $operator, $available_operators, true ) ? $operator : $default_operator,
				);

				foreach ( $condition_type_data['fields'] as $field_name => $field ) {
					$field = wp_parse_args(
						$field,
						array(
							'type'            => '',
							'data_type'       => '',
							'data_validation' => '',
						)
					);

					$field_unique_id     = "{$condition_type}_{$field_name}";
					$validation_type     = empty( $field['data_validation'] ) ? $condition_type : $field['data_validation'];
					$rule[ $field_name ] = isset( $field['default'] ) ? $field['default'] : '';

					if ( isset( $condition[ $field_unique_id ] ) ) {
						$value = wc_clean( $condition[ $field_unique_id ] );

						if ( has_filter( "woocommerce_shiptastic_shipping_method_rule_validate_{$validation_type}" ) ) {
							$value = apply_filters( "woocommerce_shiptastic_shipping_method_rule_validate_{$validation_type}", $value, $field, $condition_type, $this );
						} elseif ( 'weight' === $validation_type ) {
							$unit = get_option( 'woocommerce_weight_unit', 'kg' );

							if ( in_array( $unit, array( 'kg', 'g' ), true ) ) {
								$decimals = 3;

								if ( 'g' === $unit ) {
									$decimals = 0;
								}

								$value = (float) wc_format_decimal( $value, $decimals, true );
							} else {
								$value = (float) wc_format_decimal( $value, false, true );
							}
						} elseif ( 'price' === $field['data_type'] ) {
							$value = (float) wc_format_decimal( $value, wc_get_price_decimals(), true );
						} elseif ( 'decimal' === $field['data_type'] ) {
							$value = (float) wc_format_decimal( $value );
						} elseif ( 'array' === $field['data_type'] ) {
							$value = (array) $value;
						}

						$new_condition[ $field_name ] = $value;
					}
				}

				$rule['conditions'][ "condition_{$condition_id}" ] = $new_condition;
			}

			if ( ! isset( $rules[ $packaging ] ) ) {
				$rules[ $packaging ] = array();
			}

			$rules[ $packaging ][ "rule_{$rule_id}" ] = $rule;
		}

		return $rules;
	}

	protected function generate_shipping_rules_html( $option_name, $option ) {
		ob_start();
		$field_key       = $this->get_field_key( 'shipping_rules' );
		$condition_types = $this->get_condition_types();
		?>
		<table class="widefat wc-shiptastic-shipping-rules">
			<thead>
				<tr>
					<th class="sort"></th>
					<th class="cb">
						<input class="wc-shiptastic-shipping-rules-cb-all" name="shipping_rules_cb_all" type="checkbox" value="" />
					</th>
					<th class="packaging">
						<?php echo esc_html_x( 'Packaging', 'shipments', 'woocommerce-germanized' ); ?>
					</th>
					<th class="conditions">
						<?php echo esc_html_x( 'Conditions', 'shipments', 'woocommerce-germanized' ); ?>
					</th>
					<th class="costs">
						<?php echo esc_html_x( 'Costs', 'shipments', 'woocommerce-germanized' ); ?>
					</th>
					<th class="actions">
						<?php echo esc_html_x( 'Actions', 'shipments', 'woocommerce-germanized' ); ?>
					</th>
				</tr>
			</thead>
			<?php foreach ( $this->get_packaging_list() as $name => $title ) : ?>
				<tbody class="wc-shiptastic-shipping-rules-rows" data-edit-url="<?php echo esc_url( $this->get_packaging_edit_url( $name ) ); ?>" data-title="<?php echo esc_html( $title ); ?>" data-help-tip="<?php echo esc_html( $this->get_packaging_help_tip( $name ) ); ?>" data-packaging="<?php echo esc_attr( $name ); ?>" id="wc-shiptastic-shipping-rules-packaging-<?php echo esc_attr( $name ); ?>">
				</tbody>
			<?php endforeach; ?>
			<tfoot>
				<tr>
					<th colspan="7">
						<select class="wc-enhanced-select new-shipping-packaging">
							<?php foreach ( $this->get_packaging_list() as $name => $title ) : ?>
								<option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $title ); ?></option>
							<?php endforeach; ?>
						</select>
						<a class="button button-primary wc-shiptastic-shipping-rule-add" href="#"><?php echo esc_html_x( 'Add new', 'shipments', 'woocommerce-germanized' ); ?></a>
						<a class="button button-secondary wc-shiptastic-shipping-rule-remove disabled" href="#"><?php echo esc_html_x( 'Remove selected', 'shipments', 'woocommerce-germanized' ); ?></a>
					</th>
				</tr>
			</tfoot>
		</table>
		<script type="text/html" id="tmpl-wc-shiptastic-shipping-rules-packaging-info">
			<tr class="wc-shiptastic-shipping-rules-packaging-info">
				<td colspan="7"><p class="packaging-info"><a class="packaging-title" href="#" target="_blank"></a><span class="woocommerce-help-tip" tabindex="0" aria-label="" data-tip=""></span></p></td>
			</tr>
		</script>
		<script type="text/html" id="tmpl-wc-shiptastic-shipping-rules-row">
			<tr data-id="{{ data.rule_id }}" class="shipping-rule">
				<td class="sort ui-sortable-handle">
					<div class="wc-item-reorder-nav wc-stc-shipping-rules-reorder-nav">
					</div>
				</td>
				<td class="cb">
					<input class="cb" name="<?php echo esc_attr( $field_key ); ?>[cb][{{ data.rule_id }}]" type="checkbox" value="{{ data.rule_id }}" data-attribute="cb" title="<?php echo esc_attr_x( 'Rule:', 'shipments', 'woocommerce-germanized' ); ?> {{ data.rule_id }}" />
				</td>
				<td class="packaging">
					<select class="wc-enhanced-select shipping-packaging" name="<?php echo esc_attr( $field_key ); ?>[packaging][{{ data.rule_id }}]" data-attribute="packaging">
						<?php foreach ( $this->get_packaging_list() as $name => $title ) : ?>
							<option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $title ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td class="conditions">
					<table class="inner-conditions">
						<tbody class="wc-shiptastic-shipping-rules-condition-rows" id="wc-shiptastic-shipping-rules-{{ data.rule_id }}-condition-rows" data-rule="{{ data.rule_id }}">
						</tbody>
					</table>
				</td>
				<td class="costs">
					<p class="form-field">
						<label><?php echo esc_html_x( 'Rule cost is', 'shipments', 'woocommerce-germanized' ); ?></label>
						<input type="text" class="short wc_input_price" name="<?php echo esc_attr( $field_key ); ?>[costs][{{ data.rule_id }}]" value="{{ data.costs }}" data-attribute="costs">
						<span class="description"><?php echo wp_kses_post( get_woocommerce_currency_symbol() ); ?></span>
					</p>
				</td>
				<td class="actions">
					<a class="button wc-stc-shipment-action-button shipping-rule-add add" href="#"></a>
					<a class="button wc-stc-shipment-action-button shipping-rule-remove delete" href="#"></a>
				</td>
			</tr>
		</script>
		<script type="text/html" id="tmpl-wc-shiptastic-shipping-rules-condition-row">
			<?php
			$condition_type_columns = array();
			?>
			<tr data-condition="{{ data.condition_id }}" class="rule-condition">
				<td>
					<div class="conditions-columns">
					<div class="conditions-column conditions-when">
						<p class="form-field">
							<label><?php echo esc_html_x( 'When', 'shipments', 'woocommerce-germanized' ); ?></label>
							<select name="<?php echo esc_attr( $field_key ); ?>[conditions][{{ data.rule_id }}][{{ data.condition_id }}][type]" class="shipping-rules-condition-type" data-attribute="type">
								<?php foreach ( $condition_types as $condition_type => $condition_type_data ) : ?>
									<option value="<?php echo esc_attr( $condition_type ); ?>"><?php echo esc_html( $condition_type_data['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
					</div>

					<?php
					foreach ( $condition_types as $condition_type => $condition_type_data ) :
						$operators = $condition_type_data['operators'];
						?>
						<?php if ( ! empty( $operators ) ) : ?>
							<div class="conditions-column conditions-operator">
								<div class="shipping-rule-condition-type-operator shipping-rules-condition-type-container shipping-rules-condition-type-container-<?php echo esc_attr( $condition_type ); ?>" data-condition-type="<?php echo esc_attr( $condition_type ); ?>">
									<p class="form-field">
										<label>&nbsp;</label>
										<select name="<?php echo esc_attr( $field_key ); ?>[conditions][{{ data.rule_id }}][{{ data.condition_id }}][operator][<?php echo esc_attr( $condition_type ); ?>]" class="shipping-rules-condition-operator" data-attribute="operator">
											<?php foreach ( $operators as $operator ) : ?>
												<option value="<?php echo esc_attr( $operator ); ?>"><?php echo esc_html( $this->get_conditional_operator( $operator )['label'] ); ?></option>
											<?php endforeach; ?>
										</select>
									</p>
								</div>
							</div>
						<?php endif; ?>
						<?php
						$index = 0;

						foreach ( $condition_type_data['fields'] as $field_name => $field ) {
							$column_key = ++$index;
							$column_key = isset( $field['column'] ) ? $field['column'] : $column_key;

							if ( ! isset( $condition_type_columns[ $column_key ] ) ) {
								$condition_type_columns[ $column_key ] = array();
							}

							if ( ! isset( $condition_type_columns[ $column_key ][ $condition_type ] ) ) {
								$condition_type_columns[ $column_key ][ $condition_type ] = array();
							}

							$condition_type_columns[ $column_key ][ $condition_type ][ $field_name ] = $field;
						}
					endforeach;
					?>

					<?php foreach ( $condition_type_columns as $column ) : ?>
						<div class="conditions-column">
							<?php foreach ( $column as $column_condition_type => $fields ) : ?>
								<?php
								foreach ( $fields as $field_name => $field ) :
									$field_unique_id = "{$column_condition_type}_{$field_name}";
									$data_type       = isset( $field['data_type'] ) ? $field['data_type'] : '';
									$data_type_class = $data_type;

									if ( 'price' === $data_type ) {
										$data_type_class = 'wc_input_price';
									}

									$field                      = wp_parse_args(
										$field,
										array(
											'name'    => $field_key . "[conditions][{{ data.rule_id }}][{{ data.condition_id }}][$field_unique_id]",
											'id'      => $field_key . '-' . $field_unique_id . '-{{ data.rule_id }}-{{ data.condition_id }}',
											'custom_attributes' => array(),
											'type'    => 'text',
											'class'   => '',
											'options' => array(),
											'value'   => '{{data.' . $field_name . '}}',
										)
									);
									$field['data_type']         = '';
									$field['class']             = $field['class'] . ' ' . $data_type_class;
									$field['custom_attributes'] = array_merge( $field['custom_attributes'], array( 'data-attribute' => $field_name ) );
									?>
									<div class="shipping-rules-condition-type-container shipping-rules-condition-type-container-<?php echo esc_attr( $column_condition_type ); ?>" data-condition-type="<?php echo esc_attr( $column_condition_type ); ?>">
										<?php
										if ( 'text' === $field['type'] ) {
											woocommerce_wp_text_input( $field );
										} elseif ( in_array( $field['type'], array( 'select', 'multiselect' ), true ) ) {
											$field['options'] = is_callable( $field['options'] ) ? call_user_func( $field['options'] ) : (array) $field['options'];

											if ( 'multiselect' === $field['type'] ) {
												$field['custom_attributes']['multiple'] = 'multiple';
												$field['name']                         .= '[]';
											}

											woocommerce_wp_select( $field );
										}
										?>
									</div>
								<?php endforeach; ?>
							<?php endforeach; ?>
						</div>
						<?php endforeach; ?>
						<div class="conditions-column conditions-actions">
							<p class="form-field">
								<label>&nbsp;</label>
								<a class="button wc-stc-shipment-action-button condition-add add" href="#"></a>
								<a class="button wc-stc-shipment-action-button condition-remove delete" href="#"></a>
							</p>
						</div>
					</div>
				</td>
			</tr>
		</script>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Update a single option.
	 *
	 * @param string $key Option key.
	 * @param mixed  $value Value to set.
	 * @return bool was anything saved?
	 */
	public function update_option( $key, $value = '' ) {
		if ( empty( $this->instance_settings ) ) {
			$this->init_instance_settings();
		}

		$this->instance_settings[ $key ] = $value;

		return update_option( $this->get_instance_option_key(), apply_filters( 'woocommerce_shipping_' . $this->id . '_instance_settings_values', $this->instance_settings, $this ), 'yes' );
	}
}
