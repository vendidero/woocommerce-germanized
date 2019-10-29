<?php

/**
 * WC Unit Test Case
 *
 * Provides WooCommerce-specific setup/tear down/assert methods, custom factories,
 * and helper functions.
 *
 * @since 2.2
 */
class WC_GZD_Unit_Test_Case extends WP_UnitTestCase {

	/** @var WC_Unit_Test_Factory instance */
	protected $factory;

	/**
	 * Setup test case.
	 *
	 * @since 2.2
	 */
	public function setUp() {

		parent::setUp();

		// Add custom factories
		$this->factory = new WC_GZD_Unit_Test_Factory();

		$this->setOutputCallback( array( $this, 'filter_output' ) );

		WC_GZD_Post_types::register_taxonomies();
		WC_GZD_Install::create_units();
		WC_GZD_Install::create_labels();
	}

	/**
	 * Strip newlines and tabs when using expectedOutputString() as otherwise.
	 * the most template-related tests will fail due to indentation/alignment in.
	 * the template not matching the sample strings set in the tests.
	 *
	 * @since 2.2
	 */
	public function filter_output( $output ) {

		$output = preg_replace( '/[\n]+/S', '', $output );
		$output = preg_replace( '/[\t]+/S', '', $output );

		return $output;
	}

	/**
	 * Asserts thing is not WP_Error.
	 *
	 * @param mixed $actual
	 * @param string $message
	 *
	 * @since 2.2
	 */
	public function assertNotWPError( $actual, $message = '' ) {
		$this->assertNotInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Asserts thing is WP_Error.
	 *
	 * @param mixed $actual
	 * @param string $message
	 */
	public function assertIsWPError( $actual, $message = '' ) {
		$this->assertInstanceOf( 'WP_Error', $actual, $message );
	}
}
