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

		$welcome_page_name = __( 'About Germanized', 'woocommerce-germanized' );
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
            <a class="wc-gzd-logo" href="https://vendidero.de/woocommerce-germanized" target="_blank"
               style="margin-right: 1em"></a>
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

            <p class="woocommerce-actions wc-gzd-actions">
                <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized' ); ?>" target="_blank"
                   class="button button-primary">Einstellungen</a>
                <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized&tutorial=yes' ); ?>"
                   target="_blank" class="button button-primary">Tutorial</a>
                <a href="https://vendidero.de/woocommerce-germanized#upgrade" target="_blank" class="button wc-gzd-button wc-germanized-welcome-pro">Upgrade
                    zur <span class="wc-gzd-pro">pro</span> Version</a>
            </p>

            <div class="changelog new-feature">
                <h3>Hinweis: Mehrwertsteuersenkung in Deutschland</h3>

                <p>
                    Damit ihr, was die Mehrwertsteuersenkung im Rahmen der Corona-Pandemie angeht, nicht in Zeitnot geratet, haben wir euch in unserem <a href="https://vendidero.de/senkung-der-mehrwertsteuer-in-woocommerce-im-rahmen-der-corona-pandemie" target="_blank">Blog-Eintrag</a> einige
                    Möglichkeiten zur <a href="https://vendidero.de/senkung-der-mehrwertsteuer-in-woocommerce-im-rahmen-der-corona-pandemie#automatisches-update-der-steuersaetze" target="_blank">automatischen</a> oder manuellen Anpassung der Mehrwertsteuersätze in WooCommerce bereitgestellt.
                    Mit Hilfe unseres kleinen Zusatz-Plugins könnt ihr eure Steuersätze einfach über Nacht automatisch anpassen lassen und damit hoffentlich ruhig(er) schlafen. Wir wünschen euch weiterhin viel Erfolg!
                </p>

                <div class="wc-germanized-welcome-pro">
                    <a href="https://vendidero.de/woocommerce-germanized" target="_blank"
                       class="button button-primary wc-gzd-button"><span class="wc-gzd-pro">pro</span> Version
                        entdecken</a>
                    <p class="price smaller">ab 69 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium
                        Support!</p>
                </div>

                <div class="wc-gzd-actions">
                    <a href="https://vendidero.de/senkung-der-mehrwertsteuer-in-woocommerce-im-rahmen-der-corona-pandemie" class="button button-primary" target="_blank">Mehr erfahren</a>
                </div>
            </div>

            <div class="changelog new-feature">
                <h3>Neu: Sendungen zu Bestellungen erstellen</h3>

                <div class="columns two-col">
                    <div class="col align-center">
                        <img src="<?php echo WC_germanized()->plugin_url(); ?>/assets/images/shipments.png"/>
                    </div>
                    <div class="col">
                        <p>
                            Version 3.0 führt eine neue Abstraktion zur (besseren) Verwaltung von Sendungen zu
                            Bestellungen hinzu. Über eine einfache UI kannst du nun
                            unkompliziert (mehrere) Sendungen zu deinen Bestellungen hinzufügen. Über eine eigene
                            Benachrichtigungsmail werden deine Kunden einfach über neue Sendungen informiert.
                            Zu jeder Sendung kannst du optional Retouren anlegen, falls dein Kunde Waren zurückschicken
                            möchte.
                        </p>

                        <p>
                            Sendungen lassen sich auch automatisch bei Erreichen eines bestimmten Bestellstatus
                            erzeugen.
                            Als Kunde unserer Pro-Version kannst du zudem PDF-Lieferscheine zu Sendungen erzeugen.
                        </p>

                        <div class="wc-germanized-welcome-pro">
                            <a href="https://vendidero.de/woocommerce-germanized" target="_blank"
                               class="button button-primary wc-gzd-button"><span class="wc-gzd-pro">pro</span> Version
                                entdecken</a>
                            <p class="price smaller">ab 69 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium
                                Support!</p>
                        </div>

                        <div class="wc-gzd-actions">
                            <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized-shipments' ); ?>"
                               class="button button-primary" target="_blank">Einstellungen anpassen</a>
                            <a href="https://vendidero.de/dokument/sendungen-zu-bestellungen-erzeugen" class="button button-primary" target="_blank">Mehr erfahren</a>
                        </div>
                    </div>
                </div>
            </div>

			<?php if ( Vendidero\Germanized\DHL\Package::base_country_is_supported() ) : ?>

                <div class="changelog new-feature">
                    <h3>Neu: Nahtlose Integration von DHL Produkten</h3>

                    <div class="columns two-col">

                        <div class="col align-center">
                            <img src="<?php echo WC_germanized()->plugin_url(); ?>/assets/images/dhl.png"/>
                        </div>

                        <div class="col">
                            <p>
                                Germanized & DHL sind nun Partner. Wir haben beschlossen, die DHL Produkte nahtlos in
                                Germanized zu integrieren, um euren Aufwand bei der Sendungsverwaltung zu verringern.
                                Als DHL Geschäftskunde könnt ihr nun bequem Labels zu Sendungen und Retouren erzeugen.
                                Unsere DHL Integration bietet umfangreiche Anpassungsmöglichkeiten.
                                Du kannst z.B. je Versandmethode unterschiedliche DHL Services für deine Labels
                                konfigurieren.
                            </p>

                            <p>
                                Du kannst DHL Labels auch automatisch zu deinen Sendungen erstellen lassen und deinen
                                Aufwand somit weiter verringern.
                            </p>

                            <div class="wc-gzd-actions">
                                <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl' ); ?>"
                                   class="button button-primary" target="_blank">Einstellungen anpassen</a>
                                <a href="https://vendidero.de/dokument/dhl-integration-einrichten" class="button button-primary" target="_blank">Mehr erfahren</a>
                            </div>
                        </div>
                    </div>
                </div>

			<?php endif; ?>

            <div class="changelog">
                <h3>Weitere Neuigkeiten in Germanized 3.0</h3>

                <div class="three-col columns">
                    <div class="col">
                        <h4><span class="dashicons dashicons-cart"></span> Preisauszeichnungen</h4>
                        <p>
                            In Germanized 3.0 entscheidest du flexibel, wo, welche Preisauszeichnungen angezeigt werden
                            sollen. Du kannst zudem die Priorität bzw. Reihenfolge bequem über die UI anpassen und
                            verschiedene Orte im Template wählen.
                        </p>
                    </div>
                    <div class="col">
                        <h4><span class="dashicons dashicons-admin-appearance"></span> Strukturierte Einstellungen</h4>
                        <p>
                            Wir haben die UI für die Einstellungen komplett überarbeitet. Dabei haben wir explizit Wert
                            auf die Übersichtlichkeit gelegt. Die verschiedenen Rubriken können jetzt übersichtlich in
                            einer Tabelle ausgewählt werden.
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