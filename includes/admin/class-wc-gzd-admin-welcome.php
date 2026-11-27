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
				add_action( 'load-' . $page, array( $this, 'hide_notices' ) );
				break;
		}
	}

	/**
	 * Hide all admin notices on the about page. Notices are removed right before
	 * they are rendered (in_admin_header) to catch notices registered late, too.
	 *
	 * @return void
	 */
	public function hide_notices() {
		add_action(
			'in_admin_header',
			function () {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
				remove_all_actions( 'network_admin_notices' );
				remove_all_actions( 'user_admin_notices' );
			},
			9999
		);
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
	 * Germanized Pro release highlights, rendered as spotlights.
	 *
	 * @return array
	 */
	private function get_pro_release_features() {
		return array(
			array(
				'id'      => 'b2b',
				'title'   => 'B2B-Shop mit Kundengruppen',
				'pro'     => true,
				'icon'    => 'dashicons-groups',
				'text'    => array(
					'Mit Germanized Pro 5.0 wird dein WooCommerce Shop zum B2B- oder Hybrid-Shop: Lege Kundengruppen an und biete Geschäftskunden eigene Preise, Rabatte und Konditionen.',
				),
				'bullets' => array(
					'Gruppenpreise, Rabatte und Staffelpreise inkl. Staffelpreis-Tabelle',
					'Separate B2B-Registrierung mit manueller Prüfung, eigenen Feldern und Dokumenten-Upload',
					'Produkte, Kategorien, Zahlungs- und Versandarten pro Kundengruppe einschränken',
					'Netto-Preisanzeige, Mindestbestellwert und eigene Rechtstexte pro Kundengruppe',
				),
				'links'   => array(
					array(
						'label'    => 'B2B einrichten',
						'url'      => admin_url( 'admin.php?page=wc-settings&tab=germanized-b2b' ),
						'primary'  => true,
						'pro_only' => true,
					),
					array(
						'label' => 'Mehr erfahren',
						'url'   => 'https://vendidero.de/woocommerce-germanized/features#b2b',
					),
				),
			),
			array(
				'id'      => 'datev',
				'title'   => 'DATEV-Export',
				'pro'     => true,
				'icon'    => 'dashicons-media-spreadsheet',
				'text'    => array(
					'Übergib deine Rechnungen und Stornierungen im DATEV-Format an deinen Steuerberater - inkl. Erlöskonten, Debitorenkonten und Zahlungsbuchungen.',
				),
				'bullets' => array(
					'Erlöskonten automatisch nach Steuersatz, Land und Buchungsgruppe zuordnen',
					'Debitorenkonten automatisch vergeben, z.B. bei der Registrierung',
					'Zahlungen je Zahlungsart auf eigene Geldkonten buchen',
					'Leistungsdatum, Wirtschaftsjahr sowie Berater- und Mandantennummer',
				),
				'links'   => array(
					array(
						'label'    => 'DATEV-Export einrichten',
						'url'      => admin_url( 'admin.php?page=sab-accounting-export&export_type=datev&document_type=invoice' ),
						'primary'  => true,
						'pro_only' => true,
					),
					array(
						'label' => 'Mehr erfahren',
						'url'   => 'https://vendidero.de/woocommerce-germanized/features#accounting',
					),
				),
			),
		);
	}

	/**
	 * Performance improvements of the current core release.
	 *
	 * @return array
	 */
	private function get_performance_items() {
		return array(
			array(
				'icon'  => 'dashicons-database',
				'title' => 'Weniger Datenbankabfragen',
				'text'  => 'Checkbox-Einstellungen werden vorgeladen, Steuersatz-Abfragen zwischengespeichert und Hersteller über den Term-Cache ermittelt.',
			),
			array(
				'icon'  => 'dashicons-performance',
				'title' => 'Schnellere Ladezeiten',
				'text'  => 'Hook-Prioritäten werden dauerhaft zwischengespeichert und nur bei Theme- oder Plugin-Änderungen neu ermittelt.',
			),
			array(
				'icon'  => 'dashicons-update',
				'title' => 'Aufräumen in Etappen',
				'text'  => 'Nicht aktivierte Kundenkonten werden schrittweise im Hintergrund bereinigt - auch bei sehr vielen Konten.',
			),
		);
	}

	/**
	 * Highlighted features of the latest versions, newest first.
	 *
	 * @return array
	 */
	private function get_features() {
		$image_url = WC_germanized()->plugin_url() . '/assets/images/';

		return array(
			array(
				'id'    => 'legal-guarantee',
				'title' => 'EU Garantie- und Gewährleistungslabel',
				'wide'  => true,
				'image' => $image_url . 'legal-guarantee/legal_guarantee_notice-de.png',
				'text'  => array(
					'Ab dem 27.09.2026 sind die neuen EU Labels für Gewährleistung (und Garantie) darzustellen. Verpflichtend wird die Darstellung für alle Händler, die physische Produkte B2C, d.h. an Verbraucher, verkaufen.',
					'Germanized 4.1 bereitet deinen WooCommerce Shop auf diese neuen Richtlinien vor, damit du die Labels vorab ausführlich testen und einbinden kannst. Bitte prüfe deine Einstellungen - das neue EU Gewährleistungslabel wird standardmäßig im Footer und in der Kasse angezeigt.',
				),
				'links' => array(
					array(
						'label'   => 'Einstellungen anpassen',
						'url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-shopmarks&section=guarantee_labels' ),
						'primary' => true,
					),
					array(
						'label' => 'Mehr erfahren',
						'url'   => 'https://vendidero.de/eu-gewaehrleistungs-und-garantie-label-fuer-woocommerce',
					),
				),
			),
			array(
				'id'    => 'withdrawal-button',
				'title' => 'Widerrufsbutton für WooCommerce',
				'wide'  => true,
				'image' => $image_url . 'order-withdrawal-button.png',
				'text'  => array(
					'Ab dem 19.06.2026 gilt EU-weit die Pflicht zur Einführung eines Widerrufsbuttons für Online-Verträge über Waren und Dienstleistungen. Germanized stellt alle benötigten Funktionalitäten zur Erfüllung dieser neuen Vorschriften bereit.',
					'Die Seite zum Widerruf des Vertrags wurde als Entwurf angelegt - prüfe und veröffentliche sie rechtzeitig und verlinke sie z.B. im Footer deines Shops. Neue E-Mail-Vorlagen informieren dich über neue Widerrufe und bestätigen deinen Kunden den Eingang.',
				),
				'links' => array(
					array(
						'label'   => 'Einstellungen verwalten',
						'url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-general&section=withdrawal_button' ),
						'primary' => true,
					),
					array(
						'label' => 'Mehr erfahren',
						'url'   => 'https://vendidero.de/widerrufsbutton-rechtssicher-in-woocommerce-umsetzen',
					),
				),
			),
			array(
				'id'    => 'e-invoice',
				'title' => 'E-Rechnungen einfach erstellen (lassen)',
				'pro'   => true,
				'image' => $image_url . 'e-invoice.png',
				'text'  => array(
					'Erstelle E-Rechnungen im ZUGFeRD Format in verschiedenen Profilen (z.B. Comfort, Extended, XRechnung) - voll kompatibel mit der EU-Spezifikation EN16931. Auf Wunsch automatisch und als PDF/A inkl. strukturierter Rechnungsdaten.',
				),
				'links' => array(
					array(
						'label' => 'Mehr erfahren',
						'url'   => 'https://vendidero.de/woocommerce-germanized/features#accounting',
					),
				),
			),
			array(
				'id'    => 'shiptastic',
				'title' => 'Goodbye Shipments, hello Shiptastic!',
				'image' => $image_url . 'shiptastic.png',
				'text'  => array(
					'Die Sendungsintegration ist jetzt ein eigenständiges Plugin: <a href="https://wordpress.org/plugins/shiptastic-for-woocommerce" target="_blank">Shiptastic für WooCommerce</a>. Funktional hat sich wenig verändert, technisch wurden Präfixe, Tabellen und Klassen umbenannt. Hast du individuelle Anpassungen vorgenommen, empfehlen wir deine Integration zu aktualisieren.',
				),
				'links' => array(
					array(
						'label' => 'Zur Migration',
						'url'   => 'https://vendidero.de/doc/woocommerce-germanized/shipments-zu-shiptastic-migration',
					),
				),
			),
			array(
				'id'    => 'multistep-checkout',
				'title' => 'Block: Mehrstufige Kasse',
				'pro'   => true,
				'image' => $image_url . 'multilevel-checkout.png',
				'text'  => array(
					'Die mehrstufige Kasse für die block-basierte WooCommerce Kasse - mit Breadcrumb-Navigation, Bestätigungsseite, einer pro Schritt aktualisierten Zusammenfassung und optimiert für mobile Endgeräte.',
				),
				'links' => array(
					array(
						'label' => 'Mehr erfahren',
						'url'   => 'https://vendidero.de/woocommerce-germanized/features#multistep-checkout',
					),
				),
			),
			array(
				'id'    => 'gpsr',
				'title' => 'Produktsicherheit (GPSR)',
				'image' => $image_url . 'gpsr.png',
				'text'  => array(
					'Erweitere deine Produktdaten gemäß der Produktsicherheitsverordnung, z.B. um verknüpfte Hersteller samt Adressangaben und sicherheitsrelevante Dokumente. Die Ausgabe erfolgt standardmäßig in einem eigenen Tab und ist über die Preisauszeichnungen anpassbar.',
				),
				'links' => array(
					array(
						'label' => 'Mehr erfahren',
						'url'   => 'https://vendidero.de/doc/woocommerce-germanized/allgemeine-produktsicherheit-gpsr',
					),
				),
			),
			array(
				'id'    => 'hermes',
				'title' => 'Hermes Schnittstelle',
				'pro'   => true,
				'image' => $image_url . 'hermes-label.png',
				'text'  => array(
					'Erstelle Versand- und Retouren-Labels (auch als QR-Code) für Hermes und biete den Versand an Hermes Paketshops über die integrierte Abholstationen-Auswahl im Checkout an.',
				),
				'links' => array(
					array(
						'label' => 'Mehr erfahren',
						'url'   => 'https://vendidero.de/doc/shiptastic/hermes-integration-einrichten',
					),
				),
			),
		);
	}

	private function render_feature( $feature, $is_spotlight = false ) {
		$feature = wp_parse_args(
			$feature,
			array(
				'id'      => '',
				'title'   => '',
				'pro'     => false,
				'wide'    => false,
				'image'   => '',
				'icon'    => '',
				'text'    => array(),
				'bullets' => array(),
				'links'   => array(),
			)
		);

		$is_pro_locked = $feature['pro'] && ! WC_germanized()->is_pro();
		$classes       = array( 'wc-gzd-about-feature', 'wc-gzd-about-feature-' . $feature['id'] );

		if ( $is_spotlight ) {
			$classes[] = 'wc-gzd-about-feature-spotlight';
		}

		if ( $feature['wide'] && ! $is_spotlight ) {
			$classes[] = 'wc-gzd-about-feature-wide';
		}

		if ( $feature['pro'] ) {
			$classes[] = 'wc-gzd-about-feature-is-pro';
		}

		if ( empty( $feature['image'] ) && ! empty( $feature['icon'] ) ) {
			$classes[] = 'wc-gzd-about-feature-has-icon';
		}
		?>
		<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<div class="wc-gzd-about-feature-media">
				<?php if ( ! empty( $feature['image'] ) ) : ?>
					<img src="<?php echo esc_url( $feature['image'] ); ?>" alt="<?php echo esc_attr( $feature['title'] ); ?>" loading="lazy" />
				<?php elseif ( ! empty( $feature['icon'] ) ) : ?>
					<span class="dashicons <?php echo esc_attr( $feature['icon'] ); ?>" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<div class="wc-gzd-about-feature-body">
				<h3><?php echo esc_html( $feature['title'] ); ?><?php echo $feature['pro'] ? ' <span class="wc-gzd-pro wc-gzd-pro-outlined">pro</span>' : ''; ?></h3>
				<?php foreach ( $feature['text'] as $paragraph ) : ?>
					<p><?php echo wp_kses_post( $paragraph ); ?></p>
				<?php endforeach; ?>
				<?php if ( ! empty( $feature['bullets'] ) ) : ?>
					<ul class="wc-gzd-about-feature-bullets">
						<?php foreach ( $feature['bullets'] as $bullet ) : ?>
							<li><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php echo wp_kses_post( $bullet ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<div class="wc-gzd-about-feature-actions">
					<?php foreach ( $feature['links'] as $link ) : ?>
						<?php
						if ( ! empty( $link['pro_only'] ) && $is_pro_locked ) {
							continue;
						}

						$is_external = 0 !== strpos( $link['url'], admin_url() );
						$is_primary  = ! empty( $link['primary'] ) && ! $is_pro_locked;
						?>
						<a href="<?php echo esc_url( $link['url'] ); ?>" class="button <?php echo esc_attr( $is_primary ? 'button-primary' : '' ); ?>" <?php echo $is_external ? 'target="_blank"' : ''; ?>><?php echo esc_html( $link['label'] ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		</article>
		<?php
	}

	/**
	 * Output the about screen.
	 */
	public function about_screen() {
		// Delete the redirect transient
		delete_transient( '_wc_gzd_activation_redirect' );

		// Flush after upgrades
		if ( ! empty( $_GET['wc-gzd-updated'] ) || ! empty( $_GET['wc-gzd-installed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			flush_rewrite_rules();
		}

		$features = $this->get_features();

		if ( ! empty( $_GET['wc-gzd-installed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$message = 'Super, alles erledigt!';
		} elseif ( ! empty( $_GET['wc-gzd-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$message = 'Danke, dass du auf die neueste Version aktualisiert hast!';
		} else {
			$message = 'Danke für die Installation!';
		}
		?>
		<div class="wrap wc-gzd-about">
			<h1 class="screen-reader-text">Willkommen bei Germanized</h1>
			<hr class="wp-header-end" />

			<section class="wc-gzd-about-hero">
				<a class="wc-gzd-about-hero-tutorial" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized&tutorial=yes' ) ); ?>"><span class="dashicons dashicons-welcome-learn-more" aria-hidden="true"></span>Tutorial starten</a>
				<div class="wc-gzd-about-hero-brand">
					<a class="wc-gzd-about-logo" href="https://vendidero.de/woocommerce-germanized" target="_blank">Germanized</a>
				</div>
				<div class="wc-gzd-about-hero-content">
					<div class="wc-gzd-about-hero-title">
						<h2><?php echo esc_html( $message ); ?></h2>
						<span class="wc-gzd-pro wc-gzd-pro-outlined">Version <?php echo esc_html( WC_germanized()->version ); ?></span>
					</div>
					<p class="wc-gzd-about-lead">Germanized 4.2 macht deinen Shop spürbar schneller - und mit Germanized Pro 5.0 kommen B2B-Funktionen und der DATEV-Export dazu. Hier findest du die wichtigsten Neuerungen im Überblick.</p>
				</div>
				<div class="wc-gzd-about-hero-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ); ?>" class="button button-primary">Zu den Einstellungen</a>
				</div>
			</section>

			<section class="wc-gzd-about-section">
				<header class="wc-gzd-about-section-header">
					<span class="wc-gzd-about-eyebrow">Im Fokus</span>
					<h2>Neu in Germanized Pro 5.0</h2>
				</header>
				<div class="wc-gzd-about-spotlights">
					<?php foreach ( $this->get_pro_release_features() as $feature ) : ?>
						<?php $this->render_feature( $feature, true ); ?>
					<?php endforeach; ?>
				</div>
			</section>

			<?php if ( ! WC_germanized()->is_pro() ) : ?>
				<section class="wc-gzd-about-pro-banner">
					<div class="wc-gzd-about-pro-banner-content">
						<span class="wc-gzd-about-eyebrow">Germanized Pro</span>
						<h2>Mehr Funktionen. Premium Support.</h2>
						<ul>
							<li><span class="dashicons dashicons-yes" aria-hidden="true"></span>(E-)Rechnungen, Lieferscheine &amp; DATEV-Export</li>
							<li><span class="dashicons dashicons-yes" aria-hidden="true"></span>B2B mit Kundengruppen &amp; Staffelpreisen</li>
							<li><span class="dashicons dashicons-yes" aria-hidden="true"></span>Mehrstufige Kasse</li>
							<li><span class="dashicons dashicons-yes" aria-hidden="true"></span>Rechtstexte-Generator &amp; USt-ID-Prüfung</li>
						</ul>
					</div>
					<div class="wc-gzd-about-pro-banner-cta">
						<p class="wc-gzd-about-price"><small>ab</small> 89 €</p>
						<p class="wc-gzd-about-price-desc">inkl. MwSt. - inkl. 1 Jahr Updates &amp; Premium Support</p>
						<a href="https://vendidero.de/woocommerce-germanized#upgrade" target="_blank" class="wc-gzd-about-button-light">Jetzt upgraden</a>
					</div>
				</section>
			<?php endif; ?>

			<section class="wc-gzd-about-section">
				<header class="wc-gzd-about-section-header">
					<span class="wc-gzd-about-eyebrow">Performance</span>
					<h2>Schneller mit Germanized 4.2</h2>
				</header>
				<div class="wc-gzd-about-mini-grid">
					<?php foreach ( $this->get_performance_items() as $item ) : ?>
						<div class="wc-gzd-about-mini">
							<span class="wc-gzd-about-mini-icon dashicons <?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></span>
							<h4><?php echo esc_html( $item['title'] ); ?></h4>
							<p><?php echo esc_html( $item['text'] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="wc-gzd-about-section">
				<header class="wc-gzd-about-section-header">
					<span class="wc-gzd-about-eyebrow">Highlights</span>
					<h2>Neuerungen der letzten Versionen</h2>
				</header>
				<div class="wc-gzd-about-grid">
					<?php foreach ( $features as $feature ) : ?>
						<?php $this->render_feature( $feature ); ?>
					<?php endforeach; ?>
				</div>
			</section>


			<p class="wc-gzd-about-footer">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ); ?>"><?php esc_html_e( 'Go to Germanized Settings', 'woocommerce-germanized' ); ?> &rarr;</a>
			</p>
		</div>
		<?php
	}
}

new WC_GZD_Admin_Welcome();

?>
