<?php
/**
 * Shipment label HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */
defined( 'ABSPATH' ) || exit;

use Vendidero\Germanized\DHL\Package;

$default_args = wc_gzd_dhl_get_label_default_args( $dhl_order, $shipment );
?>

<form action="" method="post" class="wc-gzd-create-shipment-label-form">

<?php woocommerce_wp_select( array(
	'id'          		=> 'dhl_label_dhl_product',
	'label'       		=> _x( 'DHL Product', 'dhl', 'woocommerce-germanized' ),
	'description'		=> '',
	'options'			=> wc_gzd_dhl_get_products( $shipment->get_country() ),
	'value'             => isset( $default_args['dhl_product'] ) ? $default_args['dhl_product'] : '',
) ); ?>

<?php if ( $dhl_order->has_cod_payment() ) : ?>
	<?php woocommerce_wp_text_input( array(
		'id'          		=> 'dhl_label_cod_total',
		'class'          	=> 'wc_input_decimal',
		'label'       		=> _x( 'COD Amount', 'dhl', 'woocommerce-germanized' ),
		'placeholder' 		=> '',
		'description'		=> '',
		'value'       		=> isset( $default_args['cod_total'] ) ? $default_args['cod_total'] : '',
	) ); ?>

    <input type="hidden" name="dhl_label_cod_includes_additional_total" value="<?php echo ( isset( $default_args['cod_includes_additional_total'] ) ? $default_args['cod_includes_additional_total'] : '' ); ?>" />
<?php endif; ?>

<?php if ( Package::is_crossborder_shipment( $shipment->get_country() ) ) : ?>
	<?php woocommerce_wp_select( array(
		'id'          		=> 'dhl_label_duties',
		'label'       		=> _x( 'Duties', 'dhl', 'woocommerce-germanized' ),
		'description'		=> '',
		'value'       		=> isset( $default_args['duties'] ) ? $default_args['duties'] : '',
		'options'			=> wc_gzd_dhl_get_duties(),
	) ); ?>
<?php endif; ?>

