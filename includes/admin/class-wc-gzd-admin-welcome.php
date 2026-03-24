<?php
/**
 * Welcome Page Class
 *
 * Feature Overview
 *
 * Adapted from code in EDD (Copyright (c) 2012, Pippin Williamson) and WP.
 *
 * @author        Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Add Welcome Screen and Feature Overview
 *
 * @class        WC_GZD_Admin_Welcome
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_Admin_Welcome {

	private $plugin;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->plugin = 'woocommerce-germanized/woocommerce-germanized.php';

		add_action( 'admin_menu', array( $this, 'admin_menus' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
	}

	/**
	 * Add admin menus/screens
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menus() {
		if ( empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$welcome_page_name  = __( 'About Germanized', 'woocommerce-germanized' );
		$welcome_page_title = __( 'Welcome to Germanized', 'woocommerce-germanized' );

		switch ( $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			case 'wc-gzd-about':
				$page = add_dashboard_page(
					$welcome_page_title,
					$welcome_page_name,
					'manage_woocommerce',
					'wc-gzd-about',
					array(
						$this,
						'about_screen',
					)
				);
				add_action( 'admin_print_styles-' . $page, array( $this, 'admin_css' ) );
				break;
		}
	}

	/**
	 * admin_css function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_css() {
		wp_enqueue_style( 'woocommerce-gzd-activation', WC_germanized()->get_assets_build_url( 'static/admin-activation.css' ), array(), WC_GERMANIZED_VERSION );
	}

	/**
	 * Add styles just for this page, and remove dashboard page links.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_head() {
		remove_submenu_page( 'index.php', 'wc-gzd-about' );
	}

	/**
	 * Into text/links shown on all about pages.
	 *
	 * @access private
	 * @return void
	 */
	private function intro() {
		// Flush after upgrades
		if ( ! empty( $_GET['wc-gzd-updated'] ) || ! empty( $_GET['wc-gzd-installed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			flush_rewrite_rules();
		}

		// Drop minor version
		$major_version = \Vendidero\Germanized\PluginsHelper::get_major_version( WC_germanized()->version );
		?>
		<style>
			.wc-gzd-admin-welcome-hide-pro .wc-germanized-welcome-pro, .wc-gzd-admin-welcome-hide-pro .wc-gzd-pro-version {
				display: none;
			}
		</style>
		<div class="wc-gzd-news <?php echo( WC_germanized()->is_pro() ? 'wc-gzd-admin-welcome-hide-pro' : '' ); ?>">

			<h1>Willkommen bei Germanized</h1>
			<div class="about-logo-wrapper">
				<a class="wc-gzd-logo" href="https://vendidero.de/woocommerce-germanized" target="_blank"></a>
				<div class="about-text woocommerce-about-text">
					<?php
					if ( ! empty( $_GET['wc-gzd-installed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$message = 'Super, alles erledigt!';
					} elseif ( ! empty( $_GET['wc-gzd-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$message = 'Danke, dass du auf die neueste Version aktualisiert hast!';
					} else {
						$message = 'Danke für die Installation!';
					}
					echo $message . '<br/>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
					Germanized <?php echo esc_html( $major_version ); ?> erweitert deine WooCommerce Installation um wichtige Funktionen
					für den deutschen Markt.
				</div>
			</div>

			<p class="woocommerce-actions wc-gzd-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ); ?>" target="_blank" class="wc-gzd-default-button button button-primary">Einstellungen</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized&tutorial=yes' ) ); ?>" target="_blank" class="wc-gzd-default-button button button-primary">Tutorial</a>
				<a href="https://vendidero.de/woocommerce-germanized#upgrade" target="_blank" class="button wc-gzd-button wc-germanized-welcome-pro">Upgrade zur <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version</a>
			</p>

			<div class="changelog new-feature">
				<h3>Widerrufsbutton für WooCommerce</h3>

				<div class="columns two-col">
					<div class="col">
						<p>
							Ab dem 19.06.2026 gilt EU-weit die Pflicht zur Einführung eines Widerrufsbuttons für Online-Verträge über Waren und Dienstleistungen. Germanized 4.0 stellt alle benötigten Funktionalitäten zur Erfüllung dieser
							neuen Vorschriften bereit. Die neue Seite um den Vertrag zu widerrufen wurde als Entwurf bei der Installation von Germanized 4.0 angelegt. Diese Seite solltest du prüfen, rechtzeitig veröffentlichen und z.B. im Footer deines Shops verlinken.
							Auf dieser Seite befindet sich ein Shortcode, der das Formular für die Erklärung des Widerrufs einbindet. Es gibt auch neue E-Mail-Vorlagen, die sowohl dich als Shopbetreiber auf einen neuen Widerruf hinweisen als auch den Eingang des Widerrufs an den Kunden bestätigen.
						</p>

						<div class="wc-gzd-actions wc-gzd-actions-right">
							<a href="https://vendidero.de/widerrufsbutton-rechtssicher-in-woocommerce-umsetzen" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-general&section=withdrawal_button' ) ); ?>" class="wc-gzd-default-button button button-primary" target="_blank">Einstellungen verwalten</a>
						</div>
					</div>
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/order-withdrawal-button.png" style=""/>
					</div>
				</div>
			</div>

			<div class="changelog new-feature">
				<h3>Goodbye Shipments, hello Shiptastic!</h3>

				<div class="columns two-col">
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/shiptastic.png" style=""/>
					</div>
					<div class="col">
						<p>
							Die Sendungsintegration in Germanized haben wir in den letzten Versionen kontinuierlich erweitert und verbessert. Jetzt ist es soweit, dass aus den Sendungen ein eigenständiges Plugin wird: <a href="https://wordpress.org/plugins/shiptastic-for-woocommerce">Shiptastic für WooCommerce</a>.
							Dabei handelt es sich um ein Refactoring der Sendungsintegration aus Germanized, d.h. funktional hat sich nicht viel verändert, technisch werden aber Präfixe, Datenbank-Tabellen und Klassen anders benannt. Aus Kompatibilitätsgründen
							liefert Germanized die alten Klassen noch mit und wendet auch sehr viele beliebte Hooks & Filter an - wir empfehlen dennoch, insofern du individuelle Anpassungen vorgenommen hast, deine Integration zu aktualisieren.
						</p>

						<div class="wc-gzd-actions wc-gzd-actions-right">
							<a href="https://vendidero.de/doc/woocommerce-germanized/shipments-zu-shiptastic-migration" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>
						</div>
					</div>
				</div>
			</div>

			<div class="changelog new-feature">
				<h3>Produktsicherheit (GPSR)</h3>

				<div class="columns two-col">
					<div class="col">
						<p>
							Mit der Einführung der Verordnung über die allgemeine Produktsicherheit (GPSR) am 13.12.2024 gehen neue rechtliche Konsequenzen für Shopbetreiber einher, die Produkte an Verbraucher
							in der EU verkaufen. In Germanized stellen wir schon jetzt Möglichkeiten bereit, mithilfe derer du deine Produktdaten entsprechend erweitern kannst, z.B. über einen verknüpften Hersteller samt Adressangaben
							und die Hinterlegung spezieller, für die Sicherheit des Produktes relevanter, Dokumente.
						</p>

						<p>
							Standardmäßig erfolgt die Ausgabe in einem separaten Tab auf der Produktdetailseite. Wie aus Germanized gewohnt, kannst du diese Darstellung über die Preisauszeichnungen auch bequem anpassen.
						</p>

						<div class="wc-gzd-actions wc-gzd-actions-right">
							<a href="https://vendidero.de/doc/woocommerce-germanized/allgemeine-produktsicherheit-gpsr" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>
						</div>
					</div>
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/gpsr.png" style=""/>
					</div>
				</div>
			</div>

			<div class="changelog new-feature">
				<h3>Hermes Schnittstelle <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span></h3>

				<div class="columns two-col">
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/hermes-label.png" style=""/>
					</div>
					<div class="col">
						<p>
							Erstelle mit Germanized Pro bequem Labels für den Versanddienstleister Hermes. Mit unserer neuen Hermes-Schnittstelle
							kannst du sowohl normale, als auch Retouren-Labels (auch als QR-Code) erzeugen. Zudem haben wir die Möglichkeit des Versands an
							Hermes Paketshops an die in Germanized integrierte Abholstationen-Auswahl im Checkout angebunden.
						</p>

						<div class="wc-gzd-actions wc-gzd-actions-right">
							<a href="https://vendidero.de/doc/woocommerce-germanized/hermes-integration-einrichten" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>
							<a href="https://vendidero.de/woocommerce-germanized" target="_blank" class="wc-gzd-pro-version button button-primary wc-gzd-button"><span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version entdecken</a>
							<p class="price smaller wc-gzd-pro-version">ab 85 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
						</div>
					</div>
				</div>
			</div>

			<div class="changelog new-feature">
				<h3>E-Rechnungen einfach erstellen (lassen) <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span></h3>

				<div class="columns two-col">
					<div class="col">
						<p>
							Mit Germanized Pro 4.0 erstellst du nun einfach und bequem E-Rechnungen und bist damit schon jetzt auf die <a href="https://www.ihk.de/darmstadt/produktmarken/recht-und-fair-play/steuerinfo/bmf-plant-verpflichtende-erechnung-und-meldesystem-5784882" target="_blank">neuen Anforderungen</a> 2025 bestens vorbereitet.
							Vorerst unterstützt Germanized Pro das ZUGFeRD Format und ermöglicht es dir E-Rechnungen in verschiedenen Profilen zu erstellen (z.B. Comfort, Extended, XRechnung) und ist damit mit der EU-Spezifikation EN16931 voll kompatibel. Insofern es das Format hergibt, kannst du deine E-Rechnungen natürlich, wie gewohnt, auch
							automatisch erstellen lassen und, falls möglich, die PDF-Datei direkt in eine PDF/A überführen, die sowohl die PDF-Datei, als auch die strukturierten Rechnungsdaten enthält.
						</p>

						<div class="wc-gzd-actions wc-gzd-actions-right">
							<a href="https://vendidero.de/woocommerce-germanized" target="_blank" class="wc-gzd-pro-version button button-primary wc-gzd-button"><span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version entdecken</a>
							<p class="price smaller wc-gzd-pro-version">ab 85 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
						</div>
					</div>
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/e-invoice.png" style=""/>
					</div>
				</div>
			</div>

			<div class="changelog new-feature">
				<h3>Block: Mehrstufige Kasse <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span></h3>

				<div class="columns two-col">
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/multilevel-checkout.png" style=""/>
					</div>
					<div class="col">
						<p>
							Nutzer der Pro-Version haben von nun an Zugriff auf die mehrstufige Kasse speziell entwickelt für die neue, block-basierte Kasse von WooCommerce.
							Neben Highlights wie einer Breadcrumb-Navigation und Bestätigungsseite, glänzt die neue mehrstufige Kasse beim Thema UX mit einer Anzeige der jeweils für den aktuellen Schritt
							aktualisierten Zusammenfassung der Benutzereingaben. Natürlich ist die Kasse auch für mobile Endgeräte optimiert. Kleine aber feine Verbesserungen, z.B. das Verschieben der Auswahl
							einer abweichenden Rechnungsadresse in den Schritt "Zahlung", sorgen für einen optimierten Kaufprozess.
						</p>

						<div class="wc-gzd-actions wc-gzd-actions-right">
							<a href="https://vendidero.de/woocommerce-germanized" target="_blank" class="wc-gzd-pro-version button button-primary wc-gzd-button"><span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version entdecken</a>
							<p class="price smaller wc-gzd-pro-version">ab 85 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
						</div>
					</div>
				</div>
			</div>

			<div class="changelog new-feature">
				<h3>Built-in Versandregeln, automatisches Packen & mehr</h3>

				<div class="columns two-col">
					<div class="col">
						<p>
							Germanized 3.15 kommt mit neuen, lang ersehnten Features. Von nun an benötigst du kein separates Plugin mehr um deine Versandregeln zu konfigurieren.
							Mit Germanized kannst du nun ganz bequem, je Versanddienstleister, eigene Versandregeln anhand konkreter Bedingungen hinterlegen. Diese Regeln beziehen sich
							allesamt auf die von dir hinterlegten Verpackungen. Germanized bestimmt von nun an automatisch im Warenkorb welche Verpackung(en) benötigt werden und berechnet anhand
							deiner konfigurierten Regeln die Versandkosten. Das Feature <i>automatisches Packen</i> ist von nun an auch in der Basis-Version von Germanized verfügbar 🎉
						</p>

						<p>
							Außerdem kannst du von nun an deine individuellen Konfigurationen für das Erstellen von Labels, z.B. DHL Warenpost + GoGreen direkt an eine Verpackung binden.
						</p>

						<div class="wc-gzd-actions wc-gzd-actions-right">
							<a href="https://vendidero.de/germanized-3-15" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>" class="wc-gzd-default-button button button-primary" target="_blank">Versandarten konfigurieren</a>
						</div>
					</div>
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/shipping-rules.png"/>
					</div>
				</div>
			</div>

			<div class="changelog new-feature">
				<h3>Full-Site-Editing & Checkout-Block Support</h3>

				<div class="columns two-col">
					<div class="col">
						<p>
							In Germanized 3.14 haben wir uns sehr intensiv mit den neuen Blöcken auseinandergesetzt, die WooCommerce z.B. für eine
							optimierte Darstellung des <a href="https://woocommerce.com/de-de/checkout-blocks/" target="_blank">Kaufvorgangs</a> bereitstellt. In diesem Zusammenhang haben wir uns neben der Anpassung der neuen Kasse
							an die Vorgaben der Button-Lösung auch um das Bereitstellen individueller Blöcke (z.B. Checkboxen, Hinweis für Photovoltaikanlagen,
							USt.-ID Abfrage <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span>) gekümmert. Insofern du bereits die Block-basierte Kasse nutzt, solltest
							du das Layout kontrollieren und die von Germanized bereitgestellten Blöcke einfügen.</p>

						<p>Darüber hinaus stellt Germanized nun individuelle Blöcke für die verschiedenen Preisauszeichnungen bereit - diese Blöcke kannst du z.B. bei der Bearbeitung der Vorlage <em>Einzelnes Produkt</em> in WooCommerce verwenden.</p>

						<div class="wc-gzd-actions">
							<a href="https://vendidero.de/germanized-3-14" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>

							<?php if ( wc_gzd_has_checkout_block() ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( wc_get_page_id( 'checkout' ) ) ); ?>" class="wc-gzd-default-button button button-primary" target="_blank">Kasse bearbeiten</a>
							<?php endif; ?>
							<?php if ( wc_gzd_current_theme_is_fse_theme() ) : ?>
								<a href="<?php echo esc_url( admin_url( 'site-editor.php?postType=wp_template&postId=woocommerce/woocommerce//single-product&canvas=edit' ) ); ?>" class="wc-gzd-default-button button button-primary" target="_blank">Einzelnes Produkt bearbeiten</a>
							<?php endif; ?>
						</div>
					</div>
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/checkout-block.png"/>
					</div>
				</div>
			</div>

			<div class="changelog new-feature">
				<h3>Handels- bzw. Proformarechnungen erstellen <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span></h3>

				<div class="columns two-col">
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/commercial-invoice.png" style="max-width: 450px;"/>
					</div>
					<div class="col">
						<p>
							Erstelle mit Germanized Pro für deine internationale Sendungen bequem eine Handels- bzw. Proformarechnungen um den Zollbestimmungen zu entsprechen.
							Alle relevanten Informationen (Gewichte, Herstellerland, Exportgrund usw.) werden für dich automatisch platziert. Wie bei den anderen Dokumenten
							(Rechnung, Stornierung, Lieferschein) kannst du auch bei der Handelsrechnung das Layout individuell über den integrierten PDF-Editor anpassen.
						</p>

						<div class="wc-gzd-actions wc-gzd-actions-right">
							<a href="https://vendidero.de/woocommerce-germanized" target="_blank" class="wc-gzd-pro-version button button-primary wc-gzd-button"><span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version entdecken</a>
							<a href="https://vendidero.de/doc/woocommerce-germanized/handelsrechnungen-zu-sendungen-erstellen" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>

							<p class="price smaller wc-gzd-pro-version">ab 85 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
						</div>
					</div>
				</div>
			</div>

			<div class="changelog">
				<h3>Weitere Neuigkeiten in Germanized <?php echo esc_html( $major_version ); ?></h3>

				<div class="three-col columns">
					<div class="col">
						<h4><span class="dashicons dashicons-button"></span> Widerrufsbutton</h4>
						<p>
							Germanized 4.0 fügt umfangreiche Funktionalitäten zur Erfüllung der neuen rechtlichen Rahmenbedingungen des Widerrufsbuttons hinzu. Lasse deine
							Kunden den Widerruf einfach online erklären und spare Zeit bei der Bearbeitung der Anträge.
						</p>
					</div>
					<div class="col">
						<h4><span class="dashicons dashicons-archive"></span> Versandtools ausgelagert</h4>
						<p>
							Die gesamte Versandfunktionalität (Sendungen, Retouren usw.) wurde mittlerweile in das Plugin Shiptastic für WooCommerce ausgelagert und ist seit 4.0 nicht
							mehr Teil des Germanized Cores. Nicht jeder Germanized-Nutzer benötigt umfangreiche Versandtools.
						</p>
					</div>
					<div class="col">
						<h4><span class="dashicons dashicons-block-default"></span> 100% Block-Support</h4>
						<p>
							Germanized 4.0 unterstützt vollumfänglich die von WooCommerce bereitgestellten Blöcke, z.B. für Warenkorb, Kasse und Produktseite.
							Damit wurde ein wichtiger Schritt in Richtung Full-Site-Editing und einem modernen WP Frontend gemacht.
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the about screen.
	 */
	public function about_screen() {
		// Delete the redirect transient
		delete_transient( '_wc_gzd_activation_redirect' );
		?>
		<div class="wrap about-wrap">
			<?php $this->intro(); ?>
			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ); ?>"><?php esc_html_e( 'Go to Germanized Settings', 'woocommerce-germanized' ); ?></a>
			</div>
		</div>
		<?php
	}
}

new WC_GZD_Admin_Welcome();

?>
