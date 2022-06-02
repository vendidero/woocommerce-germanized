<?php
/**
 * Admin View: Settings default sidebar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="wc-gzd-admin-settings-sidebar-inner sticky">
	<h3>100% Gestaltungsfreiheit</h3>
	<div class="wc-gzd-sidebar-img">
		<a href="https://vendidero.de/woocommerce-germanized" target="_blank">
			<img class="browser" src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/new-checkbox.png"/>
		</a>
	</div>
	<p>Mit der <span class="wc-gzd-pro">pro</span> Version von Germanized bekommst du jetzt noch mehr
		Gestaltungsfreiheit für deine Checkboxen.
		Über unsere Oberfläche kannst du einfach eigene Checkboxen anlegen, bearbeiten oder löschen. Zusätzlich fügt
		Germanized Pro weitere Optionen für die Anpassung der HTML-Elemente hinzu.</p>
	<div class="wc-gzd-sidebar-action">
		<a class="button button-primary wc-gzd-button" href="https://vendidero.de/woocommerce-germanized" target="_blank">jetzt entdecken</a>
		<span class="small">ab 79 € inkl. Mwst. und 1 Jahr Updates & Support!</span>
	</div>
</div>
