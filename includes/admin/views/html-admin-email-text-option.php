<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Custom attribute handling.
$custom_attributes = array();

if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
	foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
		$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
	}
}

?>

<table class="form-table">
	<tr valign="top">
		<th scope="row">
			<label for="<?php echo esc_attr( $args['id'] ); ?>"><?php echo $args['label']; ?> <?php echo wc_gzd_help_tip( $args['desc'] ); ?></label>
		</th>
		<td class="forminp">
			<textarea id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['id'] ); ?>" style="height: 65px;" placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" <?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>><?php echo esc_textarea( $args['value'] ); ?></textarea>
		</td>
	</tr>
</table>
