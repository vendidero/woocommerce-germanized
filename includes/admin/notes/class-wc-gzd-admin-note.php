<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
abstract class WC_GZD_Admin_Note {

	abstract public function get_name();

	protected function get_name_prefixed() {
		return 'wc-gzd-admin-' . str_replace( '_', '-', $this->get_name() ) . '-notice';
	}

	public function exists() {
		if ( $note = $this->get_note() ) {
			return true;
		}

		return false;
	}

	protected function get_note() {
		try {
			$data_store = \WC_Data_Store::load( 'admin-note' );

			if ( ! $data_store ) {
				return false;
			}

			$note_ids = $data_store->get_notes_with_name( $this->get_name_prefixed() );

			if ( empty( $note_ids ) ) {
				return false;
			}

			$note_id = $note_ids[0];

			return WC_GZD_Admin_Notices::instance()->get_woo_note( $note_id );
		} catch( Exception $e ) {
			return false;
		}
	}

	protected function is_pro() {
		return false;
	}

	public function enable_notices() {
		return WC_GZD_Admin_Notices::instance()->enable_notices();
	}

	public function is_disabled() {

		if ( ! $this->enable_notices() && $this->is_dismissable() ) {
			return true;
		}

		if ( $this->is_pro() && WC_germanized()->is_pro() ) {
			$this->dismiss();

			return true;
		}

		if ( $note = $this->get_note() ) {
			if ( 'disabled' === $note->get_status() || 'deactivated' === $note->get_status() ) {
				return true;
			} else {
				return false;
			}
		} else {
			if ( $this->is_deactivatable() && 'yes' === get_option( $this->get_deactivate_option_name() ) ) {
				return true;
			}

			return get_option( $this->get_dismiss_option_name() ) === 'yes' ? true : false;
		}
	}

	abstract public function get_title();

	abstract public function get_content();

	public function get_type() {
		return 'update';
	}

	public function is_dismissable() {
		return true;
	}

	public function is_deactivatable() {
		return false;
	}

	protected function get_icon_type() {
		return 'info';
	}

	public function has_actions() {
		$actions = $this->get_actions();

		return empty( $actions ) ? false : true;
	}

	protected function add() {
		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$supports_notes = true;

		try {
			$data_store = \WC_Data_Store::load( 'admin-note' );
		} catch( Exception $e ) {
			$supports_notes = false;
		}

		if ( ! $supports_notes || in_array( $screen_id, array( 'dashboard', 'plugins' ) ) ) {
			// Use fallback
			add_action( 'admin_notices', array( $this, 'add_fallback' ), 10 );
			return;
		}

		if ( $this->exists() ) {
			return;
		}

		$note = WC_GZD_Admin_Notices::instance()->get_woo_note();

		$note->set_title( $this->get_title() );
		$note->set_content( $this->convert_content( $this->get_content() )   );
		$note->set_type( $this->get_type() );
		$note->set_name( $this->get_name_prefixed() );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-germanized' );

		foreach( $this->get_actions() as $action ) {

			$action = wp_parse_args( $action, array(
				'title'      => '',
				'url'        => '',
				'is_primary' => true,
			) );

			$note->add_action(
				sanitize_key( $action['title'] ),
				$action['title'],
				$action['url'],
				'disabled',
				$action['is_primary'] ? true : false
			);
		}

		if ( $this->is_dismissable() ) {
			$note->add_action(
				'close',
				$this->get_dismiss_text(),
				false,
				'disabled'
			);
		}

		if ( $this->is_deactivatable() ) {
			$note->add_action(
				'deactivate',
				$this->get_deactivate_text(),
				false,
				'deactivated'
			);
		}

		$note->save();
	}

	protected function convert_content( $content ) {
		// Convert list tags to <br/> to enable at least some kind of formatting.
		$content = str_replace( '</li>', '<br/>', $content );
		$content = str_replace( '<li>', '✓ &nbsp;', $content );

		return $content;
	}

	public function get_dismiss_text() {
		return __( 'Not now', 'woocommerce-germanized' );
	}

	public function get_deactivate_text() {
		return __( 'Deactivate', 'woocommerce-germanized' );
	}

	public function add_fallback() {
		$notice = $this;

		include( WC_germanized()->plugin_path() . '/includes/admin/views/html-notice-fallback.php' );
	}

	public function get_dismiss_url() {
		$name = str_replace( '_', '-', $this->get_name() );

		return add_query_arg( 'notice', 'wc-gzd-hide-' . $name . '-notice', add_query_arg( 'nonce', wp_create_nonce( 'wc-gzd-hide-' . $name . '-notice' ) ) );
	}

	public function get_deactivate_url() {
		$name = str_replace( '_', '-', $this->get_name() );

		return add_query_arg( 'notice', 'wc-gzd-disable-' . $name . '-notice', add_query_arg( 'nonce', wp_create_nonce( 'wc-gzd-disable-' . $name . '-notice' ) ) );
	}

	protected function get_dismiss_option_name() {
		$name = $this->get_name();

		return '_wc_gzd_hide_' . $name . '_notice';
	}

	protected function get_deactivate_option_name() {
		$name = $this->get_name();

		return '_wc_gzd_disable_' . $name . '_notice';
	}

	public function get_actions() {
		return array();
	}

	protected function get_days_until_show() {
		return 0;
	}

	public function dismiss( $and_note = true ) {

		if ( $and_note && ( $note = $this->get_note() ) ) {
			$note->set_status( 'disabled' );
			$note->save();
		}

		update_option( $this->get_dismiss_option_name(), 'yes' );
 	}

 	public function delete_note() {
	    if ( $note = $this->get_note() ) {
	    	$note->delete( true );
	    }
    }

    public function get_fallback_notice_type() {
		return 'error';
    }

	public function deactivate( $and_note = true ) {

		if ( $and_note && ( $note = $this->get_note() ) ) {
			$note->set_status( 'deactivated' );
			$note->save();
		}

		update_option( $this->get_deactivate_option_name(), 'yes' );
	}

 	public function reset() {
	    if ( $note = $this->get_note() ) {

	    	if ( 'deactivate' !== $note->get_status() ) {
			    $note->delete( true );
		    }
	    }

	    delete_option( $this->get_dismiss_option_name() );
    }

	public function queue() {
		$queue = $this->is_disabled() ? false : true;

		if ( $queue && $this->get_days_until_show() > 0 ) {
			$queue = false;
			$days  = $this->get_days_until_show();

			if ( get_option( 'woocommerce_gzd_activation_date' ) ) {
				$activation_date = ( get_option( 'woocommerce_gzd_activation_date' ) ? get_option( 'woocommerce_gzd_activation_date' ) : date( 'Y-m-d' ) );
				$diff            = WC_germanized()->get_date_diff( $activation_date, date( 'Y-m-d' ) );

				if ( $diff['d'] >= absint( $days ) ) {
					$queue = true;
				}
			}
		}

		if ( $queue ) {

			if ( $note = $this->get_note() ) {
				$note->set_status( 'unactioned' );
				$note->save();
			}

			$this->add();
		} else {
			if ( $note = $this->get_note() ) {

				if ( 'unactioned' === $note->get_status() ) {
					$note->set_status( 'actioned' );
					$note->save();
				}
			}
		}
	}
 }