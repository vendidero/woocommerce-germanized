<?php
/**
 * Admin View: SEPA Export
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready(function ($) {
		var form = $('#export-filters'),
			filters = form.find('.export-filters');
		filters.hide();
		form.find('input:radio').change(function () {
			switch ($(this).val()) {
				case 'sepa':
					$('#sepa-filters').slideDown();
					break;
			}
		});
	});
	//]]>
</script>
<p>
	<label>
		<input type="radio" name="content" value="sepa" id="sepa"/>
		<?php esc_html_e( 'Direct Debit', 'woocommerce-germanized' ); ?>
	</label>
</p>
<ul id="sepa-filters" class="export-filters">
	<li>
		<label>
			<?php esc_html_e( 'Start Date', 'woocommerce-germanized' ); ?>
			<input type="date" name="sepa_start_date" value=""/>
		</label>
		<label>
			<?php esc_html_e( 'End Date', 'woocommerce-germanized' ); ?>
			<input type="date" name="sepa_end_date" value=""/>
		</label>
		<label>
			<?php esc_html_e( 'Unpaid only', 'woocommerce-germanized' ); ?>
			<input type="checkbox" name="sepa_unpaid_only" value="1"/>
		</label>
	</li>
</ul>
