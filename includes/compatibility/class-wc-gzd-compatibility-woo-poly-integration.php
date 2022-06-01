<?php

defined( 'ABSPATH' ) || exit;

/**
 * PolyLang Helper
 *
 * Specific configuration for PolyLang
 *
 * @class        WC_GZD_Compatibility_PolyLang
 * @category    Class
 * @author        vendidero
 */
class WC_GZD_Compatibility_Woo_Poly_Integration extends WC_GZD_Compatibility {

	private $pll_email_instance = null;
	private $order_emails       = array();
	private $other_emails       = array();

	public static function get_name() {
		return 'Hyyan WooCommerce Polylang Integration';
	}

	public static function get_path() {
		return 'woo-poly-integration/__init__.php';
	}

	public function early_execution() {

		$this->order_emails = array(
			'customer_paid_for_order',
			'customer_sepa_direct_debit_mandate',
		);

		$this->other_emails = array(
			'customer_new_account_activation',
			'customer_revocation',
		);

		add_filter( 'woo-poly.pages.list', array( $this, 'register_pages' ) );

		$this->setup_taxonomy_translation();
		$this->setup_emails();

		/**
		 * Hyyan WooCommerce Polylang Integration compatibility loaded.
		 *
		 * Fires after Germanized loaded it's Woo PolyLang compatibility script.
		 *
		 * @param WC_GZD_Compatibility_Woo_Poly_Integration $this The compatibility instance.
		 *
		 * @since 1.9.7
		 *
		 */
		do_action( 'woocommerce_gzd_polylang_compatibility_loaded', $this );
	}

	public function load() {
		// Add fields to enable metaSync
		add_filter( 'woo-poly.product.metaSync', array( $this, 'add_fields' ), 30 );
		// Remove variation cart description from disabled state
		add_filter( 'woo-poly.fieldsLockerVariableExcludeSelectors', array( $this, 'unlock_fields' ), 20, 1 );
	}

	public function unlock_fields( $fields ) {
		$fields[] = '[name^="variable_mini_desc"]';

		return $fields;
	}

	public function get_pll_email_instance() {
		if ( $this->pll_email_instance ) {
			return $this->pll_email_instance;
		}

		return false;
	}

	public function set_pll_email_instance( $instance ) {
		$this->pll_email_instance = $instance;
	}

	public function get_order_emails() {
		/**
		 * Filter to add additional order emails to PolyLang.
		 *
		 * @param array $order_mails Array containing additional email ids.
		 * @param WC_GZD_Compatibility_Woo_Poly_Integration $integration The integration instance.
		 *
		 * @since 1.8.5
		 *
		 */
		return apply_filters( 'woocommerce_gzd_polylang_order_emails', $this->order_emails, $this );
	}

	public function get_emails() {
		/**
		 * Filter to get emails relevant for PolyLang.
		 *
		 * @param array $mails Array containing additional email ids.
		 * @param WC_GZD_Compatibility_Woo_Poly_Integration $integration The integration instance.
		 *
		 * @since 1.8.5
		 *
		 */
		return apply_filters( 'woocommerce_gzd_polylang_emails', array_merge( $this->get_order_emails(), $this->other_emails ), $this );
	}

	public function setup_emails() {
		add_filter( 'woo-poly.Emails.translatableEmails', array( $this, 'register_emails' ), 10, 2 );
		add_action( 'woo-poly.Emails.translation', array( $this, 'translate_emails' ), 10, 1 );
		add_action( 'woo-poly.Emails.switchLanguage', array( $this, 'unload_textdomain' ), 10 );
		add_action( 'woo-poly.Emails.afterSwitchLanguage', array( $this, 'reload_textdomain' ), 10 );
	}

	public function unload_textdomain() {
		unload_textdomain( 'woocommerce-germanized' );
	}

	public function reload_textdomain() {
		WC_germanized()->load_plugin_textdomain();
	}

	public function translate_emails( $pll_mail_instance ) {
		$this->set_pll_email_instance( $pll_mail_instance );

		foreach ( $this->get_order_emails() as $mail_id ) {
			add_filter( 'woocommerce_email_subject_' . $mail_id, array( $this, 'translate_order_subject' ), 10, 2 );
			add_filter( 'woocommerce_email_heading_' . $mail_id, array( $this, 'translate_order_heading' ), 10, 2 );
		}
	}

	public function translate_order_subject( $subject, $object ) {
		$email_id = str_replace( 'woocommerce_email_subject_', '', current_filter() );
		$instance = $this->get_pll_email_instance();

		if ( is_callable( array( $instance, 'translateEmailStringToObjectLanguage' ) ) ) {
			return $instance->translateEmailStringToObjectLanguage( $subject, $object, 'subject', $email_id );
		} else {
			return $subject;
		}
	}

	public function translate_order_heading( $heading, $object ) {
		$email_id = str_replace( 'woocommerce_email_heading_', '', current_filter() );
		$instance = $this->get_pll_email_instance();

		if ( is_callable( array( $instance, 'translateEmailStringToObjectLanguage' ) ) ) {
			return $instance->translateEmailStringToObjectLanguage( $heading, $object, 'heading', $email_id );
		} else {
			return $heading;
		}
	}

