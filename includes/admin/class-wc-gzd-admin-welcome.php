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
				<h3>Full-Site-Editing & Checkout-Block Support</h3>

				<div class="columns two-col">
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/checkout-block.png"/>
					</div>
					<div class="col">
						<p>
							In Germanized 3.14 haben wir uns sehr intensiv mit den neuen Blöcken auseinandergesetzt, die WooCommerce z.B. für eine
							optimierte Darstellung des <a href="https://woocommerce.com/de-de/checkout-blocks/" target="_blank">Kaufvorgangs</a> bereitstellt. In diesem Zusammenhang haben wir uns neben der Anpassung der neuen Kasse
							an die Vorgaben der Button-Lösung auch um das Bereitstellen individueller Blöcke (z.B. Checkboxen, Hinweis für Photovoltaikanlagen,
							USt.-ID Abfrage <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span>) gekümmert. Insofern du bereits die Block-basierte Kasse nutzt, solltest
							du das Layout kontrollieren und die von Germanized bereitgestellten Blöcke einfügen.</p>

						<p>Darüber hinaus stellt Germanized nun individuelle Blöcke für die verschiedenen Preisauszeichnungen bereit - diese Blöcke kannst du z.B. bei der Bearbeitung der Vorlage <em>Einzelnes Produkt</em> in WooCommerce verwenden.</p>

						<div class="wc-gzd-actions wc-gzd-actions-right">
							<a href="https://vendidero.de/germanized-3-14" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>

							<?php if ( wc_gzd_has_checkout_block() ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( wc_get_page_id( 'checkout' ) ) ); ?>" class="wc-gzd-default-button button button-primary" target="_blank">Kasse bearbeiten</a>
							<?php endif; ?>
							<?php if ( wc_gzd_current_theme_is_fse_theme() ) : ?>
								<a href="<?php echo esc_url( admin_url( 'site-editor.php?postType=wp_template&postId=woocommerce/woocommerce//single-product&canvas=edit' ) ); ?>" class="wc-gzd-default-button button button-primary" target="_blank">Einzelnes Produkt bearbeiten</a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<div class="changelog new-feature">
				<h3>Handels- bzw. Proformarechnungen erstellen <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span></h3>

				<div class="columns two-col">
					<div class="col">
						<p>
							Erstelle mit Germanized Pro für deine internationale Sendungen bequem eine Handels- bzw. Proformarechnungen um den Zollbestimmungen zu entsprechen.
							Alle relevanten Informationen (Gewichte, Herstellerland, Exportgrund usw.) werden für dich automatisch platziert. Wie bei den anderen Dokumenten
							(Rechnung, Stornierung, Lieferschein) kannst du auch bei der Handelsrechnung das Layout individuell über den integrierten PDF-Editor anpassen.
						</p>

						<div class="wc-gzd-actions wc-gzd-actions-right">
							<a href="https://vendidero.de/woocommerce-germanized" target="_blank" class="wc-gzd-pro-version button button-primary wc-gzd-button"><span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span> Version entdecken</a>
							<a href="https://vendidero.de/dokument/handelsrechnungen-zu-sendungen-erstellen" class="wc-gzd-default-button button button-primary" target="_blank">Mehr erfahren</a>

							<p class="price smaller wc-gzd-pro-version">ab 79 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
						</div>
					</div>
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/commercial-invoice.png" style="max-width: 450px;"/>
					</div>
				</div>
			</div>

			<div class="changelog new-feature">
				<h3>Photovoltaikanlagen zum Nullsteuersatz verkaufen</h3>

				<div class="columns two-col">
					<div class="col col-center">
						<img src="<?php echo esc_url( WC_germanized()->plugin_url() ); ?>/assets/images/photovoltaic-systems.png" style="max-width: 450px;"/>
					</div>
					<div class="col">
						<p>
							In der neuesten Version von Germanized unterstützen wir dich beim Verkauf von Photovoltaikanlagen nach §12 Absatz 3 UStG. Damit der Nullsteuersatz automatisch
							für deine Photovoltaikanlage(n) angewendet wird, müssen einige Bedingungen erfüllt sein: Die Lieferung muss innerhalb Deutschlands erfolgen, der Kunde hat keine
							Firmenanschrift gewählt und die speziell im Checkout hinzugefügte <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-checkboxes&checkbox_id=photovoltaic_systems' ) ); ?>">Checkbox</a> muss akzeptiert werden.
						</p>

						<div class="wc-gzd-actions wc-gzd-actions-right">
							<a href="https://vendidero.de/photovoltaikanlagen-in-woocommerce-verkaufen-so-funktionierts" target="_blank" class="wc-gzd-button button button-primary">Mehr erfahren</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-general&section=photovoltaic_systems' ) ); ?>" class="wc-gzd-default-button button button-primary">Einstellungen anpassen</a>
						</div>
					</div>
				</div>
			</div>

			<div class="changelog">
				<h3>Weitere Neuigkeiten in Germanized <?php echo esc_html( $major_version ); ?></h3>

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
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ); ?>"><?php esc_html_e( 'Go to Germanized Settings', 'woocommerce-germanized' ); ?></a>
			</div>
		</div>
		<?php
	}
}

new WC_GZD_Admin_Welcome();

?>
