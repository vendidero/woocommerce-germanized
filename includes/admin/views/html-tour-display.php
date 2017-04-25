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
	'checkout_legal' => '#woocommerce_gzd_display_checkout_legal_no_checkbox',
	'checkout_digital' => '#woocommerce_gzd_checkout_legal_digital_checkbox',
	'digital_types' => '#woocommerce_gzd_checkout_legal_digital_types',
	'legal_service' => '#woocommerce_gzd_checkout_legal_service_checkbox',
	'pay_now' => '#woocommerce_gzd_order_pay_now_button',
);

if ( ! wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
	$e = array(
		'digital_types' => '#s2id_woocommerce_gzd_checkout_legal_digital_types',
	);

	$elements = array_merge( $elements, $e );
}

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

	<li data-el="<?php echo $elements[ 'checkout_legal' ]; ?>" data-orientation="top">
		<h2>Checkbox in der Kasse</h2>
		<p>Einige Shops verzichten auf eine Checkbox zum Bestätigen der AGB und Widerrufsbelehrung und wählen stattdessen einen normalen Hinweis (s. z.B. Zalando).
		Den Hinweistext bzw. Checkbox-Text kannst du über die folgenden Optionen steuern. Mit den Platzhaltern {term_link}, {revocation_link} und {data_security_link} kannst du Links zu den entsprechenden 
		Rechtstexten einfügen (diese müssen natürlich in den Germanized Einstellungen unter <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized&section=display' ); ?>">Allgemein</a> hinterlegt sein).</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'checkout_digital' ]; ?>" data-orientation="top">
		<h2>Checkbox für digitale Produkte</h2>
		<p>Solltest du digitale Produkte verkaufen, möchtest du vielleicht verhindern, dass Käufer dieser Produkte weiterhin über ein 14-tägiges Widerrufsrecht verfügen.
		Sollte das der Fall sein, könnte jeder Käufer nach dem Download der Datei den Vertrag widerrufen. Aus diesem Grund, fügt Germanized eine Checkbox ein, die den Käufer
		dazu auffordert, auf das Widerrufsrecht zur verzichten (standardmäßig nur dann, wenn sicher herunterladbare Produkte im Warenkorb befinden).</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'digital_types' ]; ?>" data-orientation="top">
		<h2>Digitale Produkttypen</h2>
		<p>Standardmäßig wird der Hinweis bzgl. des Abtretens des Widerrufsrechts nur für herunterladbare Produkte angezeigt. Wenn du den Hinweis auch für andere Produkttypen (z.B. auch für virtuelle Produkte)
		aktivieren möchtest, kannst du dies hier auswählen. Mit den Standard-Produkttypen (wie einfaches Produkt) solltest du allerdings vorsichtig sein, da der Hinweis dann immer angezeigt wird,
		wenn ein einfaches Produkt im Warenkorb liegt (egal ob downloadbar/virtuell oder nicht).</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'legal_service' ]; ?>" data-orientation="top">
		<h2>Checkbox für Dienstleistungen</h2>
		<p>Solltest du Dienstleistungen verkaufen, möchtest du vielleicht im Sinne des Käufers bereits vor Ablauf der Widerrufsfrist mit der Erfüllung der Dienstleistung beginnen. Für diesen Fall bieten Germanized die Option einer Checkbox an,
		die es ermöglicht, den Käufer darauf hinzuweisen. Die Checkbox wird natürlich nur dann angezeigt wenn sich Dienstleistungen im Warenkorb befinden (d.h. Produkte die als Dienstleistung markiert wurden). Mehr Informationen dazu findest du auch beim <a href="https://www.haendlerbund.de/de/downloads/das-neue-widerrufsrecht-bei-dienstleistungen.pdf" target="_blank">Händlerbund</a>.
		</p>
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