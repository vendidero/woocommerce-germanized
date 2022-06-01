<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_Template_Outdated extends WC_GZD_Admin_Note {

	protected $templates = null;

	protected $current_theme = null;

	protected function get_template_version_check_result() {
		if ( is_null( $this->templates ) ) {
			$this->templates = WC_GZD_Admin::instance()->get_template_version_check_result();
		}

		return $this->templates;
	}

	public function is_disabled() {
		$templates   = $this->get_template_version_check_result();
		$is_disabled = parent::is_disabled();

		if ( ! $is_disabled ) {
			$is_disabled = true;

			foreach ( $templates as $plugin => $data ) {
				if ( $data['has_outdated'] ) {
					$is_disabled = false;
					break;
				}
			}
		}

		return $is_disabled;
	}

	protected function get_current_theme() {
		if ( is_null( $this->current_theme ) ) {
			$this->current_theme = wp_get_theme();
		}

		return $this->current_theme;
	}

	public function get_name() {
		return 'template_outdated';
	}

	public function get_title() {
		return __( 'Your theme contains outdated Germanized template files', 'woocommerce-germanized' );
	}

	public function get_content() {
		$content  = __( 'These files may need updating to ensure they are compatible with the current version of Germanized. Suggestions to fix this:', 'woocommerce-germanized' );
		$content .= '<ol>
	        <li>' . esc_html__( 'Update your theme to the latest version. If no update is available contact your theme author asking about compatibility with the current Germanized version.', 'woocommerce-germanized' ) . '</li>
	        <li>' . esc_html__( 'If you copied over a template file to change something, then you will need to copy the new version of the template and apply your changes again.', 'woocommerce-germanized' ) . '</li>
	    </ol>';

		return $content;
	}

	public function get_actions() {
		return array(
			array(
				'url'        => admin_url( 'admin.php?page=wc-status&tab=germanized' ),
				'title'      => __( 'View affected templates', 'woocommerce-germanized' ),
				'target'     => '_self',
				'is_primary' => true,
			),
		);
	}
}
