<?php

/**
 * Class Functions.
 * @package WooCommerce\Tests\Product
 * @since 2.3
 */
class WC_GZD_Tests_Util_Functions extends WC_GZD_Unit_Test_Case {

	protected function clean_newlines( $post_content ) {
		return str_replace( array( "\r", "\n"), '', $post_content );
	}

	/**
	 * Tests wc_gzd_get_post_plain_content().
	 *
	 * @since 3.8.1
	 */
	public function test_wc_gzd_get_post_plain_content() {
		global $post;

		$post_id = wp_insert_post( array(
			'post_content' => '<p>[simple_shortcode arg1="2"]Simple shortcode test.[/simple_shortcode]</p><p>[another_simple_shortcode]</p><p>Content clear</p>',
			'post_title'   => 'test',
		) );

		$post_org_id = wp_insert_post( array(
			'post_content' => '<p>test</p>',
			'post_title'   => 'test org',
		) );

		$post = get_post( $post_org_id );
		setup_postdata( $post );

		$post_content = wc_gzd_get_post_plain_content( $post_id );

		$this->assertEquals( '<p>Simple shortcode test.</p><p>Content clear</p>', $this->clean_newlines( $post_content ) );
		$this->assertEquals( $post_org_id, $post->ID );

		$post_content = wc_gzd_get_post_plain_content( $post_id, array( 'another_simple_shortcode' ) );

		$this->assertEquals( '<p>Simple shortcode test.</p><p>[another_simple_shortcode]</p><p>Content clear</p>', $this->clean_newlines( $post_content ) );

		$post_id = wp_insert_post( array(
			'post_content' => '<p>[simple_shortcode arg1="2"][inner_shortcode]Simple shortcode test.[/inner_shortcode][/simple_shortcode]</p><p>[simple_shortcode arg1="2"][inner_shortcode] Simple shortcode test.[/simple_shortcode]</p>',
			'post_title'   => 'test',
		) );

		$post_content = wc_gzd_get_post_plain_content( $post_id );

		$this->assertEquals( '<p>Simple shortcode test.</p><p> Simple shortcode test.</p>', $this->clean_newlines( $post_content ) );
	}
}