	public function register_emails( $mails, $pll_mail_instance ) {
		return array_merge( $mails, $this->get_emails() );
	}

	private function setup_taxonomy_translation() {
		// For normal products
		add_action( 'pll_save_post', array( $this, 'translate_taxonomies' ), 250, 3 );
		// For variations
		add_action( 'woo-poly.product.variation.copyMeta', array( $this, 'translate_taxonomies_variations' ), 10, 4 );
	}

	public function translate_taxonomies_variations( $from, $to, $from_variable, $to_variable ) {
		$lang = isset( $_GET['new_lang'] ) ? wc_clean( wp_unslash( $_GET['new_lang'] ) ) : pll_get_post_language( $to_variable->get_id() ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->translate_product_taxonomies( $from, $to, $lang, pll_get_post_language( $to_variable->get_id() ) );
	}

	public function translate_taxonomies( $post_id, $post, $translations ) {

		// Check for post type
		if ( ! in_array( $post->post_type, array( 'product' ), true ) ) {
			return;
		}

		foreach ( $translations as $lang => $translation ) {

			if ( empty( $translation ) ) {
				continue;
			}

			$this->translate_product_taxonomies( $post_id, $translation, $lang );
		}
	}

	public function translate_product_taxonomies( $original_post_id, $new_post_id, $lang, $current_lang = '' ) {

		if ( empty( $current_lang ) ) {
			$current_lang = pll_get_post_language( $original_post_id );
		}

		// If the subject has not yet a language, use default language.
		if ( ! $current_lang ) {
			$current_lang = pll_default_language();
		}

		// Update germanized specific terms
		$meta_to_tax = array(
			'product_delivery_time' => false,
			'product_unit'          => array( '_unit' ),
			'product_price_label'   => array( '_sale_price_label', '_sale_price_regular_label' ),
		);

		foreach ( $meta_to_tax as $tax => $metas ) {

			$save_as_taxonomy = ( is_array( $metas ) ? false : true );
			$metas            = ( is_array( $metas ) ? $metas : array( $metas ) );

			foreach ( $metas as $meta_key ) {

				$term = false;

				if ( ! $save_as_taxonomy ) {
					$slug = get_post_meta( $original_post_id, $meta_key, true );

					if ( $slug ) {

						// Use get_terms because get_term_by is filtered by polylang and won't return translated term id if current language is set
						$terms = get_terms(
							array(
								'get'             => 'all',
								'number'          => 1,
								'taxonomy'        => $tax,
								'orderby'         => 'none',
								'suppress_filter' => true,
								'lang'            => $current_lang,
								'slug'            => $slug,
							)
						);

						if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
							$term = array_shift( $terms );
						}
					}
				} else {
					$terms = get_the_terms( $original_post_id, $tax );

					if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
						$term = array_shift( $terms );
					}
				}

				if ( $term ) {

					$term_id = $term->term_id;

					// Get the translated term id
					$translated_term_id = pll_get_term( $term_id, $lang );

					// Check whether translated term exists and get the object
					if ( $translated_term_id ) {
						$translated_term = get_term_by( 'id', $translated_term_id, $tax );

						if ( $translated_term && ! is_wp_error( $translated_term ) ) {
							$term = $translated_term;
						}
					}

					// Save translated slug version
					if ( $save_as_taxonomy ) {
						wp_set_object_terms( $new_post_id, $term->term_id, $tax );
					} else {
						update_post_meta( $new_post_id, $meta_key, $term->slug );
					}
				}
			}
		}
	}

	public function add_fields( $metas ) {

		$metas['unit_price'] = array(
			'name'  => _x( 'Unit Price Metas', 'polylang', 'woocommerce-germanized' ),
			'desc'  => _x( 'Note the last unit price field is the final unit price taking into account the effect of unit sale price', 'polylang', 'woocommerce-germanized' ),
			'metas' => array(
				'_unit_price',
				'_unit_price_sale',
				'_unit_price_regular',
				'_unit_price_auto',
				'_unit_product',
				'_unit_base',
				'_unit',
			),
		);

		$metas['sale_price_labels'] = array(
			'name'  => _x( 'Sale Price Labels', 'polylang', 'woocommerce-germanized' ),
			'desc'  => _x( 'Sale price labels used to mark old prices (e.g. Recommended Retail Price)', 'polylang', 'woocommerce-germanized' ),
			'metas' => array(
				'_sale_price_label',
				'_sale_price_regular_label',
			),
		);

		$metas['shipping']['metas'][] = '_free_shipping';

		// General
		$metas['general']['metas'][] = '_service';

		return $metas;
	}

	public function register_pages( $pages ) {
		$gzd_pages = array(
			'revocation',
			'data_security',
			'imprint',
			'payment_methods',
			'shipping_costs',
		);

		return array_merge( $pages, $gzd_pages );
	}
}
