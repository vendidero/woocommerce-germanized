<?php
/**
 * Admin View: Settings pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$elements = array(
	'new' => '.page-title-action',
	'sort' => '.wc-gzd-legal-checkbox-sort',
	'edit' => '.wc-gzd-legal-checkbox-name',
	'locations' => '.wc-gzd-legal-checkbox-locations',
    'enabled' => '#woocommerce_gzd_legal_checkboxes_settings_terms_is_enabled',
	'label' => '#woocommerce_gzd_legal_checkboxes_settings_terms_label',
	'hide_input' => '#woocommerce_gzd_legal_checkboxes_settings_terms_hide_input',
);

?>

<ol class="tourbus-legs wc-gzd-tour" id="tour-settings-checkboxes">

    <?php if ( ! isset( $_GET['checkbox_id'] ) ) : ?>

	<li data-orientation="centered">
		<h2>WooCommerce Germanized Tour</h2>
		<p>Lerne jetzt schnell und einfach die ersten Schritte zur Konfiguration von WooCommerce Germanized kennen.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">Los geht's</a>
		<a class="button button-secondary" href="<?php echo WC_GZD_Admin::instance()->disable_tour_link( 'checkboxes' ); ?>" style="float: right">Nein, Danke</a>
	</li>

	<li class="wc-gzd-tour-pro" data-el="<?php echo $elements[ 'new' ]; ?>" data-orientation="bottom">
        <h2><span class="wc-gzd-pro">pro</span> Neue Checkbox hinzufügen</h2>
		<p>Insofern du über die Pro-Version von Germanized verfügst, kannst du über diesen Button eigene rechtl. Checkboxen hinzufügen und konfigurieren.</p>
        <a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
		<?php if ( ! WC_germanized()->is_pro() ) : ?>
            <a class="button button-secondary" style="float:right" href="https://vendidero.de/woocommerce-germanized" target="_blank">mehr erfahren</a>
		<?php endif; ?>
	</li>

	<li data-el="<?php echo $elements[ 'sort' ]; ?>" data-orientation="right">
		<h2>Sortieren</h2>
		<p>Deine rechtl. Checkboxen kannst du einfach via Drag & Drop sortieren. Nach dem Sortieren werden deine Einstellungen automatisch gespeichert und übernommen.</p>
		<a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
	</li>

    <li data-el="<?php echo $elements[ 'edit' ]; ?>" data-orientation="right">
        <h2>Bearbeiten</h2>
        <p>Du kannst über den Bearbeiten-Link Details der Checkbox bearbeiten. Dort kannst du dann z.B. die Beschriftung oder Fehlermeldung der Checkbox ändern.</p>
        <a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
    </li>

    <li data-el="<?php echo $elements[ 'locations' ]; ?>" data-orientation="left">
        <h2>Orte</h2>
        <p>Hier findest du eine Auflistung der Orte, bei denen die Checkbox angezeigt wird. Standardmäßig existiert die Kasse, die Registrierungs-Seite und die Bestell-Bezahlseite.</p>
        <a class="button button-primary" href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized&section=checkboxes&checkbox_id=terms' ); ?>">Tour fortsetzen</a>
    </li>

    <?php else: ?>

        <li data-el="<?php echo $elements[ 'enabled' ]; ?>" data-orientation="bottom">
            <h2>Aktivieren</h2>
            <p>Damit die Checkbox aktiviert wird, musst du diese Option aktivieren. Erst dann, wird die Checkbox auch ausgegeben. Natürlich kann die Anzeige bestimmter Checkboxen (z.B. die digitale Checkbox) noch von weiteren Faktoren abhängen.</p>
            <a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
        </li>

        <li data-el="<?php echo $elements[ 'label' ]; ?>" data-orientation="top">
            <h2>Beschriftung</h2>
            <p>Hier kannst du eine individuelle Beschriftung für deine Checkbox auswählen. Platzhalter dienen dir u.U. dazu, deine Beschritung mit dynamischen Elementen oder Optionen zu versehen.</p>
            <a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
        </li>

        <li data-el="<?php echo $elements[ 'hide_input' ]; ?>" data-orientation="top">
            <h2>Ausblenden</h2>
            <p>Du kannst für deine Checkbox auch auswählen, dass die eigentliche Checkbox (d.h. das Input-Feld) nicht mit ausgegeben, sondern lediglich der Hinweis bzw. deine Beschriftung platziert werden soll. In diesem Fall wird die Checkbox automatisch optional.</p>
            <a class="button button-primary tourbus-next" href="javascript:void(0);">weiter</a>
        </li>

        <li data-el=".submit" data-orientation="top">
            <h2>Einstellungen speichern</h2>
            <p>Wenn du deine Einstellungen angepasst hast, dann speichere sie über diesen Button.</p>

            <?php if ( WC_GZD_Admin::instance()->is_tour_enabled( 'email' ) ) : ?>
                <a class="button button-primary" href="<?php echo add_query_arg( array( 'section' => 'email' ), WC_GZD_Admin::instance()->disable_tour_link( 'checkboxes' ) ); ?>">Weiter zu den E-Mails</a>
            <?php else: ?>
                <a class="button button-primary" href="<?php echo WC_GZD_Admin::instance()->disable_tour_link( 'checkboxes' ); ?>">Tour beenden</a>
            <?php endif; ?>
        </li>

    <?php endif; ?>

</ol>