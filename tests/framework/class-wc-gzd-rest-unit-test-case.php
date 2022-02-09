<?php
/**
 * WC REST API Unit Test Case (for WP-API Endpoints).
 *
 * Provides REST API specific methods and setup/teardown.
 *
 * @since 2.7
 */

class WC_GZD_REST_Unit_Test_Case extends WC_GZD_Unit_Test_Case {

	protected $server;

	/**
	 * Setup our test server.
	 */
	public function setUp() : void {
		parent::setUp();
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_Test_Spy_REST_Server;
		do_action( 'rest_api_init' );
	}

	/**
	 * Unset the server.
	 */
	public function tearDown() : void {
		parent::tearDown();
		global $wp_rest_server;
		$wp_rest_server = null;
	}
}
