<?php
/**
 * Admin View: Settings pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$elements = array(
	'add_to_cart' => '#woocommerce_gzd_display_listings_add_to_cart',
	'vat_notice' => '#woocommerce_gzd_display_footer_vat_notice',
	'shipping_costs' => '#woocommerce_gzd_display_listings_shipping_costs',
	'unit_price' => '#woocommerce_gzd_unit_price_text',
	'display_checkout' => '#woocommerce_gzd_display_checkout_fallback',
	'pay_now' => '#woocommerce_gzd_order_pay_now_button',
);

?>

<ol class="tourbus-legs wc-gzd-tour" id="tour-settings-display">

	<li data-orientation="centered">
		<h2>WooCommerce Germanized Tour</h2>
		<p>Lerne jetzt schnell und einfach die ersten Schritte zur Konfiguration von WooCommerce Germanized kennen.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">Los geht's</a>
		<a class="button button-secondary" href="<?php echo WC_GZD_Admin::instance()->disable_tour_link( 'display' ); ?>" style="float: right">Nein, Danke</a>
	</li>

	<li data-el="<?php echo $elements[ 'add_to_cart' ]; ?>" data-orientation="bottom" data-width="700">
		<h2>Zum Warekorb Button</h2>
		<p>Das Anzeigen des zum Warenkorb Buttons in Produktlisten kann weitreichende Folgen haben (z.B. das damit verbundene Anzeigen des Versandkosten-Hinweises).
		Generell halten wir es für sinnvoll, den Warenkorb-Button in Produktlisten zu deaktivieren und damit den Besuchern zu ermöglichen, alle rechtlichen Hinweise nur auf der Produktseite präsentiert zu bekommen.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'vat_notice' ]; ?>" data-orientation="top">
		<h2>Footer-Hinweise</h2>
		<p>Unter Umständen kann es sinnvoll sein, "globale" Hinweise im Footer unterzubringen ("Alle Preise inkl. MwSt." - so macht es z.B. Zalando). Im Zweifelsfall solltest du diese Option mit deinem
		Anwalt besprechen.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'shipping_costs' ]; ?>" data-orientation="top">
		<h2>Hinweise und Preisauszeichnung</h2>
		<p>Hier wird es interessant. Stelle nun ein, welche rechtlichen Hinweise du in Produktlisten und auf der Produktdetailseite angezeigt bekommen möchtest.
		</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'unit_price' ]; ?>" data-orientation="top">
		<h2>Grundpreis</h2>
		<p>Der Grundpreis ist natürlich nicht für jeden Shop-Betreiber relevant. Für Shops, die auf Basis von Einheiten verkaufen umso mehr. 
		Passe hier die Anzeige des Grundpreises an. Für eine verfeinerte Darstellung kannst du auch die Platzhalter:</p>
		<ul>
			<li>{base_price} - der eigentliche Grundpreis</li>
			<li>{base} - die Basis z.B. 100</li>
			<li>{unit} - die <a target="_blank" href="<?php echo admin_url( 'edit-tags.php?taxonomy=product_unit&post_type=product' ); ?>" title="Einheiten verwalten">Einheit</a> z.B. kg.</li>
		</ul>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'display_checkout' ]; ?>" data-orientation="top">
		<h2>Fallback-Modus</h2>
		<p>Solltest du Darstellungsprobleme im Checkout haben (Beispiel: die Auswahl der Zahlungsart befindet sich fälschlicherweise unterhalb der Produktübersicht), kannst du diese Option
		nutzen, um das Überschreiben der, für den Checkout relevanten, <a href="http://docs.woothemes.com/document/template-structure/" target="_blank">WooCommerce Standard Template</a> durch dein Theme zu verhindern.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'pay_now' ]; ?>" data-orientation="top">
		<h2>Bezahlen-Button in E-Mails</h2>
		<p>WooCommerce Germanized sorgt dafür, dass dem Käufer direkt nach der Bestellung (d.h. nach Betätigen des Kaufen-Buttons) eine Bestellbestätigung zugestellt wird. 
		Das gilt auch für Einkäufe, die per PayPal (oder andere Zahlungsanbieter) getätigt werden. Für diesen Fall kannst du an dieser Stelle bewirken, dass der Kunde per Mail auch 
		noch einmal einen Link zum Bezahlen erhält (falls der Kunde die Zahlung nach der Bestellung nicht abgeschlossen hat).</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el=".submit" data-orientation="top">
		<h2>Einstellungen speichern</h2>
		<p>Wenn du deine Einstellungen angepasst hast, dann speichere sie über diesen Button.
		</p>
		<a class="button button-primary" href="<?php echo add_query_arg( array( 'section' => 'email' ), WC_GZD_Admin::instance()->disable_tour_link( 'display' ) ); ?>">Weiter zu den E-Mails</a>
	</li>

</ol>