<?php if ( Package::base_country_supports( 'services' ) && Package::is_shipping_domestic( $shipment->get_country() ) ) :

	$preferred_days     = array();
	$preferred_times    = array();

	try {
		$preferred_day_options = Package::get_api()->get_preferred_available_days( $shipment->get_postcode() );

		if ( $preferred_day_options ) {
			$preferred_days  = $preferred_day_options;
		}
	} catch( Exception $e ) {}
	?>

	<div class="columns">
		<div class="column <?php echo ( isset( $default_args['preferred_time'] ) ) ? 'col-6' : 'col-12'; ?>">
			<?php woocommerce_wp_select( array(
				'id'          		=> 'dhl_label_preferred_day',
				'label'       		=> _x( 'Preferred Day', 'dhl', 'woocommerce-germanized' ),
				'description'		=> '',
				'value'       		=> isset( $default_args['preferred_day'] ) ? $default_args['preferred_day'] : '',
				'options'			=> wc_gzd_dhl_get_preferred_days_select_options( $preferred_days, ( isset( $default_args['preferred_day'] ) ? $default_args['preferred_day'] : '' ) ),
			) ); ?>
		</div>
		<?php if ( isset( $default_args['preferred_time'] ) ) : ?>
			<div class="column col-6">
				<?php woocommerce_wp_select( array(
					'id'          		=> 'dhl_label_preferred_time',
					'label'       		=> _x( 'Preferred Time', 'dhl', 'woocommerce-germanized' ),
					'description'		=> '',
					'value'       		=> $default_args['preferred_time'],
					'options'			=> wc_gzd_dhl_get_preferred_times_select_options( array( $default_args['preferred_time'] => $default_args['preferred_time'] ) ),
				) ); ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $dhl_order->has_preferred_location() ) : ?>
		<?php woocommerce_wp_text_input( array(
			'id'          		=> 'dhl_label_preferred_location',
			'label'       		=> _x( 'Preferred Location', 'dhl', 'woocommerce-germanized' ),
			'placeholder' 		=> '',
			'description'		=> '',
			'value'       		=> isset( $default_args['preferred_location'] ) ? $default_args['preferred_location'] : '',
			'custom_attributes'	=> array( 'maxlength' => '80' )
		) ); ?>
	<?php endif; ?>

	<?php if ( $dhl_order->has_preferred_neighbor() ) : ?>
		<?php woocommerce_wp_text_input( array(
			'id'          		=> 'dhl_label_preferred_neighbor',
			'label'       		=> _x( 'Preferred Neighbor', 'dhl', 'woocommerce-germanized' ),
			'placeholder' 		=> '',
			'description'		=> '',
			'value'       		=> isset( $default_args['preferred_neighbor'] ) ? $default_args['preferred_neighbor'] : '',
			'custom_attributes'	=> array( 'maxlength' => '80' )
		) ); ?>
	<?php endif; ?>

	<?php woocommerce_wp_checkbox( array(
		'id'          		=> 'dhl_label_has_inlay_return',
		'label'       		=> _x( 'Create inlay return label', 'dhl', 'woocommerce-germanized' ),
		'class'             => 'checkbox show-if-trigger',
		'custom_attributes' => array( 'data-show-if' => '.show-if-has-return' ),
		'desc_tip'          => true,
		'value'             => isset( $default_args['has_inlay_return'] ) ? wc_bool_to_string( $default_args['has_inlay_return'] ) : 'no',
		'wrapper_class'     => 'form-field-checkbox'
	) ); ?>

	<div class="show-if show-if-has-return">
		<div class="columns">
			<div class="column col-12">
				<?php woocommerce_wp_text_input( array(
					'id'          		=> 'dhl_label_return_address[name]',
					'label'       		=> _x( 'Name', 'dhl', 'woocommerce-germanized' ),
					'placeholder' 		=> '',
					'description'		=> '',
					'value'             => isset( $default_args['return_address']['name'] ) ? $default_args['return_address']['name'] : '',
				) ); ?>
			</div>
		</div>
		<?php woocommerce_wp_text_input( array(
			'id'          		=> 'dhl_label_return_address[company]',
			'label'       		=> _x( 'Company', 'dhl', 'woocommerce-germanized' ),
			'placeholder' 		=> '',
			'description'		=> '',
			'value'             => isset( $default_args['return_address']['company'] ) ? $default_args['return_address']['company'] : '',
		) ); ?>
		<div class="columns">
			<div class="column col-9">
				<?php woocommerce_wp_text_input( array(
					'id'          		=> 'dhl_label_return_address[street]',
					'label'       		=> _x( 'Street', 'dhl', 'woocommerce-germanized' ),
					'placeholder' 		=> '',
					'description'		=> '',
					'value'             => isset( $default_args['return_address']['street'] ) ? $default_args['return_address']['street'] : '',
				) ); ?>
			</div>
			<div class="column col-3">
				<?php woocommerce_wp_text_input( array(
					'id'          		=> 'dhl_label_return_address[street_number]',
					'label'       		=> _x( 'Street No', 'dhl', 'woocommerce-germanized' ),
					'placeholder' 		=> '',
					'description'		=> '',
					'value'             => isset( $default_args['return_address']['street_number'] ) ? $default_args['return_address']['street_number'] : '',
				) ); ?>
			</div>
		</div>
		<div class="columns">
			<div class="column col-6">
				<?php woocommerce_wp_text_input( array(
					'id'          		=> 'dhl_label_return_address[postcode]',
					'label'       		=> _x( 'Postcode', 'dhl', 'woocommerce-germanized' ),
					'placeholder' 		=> '',
					'description'		=> '',
					'value'             => isset( $default_args['return_address']['postcode'] ) ? $default_args['return_address']['postcode'] : '',
				) ); ?>
			</div>
			<div class="column col-6">
				<?php woocommerce_wp_text_input( array(
					'id'          		=> 'dhl_label_return_address[city]',
					'label'       		=> _x( 'City', 'dhl', 'woocommerce-germanized' ),
					'placeholder' 		=> '',
					'description'		=> '',
					'value'             => isset( $default_args['return_address']['city'] ) ? $default_args['return_address']['city'] : '',
				) ); ?>
			</div>
		</div>
		<div class="columns">
			<div class="column col-6">
				<?php woocommerce_wp_text_input( array(
					'id'          		=> 'dhl_label_return_address[phone]',
					'label'       		=> _x( 'Phone', 'dhl', 'woocommerce-germanized' ),
					'placeholder' 		=> '',
					'description'		=> '',
					'value'             => isset( $default_args['return_address']['phone'] ) ? $default_args['return_address']['phone'] : '',
				) ); ?>
			</div>
			<div class="column col-6">
				<?php woocommerce_wp_text_input( array(
					'id'          		=> 'dhl_label_return_address[email]',
					'label'       		=> _x( 'Email', 'dhl', 'woocommerce-germanized' ),
					'placeholder' 		=> '',
					'description'		=> '',
					'value'             => isset( $default_args['return_address']['email'] ) ? $default_args['return_address']['email'] : '',
				) ); ?>
			</div>
		</div>
	</div>

	<?php woocommerce_wp_checkbox( array(
		'id'          		=> 'dhl_label_codeable_address_only',
		'label'       		=> _x( 'Valid address only', 'dhl', 'woocommerce-germanized' ),
		'placeholder' 		=> '',
		'description'		=> '',
		'value'       		=> isset( $default_args['codeable_address_only'] ) ? wc_bool_to_string( $default_args['codeable_address_only'] ) : 'no',
		'wrapper_class'     => 'form-field-checkbox'
	) ); ?>

	<p class="show-services-trigger">
		<a href="#" class="show-further-services <?php echo ( ! empty( $default_args['services'] ) ? 'hide-default' : '' ); ?>">
			<span class="dashicons dashicons-plus"></span> <?php _ex(  'More services', 'dhl', 'woocommerce-germanized' ); ?>
		</a>
		<a class="show-fewer-services <?php echo ( empty( $default_args['services'] ) ? 'hide-default' : '' ); ?>" href="#">
			<span class="dashicons dashicons-minus"></span> <?php _ex(  'Fewer services', 'dhl', 'woocommerce-germanized' ); ?>
		</a>
	</p>

	<div class="<?php echo ( empty( $default_args['services'] ) ? 'hide-default' : '' ); ?> show-if-further-services">

		<?php woocommerce_wp_select( array(
			'id'          		=> 'dhl_label_visual_min_age',
			'label'       		=> _x( 'Age check', 'dhl', 'woocommerce-germanized' ),
			'description'		=> '',
			'value'       		=> isset( $default_args['visual_min_age'] ) ? $default_args['visual_min_age'] : '',
			'options'			=> wc_gzd_dhl_get_visual_min_ages(),
		) ); ?>

		<?php woocommerce_wp_checkbox( array(
			'id'          		=> 'dhl_label_service_GoGreen',
			'label'       		=> _x( 'GoGreen', 'dhl', 'woocommerce-germanized' ),
			'description'		=> '',
			'value'       		=> in_array( 'GoGreen', $default_args['services'] ) ? 'yes' : 'no',
			'wrapper_class'     => 'form-field-checkbox'
		) ); ?>

		<?php woocommerce_wp_checkbox( array(
			'id'          		=> 'dhl_label_service_AdditionalInsurance',
			'label'       		=> _x( 'Additional insurance', 'dhl', 'woocommerce-germanized' ),
			'description'       => '',
			'value'		        => in_array( 'AdditionalInsurance', $default_args['services'] ) ? 'yes' : 'no',
			'wrapper_class'     => 'form-field-checkbox'
		) ); ?>

        <?php if ( $dhl_order->supports_email_notification() ) : ?>

            <?php woocommerce_wp_checkbox( array(
                'id'          		=> 'dhl_label_service_ParcelOutletRouting',
                'label'       		=> _x( 'Retail outlet routing', 'dhl', 'woocommerce-germanized' ),
                'description'       => '',
                'value'		        => in_array( 'ParcelOutletRouting', $default_args['services'] ) ? 'yes' : 'no',
                'wrapper_class'     => 'form-field-checkbox'
            ) ); ?>

        <?php endif; ?>

        <?php if ( ! $dhl_order->has_preferred_neighbor() ) : ?>

            <?php woocommerce_wp_checkbox( array(
                'id'          		=> 'dhl_label_service_NoNeighbourDelivery',
                'label'       		=> _x( 'No neighbor', 'dhl', 'woocommerce-germanized' ),
                'description'       => '',
                'value'		        => in_array( 'NoNeighbourDelivery', $default_args['services'] ) ? 'yes' : 'no',
                'wrapper_class'     => 'form-field-checkbox'
            ) ); ?>

        <?php endif; ?>

		<?php woocommerce_wp_checkbox( array(
			'id'          		=> 'dhl_label_service_NamedPersonOnly',
			'label'       		=> _x( 'Named person only', 'dhl', 'woocommerce-germanized' ),
			'description'		=> '',
			'value'		        => in_array( 'NamedPersonOnly', $default_args['services'] ) ? 'yes' : 'no',
			'wrapper_class'     => 'form-field-checkbox'
		) ); ?>

		<?php woocommerce_wp_checkbox( array(
			'id'          		=> 'dhl_label_service_BulkyGoods',
			'label'       		=> _x( 'Bulky goods', 'dhl', 'woocommerce-germanized' ),
			'description'		=> '',
			'value'		        => in_array( 'BulkyGoods', $default_args['services'] ) ? 'yes' : 'no',
			'wrapper_class'     => 'form-field-checkbox'
		) ); ?>

		<?php woocommerce_wp_checkbox( array(
			'id'          		=> 'dhl_label_service_IdentCheck',
			'label'       		=> _x( 'Identity check', 'dhl', 'woocommerce-germanized' ),
			'description'		=> '',
			'class'             => 'checkbox show-if-trigger',
			'value'		        => in_array( 'IdentCheck', $default_args['services'] ) ? 'yes' : 'no',
			'custom_attributes' => array( 'data-show-if' => '.show-if-ident-check' ),
			'wrapper_class'     => 'form-field-checkbox'
		) ); ?>

		<div class="show-if show-if-ident-check">
			<?php woocommerce_wp_text_input( array(
				'id'          		=> 'dhl_label_ident_date_of_birth',
				'label'       		=> _x( 'Date of Birth', 'dhl', 'woocommerce-germanized' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $default_args['ident_date_of_birth'] ) ? $default_args['ident_date_of_birth'] : '',
				'custom_attributes' => array( 'pattern' => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])', 'maxlength' => 10 ),
				'class'				=> 'short date-picker'
			) ); ?>

			<?php woocommerce_wp_select( array(
				'id'          		=> 'dhl_label_ident_min_age',
				'label'       		=> _x( 'Minimum age', 'dhl', 'woocommerce-germanized' ),
				'description'		=> '',
				'value'       		=> isset( $default_args['ident_min_age'] ) ? $default_args['ident_min_age'] : '',
				'options'			=> wc_gzd_dhl_get_ident_min_ages(),
			) ); ?>
		</div>
	</div>
<?php elseif( Package::is_crossborder_shipment( $shipment->get_country ) ) : ?>

	<?php woocommerce_wp_checkbox( array(
		'id'          		=> 'dhl_label_service_Premium',
		'label'       		=> _x( 'Premium', 'dhl', 'woocommerce-germanized' ),
		'description'		=> '',
		'value'		        => in_array( 'Premium', $default_args['services'] ) ? 'yes' : 'no',
		'wrapper_class'     => 'form-field-checkbox'
	) ); ?>

	<?php woocommerce_wp_checkbox( array(
		'id'          		=> 'dhl_label_service_GoGreen',
		'label'       		=> _x( 'GoGreen', 'dhl', 'woocommerce-germanized' ),
		'description'		=> '',
		'value'       		=> in_array( 'GoGreen', $default_args['services'] ) ? 'yes' : 'no',
		'wrapper_class'     => 'form-field-checkbox'
	) ); ?>

<?php endif; ?>

</form>
