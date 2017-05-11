<?php
/**
 * Admin View: Settings pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$elements = array(
    'submit' => '#woocommerce_gzd_order_submit_btn_text',
    'contract' => '#woocommerce_gzdp_contract_after_confirmation',
    'terms' => '#woocommerce_terms_page_id',
    'complaints' => '#woocommerce_gzd_alternative_complaints_text_none',
    'small_business' => '#woocommerce_gzd_small_enterprise',
    'delivery_time' => '#woocommerce_gzd_default_delivery_time',
    'shipping_tax' => '#woocommerce_gzd_shipping_tax',
    'customer_account' => '#woocommerce_gzd_customer_account_checkbox',
    'customer_activation' => '#woocommerce_gzd_customer_activation',
    'invoice' => '#woocommerce_gzdp_invoice_enable',
    'vat_id' => '#woocommerce_gzdp_enable_vat_check',
);

if ( ! wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
    $e = array(
        'terms' => '#s2id_woocommerce_terms_page_id',
        'delivery_time' => '#s2id_woocommerce_gzd_default_delivery_time',
    );

    $elements = array_merge( $elements, $e );
}

?>

<ol class="tourbus-legs wc-gzd-tour" id="tour-settings-general">

	<li data-orientation="centered">
		<h2>WooCommerce Germanized Tour</h2>
		<p>Lerne jetzt schnell und einfach die ersten Schritte zur Konfiguration von WooCommerce Germanized kennen.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">Los geht's</a>
		<a class="button button-secondary" href="<?php echo WC_GZD_Admin::instance()->disable_tour_link( 'general' ); ?>" style="float: right">Nein, Danke</a>
	</li>

	<li data-el=".subsubsub" data-orientation="bottom">
		<h2>Kategorien</h2>
		<p>Über die Tabs kannst du die unterschiedlichen Einstellungen anwählen und die dort hinterlegten Optionen konfigurieren.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'submit' ]; ?>" data-orientation="top">
		<h2>Kaufen-Button</h2>
		<p>Spätestens mit Verabschiedung der Button-Lösung, hat die Beschriftung des Kaufen-Buttons an Relevanz gewonnen. Du kannst hier natürlich
		auch andere Beschriftungen z.B. "zahlungspflichtig bestellen" wählen.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li class="wc-gzd-tour-pro" data-el="<?php echo $elements[ 'contract' ]; ?>" data-orientation="top" data-width="500">
		<h2><span class="wc-gzd-pro">pro</span> Verspäteter Vertragsschluss</h2>
		<p>
			Als Nutzer der Pro-Version kannst du über diese Option festlegen, dass du alle Bestellungen vor Annahme des Kaufvertrages manuell prüfen möchtest.
			Standardmäßig erfolgt die Vertragsbestätigung bei WooCommerce direkt nach der Bestellung. Mit dieser Option kannst du dieses Verhalten verhindern.
		</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
		<?php if ( ! WC_germanized()->is_pro() ) : ?>
			<a class="button button-secondary" style="float:right" href="https://vendidero.de/woocommerce-germanized#contract" target="_blank">mehr erfahren</a>
		<?php endif; ?>
	</li>

	<li data-el="<?php echo $elements[ 'terms' ]; ?>" data-orientation="top">
		<h2>Rechtliche Hinweisseiten</h2>
		<p>Damit dein Shop besser vor Abmahnungen geschützt ist, solltest du hier auf jeden Fall deine Rechtstexte wie z.B. AGB, Widerrufsbelehrung, Impressum etc. auswählen.
		Diese Texte werden, je nach Einstellungen, auch als Textanhang für die Bestellbestätigung oder andere WooCommerce E-Mails verwendet.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'complaints' ]; ?>" data-orientation="top">
		<h2>Streitbeilegung</h2>
		<p>Das Thema Streitbelegung ist relativ aktuell und einige Informationspflichten sind diesbezüglich einzuhalten. Wir stellen dir hier die von Trusted Shops bereitgestellten Mustertexte für die jeweiligen
		Fälle zur Verfügung. Den Text kannst du an deine Bedürfnisse anpassen und den Shortcode [gzd_complaints] (passiert mit dem Anlegen der rechtlichen Hinweisseiten automatisch) in dein Impressum einbinden, um den Text anzuzeigen. Zudem empfiehlt Trusted Shops
		den Text auch in deinen AGB einzubinden. Weitere Informationen dazu findest du <a href="http://shopbetreiber-blog.de/2017/01/05/streitschlichtung-neue-infopflichten-fuer-alle-online-haendler-ab-1-februar/" target="_blank">hier</a>.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

    <li data-el="<?php echo $elements[ 'small_business' ]; ?>" data-orientation="bottom">
        <h2>Kleinunternehmerregelung</h2>
        <p>Bist du Kleinunternehmer nach §19 UStG.? Dann aktiviere diese Option. Wenn du zusätzlich einen Hinweis auf der Produktseite
            aktivieren möchtest, setze auch die untere Checkbox.</p>
        <a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
    </li>

	<li data-el="<?php echo $elements[ 'delivery_time' ]; ?>" data-orientation="top" data-width="600">
		<h2>Lieferzeiten</h2>
		<p>Standardmäßig verfügt WooCommerce über keine <a href="<?php echo admin_url( 'edit-tags.php?taxonomy=product_delivery_time&post_type=product' ); ?>" target="_blank">Lieferzeiten-Verwaltung</a> für Produkte. Diese Funktionalität fügt Germanized hinzu.
		Wähle optional eine Standard-Lieferzeit aus, die immer dann gezeigt wird, wenn keine Lieferzeit für das Produkt hinterlegt wurde.
		Der Lieferzeiten-Text wird verwendet um die Lieferzeiten im Shop darzustellen. Du kannst mit dieser Option die Ausgabe steuern - z.B. Lieferzeit: {delivery_time} wobei der Platzhalter {delivery_time} mit dem für die Lieferzeit hinterlegtem Wert (z.B. 3-4 Tage) ersetzt wird.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'shipping_tax' ]; ?>" data-orientation="top">
		<h2>Versand- und Gebührenberechnung</h2>
		<p>Diese Option ist eigentlich nur relevant für Shop-Betreiber, die Artikel zu unterschiedlichen Umsatzsteuersätzen (z.B. 19% und 7%) verkaufen.
		In diesem Fall übernimmt Germanized eine genauere Berechnung der Umsatzsteuer für Versandkosten. Unterhalb der Option findest du dafür ein Beispiel.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'customer_account' ]; ?>" data-orientation="top">
		<h2>Kundenkonten</h2>
		<p>Viele Händler bieten Käufern das Erstellen von Kundenkonten an. In diesem Fall solltest du mit deinem Anwalt Rücksprache halten, ob eine Checkbox
		von Nöten ist, die auf deine Datenschutzerklärung hinweist. Sollte das der Fall sein, kannst du unten aufgeführte Optionen verwenden. 
		</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li data-el="<?php echo $elements[ 'customer_activation' ]; ?>" data-orientation="top" data-width="600">
		<h2>Double Opt-In</h2>
		<p>Ob du das Double Opt-In-Verfahren für Kundenregistrierungen einsetzt oder nicht, solltest du auch mit deinem Anwalt besprechen. Germanized hat dieses Verfahren so umgesetzt,
		dass Kundenkonten direkt nach der Registrierung zwar nutzbar sind (um den Kaufprozess nicht zu unterbrechen) aber u.U. nach X Tagen (je nach Einstellung) automatisch gelöscht werden, wenn der Kunde
		nicht auf den Link in der E-Mail (zugestellt nach der Regisrierung) klickt und das Konto aktiviert.
		</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

	<li class="wc-gzd-tour-pro" data-el="<?php echo $elements[ 'invoice' ]; ?>" data-orientation="top" data-width="500">
		<h2><span class="wc-gzd-pro">pro</span> Rechnungen & Lieferscheine</h2>
		<p>
			Als Nutzer der Pro-Version kannst du über diese Option das Erzeugen von PDF-Rechnungen zu Bestellungen aktivieren.
			Dieses Feature haben wir in Germanized Pro besonders ausgeklügelt implementiert, sodass du dich voll und ganz auf das Verkaufen konzentrieren kannst.
		</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
		<?php if ( ! WC_germanized()->is_pro() ) : ?>
			<a class="button button-secondary" style="float:right" href="https://vendidero.de/woocommerce-germanized#accounting" target="_blank">mehr erfahren</a>
		<?php endif; ?>
	</li>

	<li class="wc-gzd-tour-pro" data-el="<?php echo $elements[ 'vat_id' ]; ?>" data-orientation="top" data-width="500">
		<h2><span class="wc-gzd-pro">pro</span> Umsatzsteuer ID prüfen</h2>
		<p>
			Verkäufer, die an gewerbliche Käufer im europäischen Ausland verkaufen kennen das: Die Umsatzsteuer soll bei Eingabe
			einer validen Umsatzsteuer-Identifikationsnummer entfallen. Mit der Pro-Version kein Problem mehr. Das Plugin prüft nach Eingabe einer USt.-ID automatisch
			anhand der europäischen Schnittstelle, ob die ID valide ist und entfernt die USt. in der Kasse.
		</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
		<?php if ( ! WC_germanized()->is_pro() ) : ?>
			<a class="button button-secondary" style="float:right" href="https://vendidero.de/woocommerce-germanized#vat" target="_blank">mehr erfahren</a>
		<?php endif; ?>
	</li>

	<li data-el=".submit" data-orientation="top">
		<h2>Einstellungen speichern</h2>
		<p>Wenn du deine Einstellungen angepasst hast, dann speichere sie über diesen Button.
		</p>
		<a class="button button-primary" href="<?php echo add_query_arg( array( 'section' => 'display' ), WC_GZD_Admin::instance()->disable_tour_link( 'general' ) ); ?>">Weiter zur Anzeige</a>
	</li>

</ol>