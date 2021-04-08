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
            .wc-gzd-admin-welcome-hide-pro .wc-germanized-welcome-pro {
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
                   class="button button-primary">Einstellungen</a>
                <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized&tutorial=yes' ); ?>"
                   target="_blank" class="button button-primary">Tutorial</a>
                <a href="https://vendidero.de/woocommerce-germanized#upgrade" target="_blank" class="button wc-gzd-button wc-germanized-welcome-pro">Upgrade
                    zur <span class="wc-gzd-pro">pro</span> Version</a>
            </p>

            <?php if ( \Vendidero\Germanized\Shipments\Package::is_packing_supported() ) : ?>
                <div class="changelog new-feature">
                    <h3>Neu: Automatisiertes Packen von Sendungen <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span></h3>

                    <div class="columns two-col">
                        <div class="col col-center">
                            <img src="<?php echo WC_germanized()->plugin_url(); ?>/assets/images/automated-packing.png"/>
                        </div>
                        <div class="col">
                            <p>
                                Wir machen es euch nun noch leichter bei der Sendungsverwaltung: Mit Germanized Pro könnt ihr nun mit Hilfe eines Algorithmus eure Sendungen
                                automatisch packen lassen. Das Packen geschieht auf Basis der von euch hinterlegten Verpackungen. Damit lassen sich Bestellungen nun automatisch
                                in mehrere Sendungen aufteilen, falls die Positionen nicht in eine der verfügbaren Verpackungen passen. <br/><br/>Damit könnt ihr direkt und ohne weitere Kontrolle mit der Versandvorbereitung beginnen.</p>

                            <div class="wc-germanized-welcome-pro">
                                <a href="https://vendidero.de/woocommerce-germanized" target="_blank"
                                   class="button button-primary wc-gzd-button"><span class="wc-gzd-pro">pro</span> Version
                                    entdecken</a>
                                <p class="price smaller">ab 69 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium
                                    Support!</p>
                            </div>

                            <div class="wc-gzd-actions">
                                <a href="https://vendidero.de/dokument/sendungen-automatisiert-packen" target="_blank" class="button button-primary">Weitere Informationen</a>

                                <?php if ( WC_germanized()->is_pro() ) : ?>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-shipments&section=packaging' ) ); ?>" target="_blank" class="button button-primary">Einstellungen</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="changelog new-feature">
                <h3>Neu: PDF-Dokumente visuell bearbeiten <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span></h3>

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
                    </div>
                    <div class="col col-center">
                        <img src="<?php echo WC_germanized()->plugin_url(); ?>/assets/images/edit-pdf-documents.png"/>
                    </div>
                </div>

                <div class="wc-germanized-welcome-pro">
                    <a href="https://vendidero.de/woocommerce-germanized" target="_blank"
                       class="button button-primary wc-gzd-button"><span class="wc-gzd-pro">pro</span> Version
                        entdecken</a>
                    <p class="price smaller">ab 69 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium
                        Support!</p>
                </div>

                <div class="wc-gzd-actions">
                    <a href="https://vendidero.de/germanized-pro-3-0" class="button button-primary" target="_blank">Mehr erfahren</a>
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
                    </div>
                </div>

                <div class="wc-germanized-welcome-pro">
                    <a href="https://vendidero.de/woocommerce-germanized" target="_blank"
                       class="button button-primary wc-gzd-button"><span class="wc-gzd-pro">pro</span> Version
                        entdecken</a>
                    <p class="price smaller">ab 69 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium
                        Support!</p>
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
                    </div>
                    <div class="col col-center">
                        <img src="<?php echo WC_germanized()->plugin_url(); ?>/assets/images/sevdesk.png"/>
                    </div>
                </div>

                <div class="wc-germanized-welcome-pro">
                    <a href="https://vendidero.de/woocommerce-germanized" target="_blank"
                       class="button button-primary wc-gzd-button"><span class="wc-gzd-pro">pro</span> Version
                        entdecken</a>
                    <p class="price smaller">ab 69 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium
                        Support!</p>
                </div>
            </div>

            <div class="changelog">
                <h3>Weitere Neuigkeiten in Germanized 3.3</h3>

                <div class="three-col columns">
                    <div class="col">
                        <h4><span class="dashicons dashicons-archive"></span> Verpackungen</h4>
                        <p>
                            Mit Germanized 3.3 kannst du zu deinen Sendungen jetzt flexibel Verpackungsmaterial hinzufügen
                            und damit das Gesamtgewicht der Sendungen (das z.B. für die Label-Erstellung benötigt wird) besser verwalten.
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
                        <h4><span class="dashicons dashicons-admin-tools"></span> Under the Hook</h4>
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