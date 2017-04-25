<?php
/**
 * Admin View: Settings pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$elements = array(
	'attach_terms' => '#woocommerce_gzd_mail_attach_terms',
	'legal_terms' => '#woocommerce_gzdp_legal_page_terms_enabled',
);

if ( ! wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
	$e = array(
		'attach_terms' => '#s2id_woocommerce_gzd_mail_attach_terms',
	);

	$elements = array_merge( $elements, $e );
}

?>

<ol class="tourbus-legs wc-gzd-tour" id="tour-settings-email">

	<li data-orientation="centered">
		<h2>WooCommerce Germanized Tour</h2>
		<p>Lerne jetzt schnell und einfach die ersten Schritte zur Konfiguration von WooCommerce Germanized kennen.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">Los geht's</a>
		<a class="button button-secondary" href="<?php echo WC_GZD_Admin::instance()->disable_tour_link( 'email' ); ?>" style="float: right">Nein, Danke</a>
	</li>

	<li data-el="<?php echo $elements[ 'attach_terms' ]; ?>" data-orientation="bottom" data-width="500">
		<h2>Rechtstexte an E-Mails anhängen</h2>
		<p>An dieser Stelle kannst du für jeden Rechtstext (z.B. AGB, Widerufsbelehrung) auswählen, an welche WooCommerce E-Mails der Text angehängt werden soll.
		Per Drag & Drop kannst du die Reihenfolge der Rechtstexte in den E-Mails beeinflussen (z.B. zuerst AGB dann Widerrufsbelehrung).</p>
		<h3>Hinweis</h3>
		<p>Die hier auswählbaren WooCommerce E-Mails entsprechen den Bezeichnungen des jeweiligen Templates unter <a target="_blank" href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=email' ); ?>">E-Mails</a>. So ist "Neue Bestellung" nicht die E-Mail die an den Kunden versendet wird, sondern der Hinweis an den Admin.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li class="wc-gzd-tour-pro" data-el="<?php echo $elements[ 'legal_terms' ]; ?>" data-orientation="top" data-width="500">
		<h2><span class="wc-gzd-pro">pro</span> PDF Anhänge in E-Mails</h2>
		<p>
			Nutzer der Pro-Version von WooCommerce Germanized können optional PDF-Dateien anstatt reinem Text an die WooCommerce E-Mails anhängen.
			Die PDF-Dateien können automatisch, basierend auf dem Inhalt der jeweiligen Seite erzeugt oder manuell hinterlegt werden.
		</p>
		<a class="button button-primary tourbus-disable" href="<?php echo WC_GZD_Admin::instance()->disable_tour_link( '' ); ?>">Tour beenden</a>
		<?php if ( ! WC_germanized()->is_pro() ) : ?>
			<a class="button button-secondary" style="float:right" href="https://vendidero.de/woocommerce-germanized#pro" target="_blank">mehr zur <span class="wc-gzd-pro">pro</span> Version</a>
		<?php endif; ?>
	</li>

</ol>