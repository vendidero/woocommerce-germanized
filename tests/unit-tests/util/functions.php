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

		add_shortcode( 'another_simple_shortcode', function() {
			return 'shortcode_wrapper';
		} );

		$post_content = wc_gzd_get_post_plain_content( $post_id, array( 'another_simple_shortcode' ) );
		$this->assertEquals( '<p>Simple shortcode test.</p>shortcode_wrapper<p>Content clear</p>', $this->clean_newlines( $post_content ) );

		$post_id = wp_insert_post( array(
			'post_content' => '<p>[simple_shortcode arg1="2"][inner_shortcode]Simple shortcode test.[/inner_shortcode][/simple_shortcode]</p><p>[simple_shortcode arg1="2"][inner_shortcode] Simple shortcode test.[/simple_shortcode]</p>',
			'post_title'   => 'test',
		) );

		$post_content = wc_gzd_get_post_plain_content( $post_id );

		$this->assertEquals( '<p>Simple shortcode test.</p><p> Simple shortcode test.</p>', $this->clean_newlines( $post_content ) );

		$post_id = wp_insert_post( array(
			'post_content' => '<p>Does it work?</p>',
			'post_title'   => 'test',
		) );

		add_filter( 'the_content', function( $content ) {
			return $content . '[test]';
		}, 5000 );

		$post = get_post( $post_id );
		$content = $post->post_content;
		$content = apply_filters( 'the_content', $content );

		$this->assertEquals( '<p>Does it work?</p>[test]', $this->clean_newlines( $content ) );

		$post_content = wc_gzd_get_post_plain_content( $post_id );

		$this->assertEquals( '<p>Does it work?</p>', $this->clean_newlines( $post_content ) );

		$post_id = wp_insert_post( array(
			'post_content' => '[vc_row][vc_column width="1/4"][/vc_column][vc_column width="3/4"][vc_column_text]Does it work?[/vc_column_text][/vc_column][/vc_row]',
			'post_title'   => 'test',
		) );

		$post_content = wc_gzd_get_post_plain_content( $post_id );

		$this->assertEquals( '<p>Does it work?</p>', $this->clean_newlines( $post_content ) );
	}
}
