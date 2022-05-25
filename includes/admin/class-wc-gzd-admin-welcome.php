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
		if ( empty( $_GET['page'] ) ) {
			return;
		}

		$welcome_page_name  = __( 'About Germanized', 'woocommerce-germanized' );
		$welcome_page_title = __( 'Welcome to Germanized', 'woocommerce-germanized' );

		switch ( $_GET['page'] ) {
			case 'wc-gzd-about' :
				$page = add_dashboard_page( $welcome_page_title, $welcome_page_name, 'manage_options', 'wc-gzd-about', array(
					$this,
					'about_screen'
				) );
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
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'woocommerce-gzd-activation', WC_germanized()->plugin_url() . '/assets/css/admin-activation' . $suffix . '.css', array(), WC_GERMANIZED_VERSION );
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
		if ( ! empty( $_GET['wc-gzd-updated'] ) || ! empty( $_GET['wc-gzd-installed'] ) ) {
			flush_rewrite_rules();
		}

		// Drop minor version if 0
		$major_version = substr( WC_germanized()->version, 0, 3 );
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
		            if ( ! empty( $_GET['wc-gzd-installed'] ) ) {
			            $message = 'Super, alles erledigt!';
		            } elseif ( ! empty( $_GET['wc-gzd-updated'] ) ) {
			            $message = 'Danke, dass du auf die neueste Version aktualisiert hast!';
		            } else {
			            $message = 'Danke für die Installation!';
		            }
		            echo $message . '<br/>';
		            ?>
                    Germanized <?php echo $major_version; ?> erweitert deine WooCommerce Installation um wichtige Funktionen
                    für den deutschen Markt.
                </div>
            </div>

            <p class="woocommerce-actions wc-gzd-actions">
                <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized' ); ?>" target="_blank"
                   class="wc-gzd-default-button button button-primary">Einstellungen</a>
                <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized&tutorial=yes' ); ?>"
                   target="_blank" class="wc-gzd-default-button button button-primary">Tutorial</a>
                <a href="https://vendidero.de/woocommerce-germanized#upgrade" target="_blank" class="button wc-gzd-button wc-germanized-welcome-pro">Upgrade
                    zur <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version</a>
            </p>

            <div class="changelog new-feature">
                <h3>Achtung: Neue Preisangabeverordnung und Omnibus-Richtlinie ab dem 28.05.22</h3>
                <p>
                    Ab dem 28.05.2022 gilt eine neue <a href="https://www.it-recht-kanzlei.de/preisangabenverordnung-2022-wichtige-aenderungen.html" target="_blank">Preisangabeverordnung</a>. Bei Grundpreisen ändern sich die zulässigen Einheiten. Ihr solltet also eure in Germanized hinterlegten <a href="https://vendidero.de/dokument/grundpreise-hinterlegen" target="_blank">Grundpreise</a> kontrollieren und ggf. korrigieren.
                    Auch bei Preisermäßigungen gibt es neue Pflichten, die zu beachten sind. Insofern du Lebensmittel mit Pfand verkaufst, muss der Pfand von nun an separat ausgewiesen werden. Das ist mit Germanized <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> kein Problem.
                    <br/><br/>
                    Zusätzlich tritt die <a href="https://www.haendlerbund.de/de/news/aktuelles/rechtliches/4145-omnibus-rezensionen-gekennzeichnet" target="_blank">Omnibus-Richtlinie</a> in Kraft. Du musst von nun an Informationen zur Authentizität von Kundenbewertungen bereitstellen. Wenn du deine Kundenbewertungen über die in WooCommerce integrierte Funktion
                    bereitstellst, unterstützt dich Germanized dabei. Wir haben dafür eine neue rechtliche <a target="_blank" href="<?php echo esc_url( wc_gzd_get_page_permalink( 'review_authenticity' ) ); ?>">Hinweisseite</a> angelegt und entsprechende <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-general&section=shop' ) ); ?>">Hinweise platziert</a>.
                </p>
            </div>

            <div class="changelog new-feature">
                <h3>Neu: Verkaufe Lebensmittel rechtssicher <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span></h3>

                <div class="columns two-col">
                    <div class="col">
                        <p>
                            Mit Germanized Pro verkaufst du nun auch Lebensmittel rechtssicher online. Neben der Lebensmittelkennzeichnung (Nährwerte, Zutaten, Allergene, weitere Pflichtinformationen)
                            kannst du mit Germanized auch den Nutri-Score für deine Lebensmittel anzeigen. Auch dem Thema Pfand haben wir uns intensiv gewidmet. Mit Germanized Pro
                            kannst du nun auch Getränke verkaufen und den Pfand einfach und verlässlich abrechnen. In diesem Fall kümmert sich Germanized auch um die spezielle Kennzeichnung von Mehrweg- bzw. Einweg.
                        </p>

                        <div class="wc-gzd-actions">
                            <a href="https://vendidero.de/woocommerce-germanized" target="_blank" class="wc-gzd-pro-version button button-primary wc-gzd-button"><span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version entdecken</a>
                            <a href="https://vendidero.de/dokument/lebensmittel-auszeichnen" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>

                            <p class="price smaller wc-gzd-pro-version">ab 79 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
                        </div>
                    </div>
                    <div class="col col-center">
                        <img src="<?php echo WC_germanized()->plugin_url(); ?>/assets/images/sell-food.png" style="max-width: 450px;"/>
                    </div>
                </div>
            </div>

            <div class="changelog new-feature">
                <h3>DPD Labels zu Sendungen erstellen <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span></h3>

                <div class="columns two-col">
                    <div class="col col-center">
                        <img src="<?php echo WC_germanized()->plugin_url(); ?>/assets/images/dpd.png" style="max-width: 450px;"/>
                    </div>
                    <div class="col">
                        <p>
                            Neben DHL und Deutsche Post bietet Germanized Pro nun eine weitere, automatische Integration des Versanddienstleisters DPD an. Über die DPD Schnittstelle <strong>DPD Cloud Webservice oder DPD WebConnect</strong> könnt ihr
                            bequem Labels zu Sendungen und Retouren erstellen. Selbstverständlich greifen auch hier die vielfältigen Möglichkeiten der Automatisierung, d.h. ihr könnt von
                            der Erstellung der Sendungen und Zuordnung der passenden Verpackung bis hin zur Label-Erstellung via DPD euren Versandprozess bestmöglich automatisieren.
                        </p>

                        <div class="wc-gzd-actions wc-gzd-actions-right">
                            <a href="https://vendidero.de/woocommerce-germanized" target="_blank" class="wc-gzd-pro-version button button-primary wc-gzd-button"><span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version entdecken</a>
                            <a href="https://vendidero.de/dokument/dpd-integration-einrichten" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>

                            <p class="price smaller wc-gzd-pro-version">ab 79 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="changelog new-feature">
                <h3>PDF-Dokumente visuell bearbeiten <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span></h3>

                <div class="columns two-col">
                    <div class="col">
                        <p>
                            Endlich ist es soweit: <a href="https://vendidero.de/germanized-pro-3-0" target="_blank">Germanized Pro 3.0</a> wurde veröffentlicht. Mit diesem neuen Update kannst du deine PDF-Dokumente (Rechnungen, Stornierungen, Lieferscheine, rechtl. Hinweisseiten)
                            über den Gutenberg-Editor komplett frei gestalten. Dafür haben wir Gutenberg explizit für PDF-Dokumente vorbereitet und viele individuelle Blöcke hinzugefügt, mit denen du deine PDF-Dokumente an deine Bedürfnisse anpasst. Das Ergebnis siehst du als Live-Vorschau im Browser.
                        </p>
                        <p>
                            Mit dem Dokumenten-Editor in Germanized Pro baust du deine Belege individuell auf. Über die Google Fonts Integration suchst du dir eine passende Schriftart für dein PDF Dokument aus. Auch die Tabelle der Positionen einer Rechnung kannst du individuell gestalten.
                            Wähle aus welche Spalten, mit welcher Breite und welcher Bezeichnung angezeigt werden sollen. Damit hast du die volle Kontrolle.
                        </p>

                        <div class="wc-gzd-actions">
                            <a href="https://vendidero.de/woocommerce-germanized" target="_blank" class="wc-gzd-pro-version button button-primary wc-gzd-button"><span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version entdecken</a>
                            <a href="https://vendidero.de/germanized-pro-3-0" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>

                            <p class="price smaller wc-gzd-pro-version">ab 79 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
                        </div>
                    </div>
                    <div class="col col-center">
                        <img src="<?php echo WC_germanized()->plugin_url(); ?>/assets/images/edit-pdf-documents.png"/>
                    </div>
                </div>
            </div>

            <div class="changelog new-feature">
                <h3>Neu: Belege an lexoffice übertagen <span class="wc-gzd-pro">pro</span></h3>

                <div class="columns two-col">
                    <div class="col col-center">
                        <img src="<?php echo WC_germanized()->plugin_url(); ?>/assets/images/lexoffice.png"/>
                    </div>
                    <div class="col">
                        <p>
                            Damit eure Buchhaltung möglichst wenig Arbeit bereitet, haben wir in Germanized Pro 3.0 eine Schnittstelle zu lexoffice für euch parat.
                            Mit der Integration von lexoffice könnt ihr eure Belege ganz einfach per API übertragen. Auf Wunsch geht das auch vollautomatisch, d.h. Germanized
                            überträgt eure Belege nach Erzeugung automatisch an lexoffice. Verringert euren Zeitaufwand bei der Buchhaltung mit Germanized Pro und lexoffice.
                        </p>

                        <div class="wc-gzd-actions wc-gzd-actions-right">
                            <a href="https://vendidero.de/woocommerce-germanized" target="_blank" class="wc-gzd-pro-version button button-primary wc-gzd-button"><span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version entdecken</a>
                            <p class="price smaller wc-gzd-pro-version">ab 79 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="changelog new-feature">
                <h3>Neu: sevDesk Integration <span class="wc-gzd-pro">pro</span></h3>

                <div class="columns two-col">
                    <div class="col">
                        <p>
                            Mit sevDesk bieten wir euch eine Schnittstelle zu einer weiteren Cloud-Buchhaltungs-Lösung an. Auch mit unserer
                            sevDesk Integration könnt ihr eure Belege, auf Wunsch vollautomatisch, per API an sevDesk übertragen. Optional
                            habt ihr in den sevDesk Einstellungen die Möglichkeit, falls möglich, die Rechnung direkt mit einer konkreten Transaktion zu verknüpfen.
                        </p>

                        <div class="wc-gzd-actions">
                            <a href="https://vendidero.de/woocommerce-germanized" target="_blank" class="wc-gzd-pro-version button button-primary wc-gzd-button"><span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version entdecken</a>
                            <p class="price smaller wc-gzd-pro-version">ab 79 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
                        </div>
                    </div>
                    <div class="col col-center">
                        <img src="<?php echo WC_germanized()->plugin_url(); ?>/assets/images/sevdesk.png"/>
                    </div>
                </div>
            </div>

            <div class="changelog">
                <h3>Weitere Neuigkeiten in Germanized <?php echo $major_version; ?></h3>

                <div class="three-col columns">
                    <div class="col">
                        <h4><span class="dashicons dashicons-admin-site"></span> Lieferzeiten je Land</h4>
                        <p>
                            Mit Germanized 3.7 kannst du jetzt optional abweichende Lieferzeiten je Land hinterlegen. Du kannst
                            ebenfalls Lieferzeiten für alle EU-Länder bzw. Nicht-EU-Länder hinterlegen und <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-shopmarks&section=delivery_times' ) ); ?>">individuelle Fallbacks</a> dafür nutzen.
                        </p>
                    </div>
                    <div class="col">
                        <h4><span class="dashicons dashicons-admin-generic"></span> Grundpreisberechnung</h4>
                        <p>
                            Um Staffelpreise oder Rollen-basierte-Preise besser zu unterstützen, aktualisiert
                            Germanized nun automatisch den Grundpreis wenn sich auf der Produktseite der Preis ändert
                            oder durch ein Plugin dynamisch verändert wird.
                        </p>
                    </div>
                    <div class="col">
                        <h4><span class="dashicons dashicons-admin-tools"></span> Under the Hood</h4>
                        <p>
                            Unter der Haube hat sich einiges in Germanized 3.0 verändert. Germanized unterstützt von nun
                            an nur noch WooCommerce ab Version 3. Damit konnten einige, veraltete Legacy-Bestandteile
                            der Software ausgemistet werden.
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
		?>
        <div class="wrap about-wrap">
			<?php $this->intro(); ?>

            <div class="return-to-dashboard">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ); ?>"><?php _e( 'Go to Germanized Settings', 'woocommerce-germanized' ); ?></a>
            </div>
        </div>
		<?php
	}
}

new WC_GZD_Admin_Welcome();

?>