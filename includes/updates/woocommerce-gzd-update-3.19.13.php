<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dispute_options = array(
	'woocommerce_gzd_alternative_complaints_text_none',
	'woocommerce_gzd_alternative_complaints_text_willing',
	'woocommerce_gzd_alternative_complaints_text_obliged'
);

$sentences_to_remove = array(
	'The european commission provides',
	'Consumers may use this platform',
	'Die Europäische Kommission stellt',
);

foreach( $dispute_options as $option_name ) {
	$option_value = get_option( $option_name );

	if ( ! empty( $option_value ) && is_string( $option_value ) ) {
		$sentences     = explode( '. ', $option_value );
		$new_sentences = array();

		if ( count( $sentences ) >= 2 ) {
			foreach( $sentences as $sentence ) {
				$include_sentence = true;

				foreach( $sentences_to_remove as $search ) {
					$haystack_clean = strtolower( preg_replace( '/\s+/', '', $sentence ) );
					$search_clean   = strtolower( preg_replace( '/\s+/', '', $search ) );

					if ( strstr( $haystack_clean, $search_clean ) ) {
						$include_sentence = false;
						break;
					}
				}

				if ( $include_sentence ) {
					$new_sentences[] = $sentence;
				}
			}

			if ( ! empty( $new_sentences ) ) {
				update_option( $option_name, implode( '. ', $new_sentences ) );
			}
		}
	}
}

/**
 * Show legal news note
 */
WC_GZD_Admin_Notices::instance()->activate_legal_news_note();
