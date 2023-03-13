<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Legal_Checkbox {

	private $id = '';

	private $settings = array(
		'admin_name'           => '',
		'admin_desc'           => '',
		'html_id'              => '',
		'html_name'            => '',
		'html_classes'         => array(),
		'html_wrapper_classes' => array(),
		'html_style'           => '',
		'hide_input'           => 'no',
		'is_mandatory'         => 'no',
		'is_shown'             => 'yes',
		'is_enabled'           => 'yes',
		'is_core'              => 'no',
		'refresh_fragments'    => 'no',
		'value'                => '1',
		'label'                => '',
		'label_args'           => array(),
		'template_name'        => 'checkboxes/default.php',
		'template_args'        => array(),
		'error_message'        => '',
		'priority'             => 10,
		'locations'            => array(),
		'supporting_locations' => array(),
		'show_for_categories'  => array(),
		'show_for_countries'   => array(),
	);

	public function __construct( $id, $args = array() ) {
		$this->set_id( $id );
		$this->update( $args );
	}

	public function __get( $key ) {
		if ( is_callable( array( $this, "get_{$key}" ) ) ) {
			return $this->{"get_{$key}"}();
		} elseif ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
		} else {
			return '';
		}
	}

	/**
	 * Update method for the settings array which tries to call the setters if available.
	 *
	 * @param array $args
	 */
	public function update( $args = array() ) {

		// Merge html classes to avoid core classes being overriden by empty option
		$merge = array( 'html_classes', 'html_wrapper_classes', 'label_args' );

		foreach ( $merge as $merge_key ) {
			if ( isset( $args[ $merge_key ] ) ) {
				$getter             = 'get_' . $merge_key;
				$args[ $merge_key ] = array_merge( $this->$getter(), $args[ $merge_key ] );
			}
		}

		foreach ( $args as $prop => $value ) {
			try {
				$setter = "set_$prop";
				if ( ! is_null( $value ) && is_callable( array( $this, $setter ) ) ) {
					$reflection = new ReflectionMethod( $this, $setter );

					if ( $reflection->isPublic() ) {
						$this->{$setter}( $value );
					}
				} else {
					$this->settings[ $prop ] = $value;
				}
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}
	}

	/**
	 * Returns an option of the current checkbox from the database.
	 *
	 * @param $key
	 * @param string $default
	 *
	 * @return array|string
	 */
	public function get_option( $key, $default = '' ) {
		$options = WC_GZD_Legal_Checkbox_Manager::instance()->get_options();
		$value   = $default;

		if ( isset( $options[ $this->get_id() ] ) && isset( $options[ $this->get_id() ][ $key ] ) ) {
			$value = $options[ $this->get_id() ][ $key ];
		}

		if ( is_array( $value ) ) {
			$value = array_map( 'stripslashes', $value );
		} elseif ( ! is_null( $value ) ) {
			$value = stripslashes( $value );
		}

		return $value;
	}

	public function update_option( $key, $value ) {
		$options = WC_GZD_Legal_Checkbox_Manager::instance()->get_options();

		// Do not allow overriding id
		if ( 'id' === $key ) {
			return false;
		}

		$options[ $this->get_id() ][ $key ] = $value;
		$this->settings[ $key ]             = $value;

		return WC_GZD_Legal_Checkbox_Manager::instance()->update_options( $options );
	}

	/**
	 * Unique identifier
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param string $identifier
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Returns the HTML id used for the input field.
	 *
	 * @return string
	 */
	public function get_html_id() {
		return $this->settings['html_id'];
	}

	/**
	 * @param string $html_id
	 */
	public function set_html_id( $html_id ) {
		$this->settings['html_id'] = $html_id;
	}

	/**
	 * Returns whether to show or not show the actual checkbox
	 *
	 * @return string yes or no
	 */
	public function get_hide_input() {
		return $this->settings['hide_input'];
	}

	/**
	 * @param bool $show_label_only
	 */
	public function set_hide_input( $hide_input ) {
		$this->settings['hide_input'] = wc_bool_to_string( $hide_input );
	}

	/**
	 * Whether to show or not show the actual checkbox
	 *
	 * @return bool
	 */
	public function hide_input() {
		return $this->get_hide_input() === 'yes';
	}

	/**
	 * HTML classes used for the checkbox
	 *
	 * @return array
	 */
	public function get_html_classes() {
		return $this->settings['html_classes'];
	}

	/**
	 * @param array $html_classes
	 */
	public function set_html_classes( $html_classes ) {
		$this->settings['html_classes'] = $html_classes;
	}

	/**
	 * HTML classes for the surrounding wrapper.
	 *
	 * @return array
	 */
	public function get_html_wrapper_classes() {
		$classes = $this->settings['html_wrapper_classes'];

		if ( ! is_array( $classes ) ) {
			$classes = array();
		}

		if ( $this->is_mandatory() ) {
			$classes = array_merge( $classes, array( 'validate-required' ) );
		}

		return array_unique( $classes );
	}

	/**
	 * @param array $html_wrapper_classes
	 */
	public function set_html_wrapper_classes( $html_wrapper_classes ) {
		$this->settings['html_wrapper_classes'] = $html_wrapper_classes;
	}

	/**
	 * HTML CSS style as string.
	 *
	 * @return string
	 */
	public function get_html_style() {
		return $this->settings['html_style'];
	}

	/**
	 * @param string $html_style
	 */
	public function set_html_style( $html_style ) {
		$this->settings['html_style'] = $html_style;
	}

	/**
	 * The name attribute of the checkbox element.
	 *
	 * @return string
	 */
	public function get_html_name() {
		return $this->settings['html_name'];
	}

	/**
	 * @param string $html_name
	 */
	public function set_html_name( $html_name ) {
		$this->settings['html_name'] = $html_name;
	}

	/**
	 * The value attribute of the checkbox element.
	 *
	 * @return string
	 */
	public function get_value() {
		return $this->settings['value'];
	}

	/**
	 * @param string $value
	 */
	public function set_value( $value ) {
		$this->settings['value'] = $value;
	}

	/**
	 * The checkbox label. By default returns the formatted label (placeholders being replaced).
	 *
	 * @param bool $plain Whether to return plain text without replacing placeholders or not
	 *
	 * @return string
	 */
	public function get_label( $plain = false ) {
		if ( $plain ) {
			return $this->settings['label'];
		} else {
			$label = $this->settings['label'];
			$label = wc_gzd_replace_label_shortcodes( $label, $this->get_label_args() );
			$id    = $this->get_id();

			/**
			 * Filter the label for a legal checkbox.
			 * `$id` equals the checkbox id.
			 *
			 * @param string $label The HTML label.
			 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
			 *
			 * @since 2.0.0
			 *
			 */
			return apply_filters( "woocommerce_gzd_legal_checkbox_{$id}_label", $label, $this );
		}
	}

	/**
	 * @param string $label
	 */
	public function set_label( $label ) {
		$this->settings['label'] = $label;
	}

	/**
	 * Placeholders (key => value) which are being applied to the label.
	 *
	 * @return array
	 */
	public function get_label_args() {
		$id = $this->get_id();

		/**
		 * Filter available label arguments for a legal checkbox.
		 * `$id` equals the checkbox id.
		 *
		 * @param string[] $label_args Label arguments as key => value.
		 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
		 *
		 * @since 3.1.6
		 */
		return apply_filters( "woocommerce_gzd_legal_checkbox_{$id}_label_args", $this->settings['label_args'], $this );
	}

	/**
	 * @param array $label_args
	 */
	public function set_label_args( $label_args ) {
		$this->settings['label_args'] = $label_args;
	}

	/**
	 * Error message being outputted when validation fails.
	 *
	 * @param bool $plain Whether to return plain text without replacing placeholders or not
	 *
	 * @return string
	 */
	public function get_error_message( $plain = false ) {
		$error_message = '';

		if ( $plain ) {
			$error_message = $this->settings['error_message'];

			if ( empty( $error_message ) ) {
				$error_message = sprintf( __( '%s is a mandatory field.', 'woocommerce-germanized' ), $this->get_admin_name() );
			}
		} else {
			$error_text = $this->settings['error_message'];
			$error_text = wc_gzd_replace_label_shortcodes( $error_text, $this->get_label_args() );
			$id         = $this->get_id();

			/**
			 * Filter the error message for a legal checkbox.
			 * `$id` equals the checkbox id.
			 *
			 * @param string $error_text The error message.
			 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
			 *
			 * @since 2.0.0
			 *
			 */
			$error_message = apply_filters( "woocommerce_gzd_legal_checkbox_{$id}_error_text", $error_text, $this );

			if ( empty( $error_message ) ) {
				$error_message = sprintf( __( '%s is a mandatory field.', 'woocommerce-germanized' ), $this->get_admin_name() );
			}
		}

		return $error_message;
	}

	/**
	 * @param string $error_message
	 */
	public function set_error_message( $error_message ) {
		$this->settings['error_message'] = $error_message;
	}

	/**
	 * The printing order (from low to high)
	 *
	 * @return int
	 */
	public function get_priority() {
		return $this->settings['priority'];
	}

	/**
	 * @param int $priority
	 */
	public function set_priority( $priority ) {
		$this->settings['priority'] = $priority;
	}

	/**
	 * The template location.
	 *
	 * @return string
	 */
	public function get_template_name() {
		return $this->settings['template_name'];
	}

	/**
	 * @param string $template_name
	 */
	public function set_template_name( $template_name ) {
		$this->settings['template_name'] = $template_name;
	}

	/**
	 * Arguments passed to the template file in key => value pairs.
	 *
	 * @return array
	 */
	public function get_template_args() {
		return $this->settings['template_args'];
	}

	/**
	 * @param array $template_args
	 */
	public function set_template_args( $template_args ) {
		$this->settings['template_args'] = $template_args;
	}

	/**
	 * The locations where the checkbox is being outputted.
	 *
	 * @return array
	 */
	public function get_locations() {
		return $this->settings['locations'];
	}

	/**
	 * @param array $locations
	 */
	public function set_locations( $locations ) {
		$this->settings['locations'] = $locations;
	}

	/**
	 * Locations being supported by the current checkbox.
	 *
	 * @return array
	 */
	public function get_supporting_locations() {
		return $this->settings['supporting_locations'];
	}

	public function set_supporting_locations( $locations ) {
		$this->settings['supporting_locations'] = $locations;
	}

	/**
	 * Product categories to show the checkbox for.
	 *
	 * @return array
	 */
	public function get_show_for_categories() {
		return $this->settings['show_for_categories'];
	}

	public function set_show_for_categories( $category_ids ) {
		$category_ids = array_map( 'absint', (array) $category_ids );

		$this->settings['show_for_categories'] = array_filter( $category_ids );
	}

	public function show_for_category( $category_id ) {
		return in_array( absint( $category_id ), $this->get_show_for_categories(), true );
	}

	/**
	 * Countries show the checkbox for.
	 *
	 * @return array
	 */
	public function get_show_for_countries() {
		return $this->settings['show_for_countries'];
	}

	public function show_for_country( $country ) {
		return in_array( $country, $this->get_show_for_countries(), true );
	}

	public function set_show_for_countries( $countries ) {
		$countries = (array) $countries;

		$this->settings['show_for_countries'] = array_filter( $countries );
	}

	/**
	 * Whether the checkbox is enabled or not.
	 *
	 * @return string yes or no
	 */
	public function get_is_enabled() {
		return $this->settings['is_enabled'];
	}

	/**
	 * Whether the checkbox is enabled or not.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->get_is_enabled() === 'yes';
	}

	/**
	 * @param bool $enabled
	 */
	public function set_is_enabled( $enabled ) {
		$this->settings['is_enabled'] = wc_bool_to_string( $enabled );
	}

	/**
	 * Whether the checkbox is to be printed or not.
	 *
	 * @return string yes or no
	 */
	public function get_is_shown() {
		return $this->settings['is_shown'];
	}

	/**
	 * Whether the checkbox is to be printed or not.
	 *
	 * @return bool
	 */
	public function is_shown() {
		return $this->get_is_shown() === 'yes';
	}

	/**
	 * @param bool $show
	 */
	public function set_is_shown( $show ) {
		$this->settings['is_shown'] = wc_bool_to_string( $show );
	}

	/**
	 * Whether the checkbox is mandatory or not.
	 *
	 * @return string yes or no
	 */
	public function get_is_mandatory() {
		return $this->settings['is_mandatory'];
	}

	/**
	 * Whether the checkbox is mandatory or not.
	 *
	 * @return bool
	 */
	public function is_mandatory() {
		return ( $this->get_is_mandatory() === 'yes' && ! $this->hide_input() );
	}

	/**
	 * @param bool $mandatory
	 */
	public function set_is_mandatory( $mandatory ) {
		$this->settings['is_mandatory'] = wc_bool_to_string( $mandatory );
	}

	/**
	 * Whether the checkbox shall be refresh via checkout fragments or not.
	 *
	 * @return string yes or no
	 */
	public function get_refresh_fragments() {
		return $this->settings['refresh_fragments'];
	}

	/**
	 * Whether the checkbox shall be refresh via checkout fragments or not.
	 *
	 * @return bool.
	 */
	public function do_refresh_fragments() {
		return $this->get_refresh_fragments() === 'yes';
	}

	/**
	 * @param bool $refresh_fragments
	 */
	public function set_refresh_fragments( $refresh_fragments ) {
		$this->settings['refresh_fragments'] = wc_bool_to_string( $refresh_fragments );
	}

	/**
	 * Whether the checkbox is a core checkbox or not.
	 *
	 * @return string yes or no
	 */
	public function get_is_core() {
		return $this->settings['is_core'];
	}

	/**
	 * Whether the checkbox is a core checkbox or not.
	 *
	 * @return bool.
	 */
	public function is_core() {
		return $this->get_is_core() === 'yes';
	}

	/**
	 * @param bool $is_core
	 */
	public function set_is_core( $is_core ) {
		$this->settings['is_core'] = wc_bool_to_string( $is_core );
	}

	/**
	 * The name being shown within the admin UI.
	 *
	 * @return string
	 */
	public function get_admin_name() {
		return $this->settings['admin_name'];
	}

	/**
	 * @param string $admin_name
	 */
	public function set_admin_name( $admin_name ) {
		$this->settings['admin_name'] = $admin_name;
	}

	/**
	 * The description being shown within the admin UI.
	 *
	 * @return string
	 */
	public function get_admin_desc() {
		return $this->settings['admin_desc'];
	}

	/**
	 * @param string $admin_desc
	 */
	public function set_admin_desc( $admin_desc ) {
		$this->settings['admin_desc'] = $admin_desc;
	}

	/**
	 * Whether the current checkbox is hidden.
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return ( ! $this->is_shown() );
	}

	/**
	 * Whether the current checkbox shall be printed or not.
	 *
	 * @return bool
	 */
	public function is_printable() {
		return ( $this->is_enabled() && ! $this->is_hidden() );
	}

	/**
	 * Whether the current checkbox shall be validated or not.
	 *
	 * @return bool
	 */
	protected function is_validateable() {
		return ( $this->is_enabled() && ! $this->is_hidden() );
	}

	/**
	 * Whether the checkbox is new.
	 *
	 * @return bool
	 */
	public function is_new() {
		return $this->get_id() === 'new';
	}

	/**
	 * Render HTML classes.
	 *
	 * @param $classes
	 */
	public function render_classes( $classes ) {
		echo esc_attr( wc_gzd_get_html_classes( $classes ) );
	}

	/**
	 * Validate the checkbox.
	 *
	 * @param string $value
	 * @param string $location
	 *
	 * @return bool
	 */
	public function validate( $value = '', $location = 'checkout' ) {
		$value = wc_clean( $value );
		$id    = $this->get_id();

		if ( $this->is_validateable() && $this->is_mandatory() ) {
			if ( has_filter( "woocommerce_gzd_legal_checkbox_{$location}_{$id}_validate" ) ) {
				/**
				 * Filter whether a certain checkbox `$id` shall be validated for a certain
				 * location `$location`.
				 *
				 * @param bool $validate Whether to validate the checkbox or not.
				 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
				 *
				 * @since 2.0.0
				 *
				 */
				return apply_filters( "woocommerce_gzd_legal_checkbox_{$location}_{$id}_validate", true, $this );
			} elseif ( empty( $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Render the checkbox. Output a wrapper to make the checkbox refreshable even though it is not being printed.
	 */
	public function render() {
		echo '<div class="wc-gzd-checkbox-placeholder wc-gzd-checkbox-placeholder-' . esc_attr( $this->get_html_id() ) . '" data-checkbox="' . esc_attr( $this->get_id() ) . '">';

		if ( $this->is_printable() ) {
			wc_get_template( $this->get_template_name(), array_merge( array( 'checkbox' => $this ), $this->get_template_args() ) );
		}

		echo '</div>';
	}

	/**
	 * Returns the current checkbox' data.
	 *
	 * @return array
	 */
	public function get_data() {
		$data = array(
			'id' => $this->get_id(),
		);

		foreach ( $this->settings as $key => $value ) {
			$getter = 'get_' . $key;

			if ( is_callable( array( $this, $getter ) ) ) {
				$data[ $key ] = $this->$getter();
			} else {
				$data[ $key ] = $value;
			}
		}

		$data['location_titles'] = array();
		$titles                  = WC_GZD_Legal_Checkbox_Manager::instance()->get_locations();

		foreach ( $this->get_locations() as $location ) {
			$data['location_titles'][ $location ] = $titles[ $location ];
		}

		return $data;
	}

	/**
	 * Returns the prefix for a certain form field within the administration UI.
	 *
	 * @return string
	 */
	public function get_form_field_id_prefix() {
		return "woocommerce_gzd_checkboxes_{$this->get_id()}_";
	}

	/**
	 * Returns the id for a certain form field within the administration UI.
	 *
	 * @return string
	 */
	public function get_form_field_id( $key ) {
		return $this->get_form_field_id_prefix() . $key;
	}

	/**
	 * Returns form fields as array to be interpreted by WC_Admin_Settings.
	 *
	 * @return array
	 */
	public function get_form_fields() {
		$label_args   = array_keys( $this->get_label_args() );
		$placeholders = '';

		if ( ! empty( $label_args ) ) {
			$placeholders = implode( ', ', $label_args );
		}

		$locations            = WC_GZD_Legal_Checkbox_Manager::instance()->get_locations();
		$supporting_locations = array();

		foreach ( $this->get_supporting_locations() as $location ) {
			$supporting_locations[ $location ] = $locations[ $location ];
		}

		/**
		 * Filters legal checkbox settings before titles.
		 *
		 * @param array $settings Array containing settings.
		 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
		 *
		 * @since 2.0.0
		 *
		 */
		$options = apply_filters(
			'woocommerce_gzd_legal_checkbox_fields_before_titles',
			array(

				array(
					'title'   => __( 'Status', 'woocommerce-germanized' ),
					'type'    => 'gzd_toggle',
					'id'      => $this->get_form_field_id( 'is_enabled' ),
					'desc'    => __( 'Enable checkbox', 'woocommerce-germanized' ),
					'default' => wc_bool_to_string( $this->get_is_enabled() ),
				),

				array(
					'title'    => __( 'Name', 'woocommerce-germanized' ),
					'type'     => 'text',
					'id'       => $this->get_form_field_id( 'admin_name' ),
					'desc_tip' => true,
					'desc'     => __( 'Choose a name to identify your checkbox. Upon creating a new checkbox, this value is being used to generate the Id.', 'woocommerce-germanized' ),
					'default'  => $this->get_admin_name(),
				),

				array(
					'title'             => __( 'Id', 'woocommerce-germanized' ),
					'type'              => 'text',
					'id'                => $this->get_form_field_id( 'id' ),
					'desc_tip'          => true,
					'desc'              => __( 'The checkbox Id is the unique indentifier which is used to identify the checkbox within the code. Cannot be edited after creating the checkbox.', 'woocommerce-germanized' ),
					'default'           => $this->get_id(),
					'custom_attributes' => array( 'disabled' => 'disabled' ),
				),

				array(
					'title'    => __( 'Description', 'woocommerce-germanized' ),
					'type'     => 'text',
					'id'       => $this->get_form_field_id( 'admin_desc' ),
					'desc'     => __( 'Describe the use case of your checkbox.', 'woocommerce-germanized' ),
					'desc_tip' => true,
					'default'  => $this->get_admin_desc(),
				),

				array(
					'title'    => __( 'Label', 'woocommerce-germanized' ),
					'type'     => 'textarea',
					'id'       => $this->get_form_field_id( 'label' ),
					'css'      => 'width:100%; height: 65px;',
					'desc_tip' => __( 'Choose a label to be inserted next to the checkbox.', 'woocommerce-germanized' ),
					'desc'     => ! empty( $placeholders ) ? sprintf( __( 'You may use one of the following placeholders within the text: %s', 'woocommerce-germanized' ), '<code>' . $placeholders . '</code>' ) : '',
					'default'  => $this->get_label( true ),
				),

				array(
					'title'    => __( 'Error Message', 'woocommerce-germanized' ),
					'type'     => 'textarea',
					'id'       => $this->get_form_field_id( 'error_message' ),
					'css'      => 'width:100%; height: 65px;',
					'desc_tip' => __( 'Choose an error message to be shown when the user has not confirmed the checkbox.', 'woocommerce-germanized' ),
					'desc'     => ! empty( $placeholders ) ? sprintf( __( 'You may use one of the following placeholders within the text: %s', 'woocommerce-germanized' ), '<code>' . $placeholders . '</code>' ) : '',
					'default'  => $this->get_error_message( true ),
				),

				array(
					'title'   => __( 'Hide input', 'woocommerce-germanized' ),
					'type'    => 'gzd_toggle',
					'id'      => $this->get_form_field_id( 'hide_input' ),
					'desc'    => __( 'Do only show a label and hide the actual checkbox.', 'woocommerce-germanized' ),
					'default' => wc_bool_to_string( $this->get_hide_input() ),
				),

				array(
					'title'   => __( 'Mandatory', 'woocommerce-germanized' ),
					'type'    => 'gzd_toggle',
					'id'      => $this->get_form_field_id( 'is_mandatory' ),
					'desc'    => __( 'Mark the checkbox as mandatory.', 'woocommerce-germanized' ),
					'default' => wc_bool_to_string( $this->get_is_mandatory() ),
				),

				array(
					'title'   => __( 'Locations', 'woocommerce-germanized' ),
					'type'    => 'multiselect',
					'class'   => 'wc-enhanced-select',
					'id'      => $this->get_form_field_id( 'locations' ),
					'label'   => __( 'Choose where to display your checkbox.', 'woocommerce-germanized' ),
					'default' => $this->get_locations(),
					'options' => $supporting_locations,
				),

			),
			$this
		);

		$id = $this->get_id();

		/**
		 * Filters legal checkbox settings for `$id` before titles.
		 *
		 * @param array $settings Array containing settings.
		 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
		 *
		 * @since 2.0.0
		 *
		 */
		$options = apply_filters( "woocommerce_gzd_legal_checkbox_{$id}_fields_before_titles", $options, $this );

		array_unshift(
			$options,
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'checkbox_options',
			)
		);
		array_push(
			$options,
			array(
				'type' => 'sectionend',
				'id'   => 'checkbox_options',
			)
		);

		/**
		 * Filters legal checkbox settings.
		 *
		 * @param array $settings Array containing settings.
		 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
		 *
		 * @since 2.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_legal_checkbox_fields', $options, $this );
	}

	public function pre_update_option( $value, $old_value, $name ) {
		$name = str_replace( $this->get_form_field_id_prefix(), '', $name );
		$this->update_option( $name, $value );

		// Return old value to disable WP from saving the option
		return $old_value;
	}

	public function pre_get_option( $value, $name, $default = null ) {
		$name = str_replace( $this->get_form_field_id_prefix(), '', $name );

		return $this->get_option( $name, $default );
	}

	/**
	 * Outputs the admin fields within the settings screen.
	 */
	public function admin_options() {
		foreach ( $this->get_form_fields() as $field ) {
			if ( ! isset( $field['id'] ) || 'title' === $field['type'] ) {
				continue;
			}

			add_filter( 'pre_option_' . $field['id'], array( $this, 'pre_get_option' ), 10, 3 );
		}

		WC_Admin_Settings::output_fields( $this->get_form_fields() );

		foreach ( $this->get_form_fields() as $field ) {
			if ( ! isset( $field['id'] ) || 'title' === $field['type'] ) {
				continue;
			}

			remove_filter( 'pre_option_' . $field['id'], array( $this, 'pre_get_option' ), 10 );
		}
	}

	/**
	 * Saves the fields from the settings screen.
	 */
	public function save_fields() {
		foreach ( $this->get_form_fields() as $field ) {
			if ( ! isset( $field['id'] ) ) {
				continue;
			}

			add_filter( 'pre_update_option_' . $field['id'], array( $this, 'pre_update_option' ), 10, 3 );
		}

		WC_Admin_Settings::save_fields( $this->get_form_fields() );

		foreach ( $this->get_form_fields() as $field ) {
			if ( ! isset( $field['id'] ) ) {
				continue;
			}

			remove_filter( 'pre_update_option_' . $field['id'], array( $this, 'pre_update_option' ), 10 );
		}
	}
}


