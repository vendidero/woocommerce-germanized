<?php

namespace Vendidero\Shiptastic\Packing;

class Logger implements \Psr\Log\LoggerInterface {

	protected $logger = null;

	/**
	 * @throws \Exception
	 */
	public function __construct() {
		$logger = wc_get_logger();

		if ( ! $logger ) {
			throw new \Exception( 'No logger found.' );
		}

		$this->logger = $logger;
	}

	protected function get_context( $context ) {
		$context = array_merge(
			$context,
			array(
				'source' => 'wc-shiptastic-packing',
			)
		);

		return $context;
	}

	public function log( $level, $message, $context = array() ) {
		$this->logger->log( $level, $message, $this->get_context( $context ) );
	}

	public function emergency( $message, $context = array() ) {
		$this->logger->emergency( $message, $this->get_context( $context ) );
	}

	public function alert( $message, $context = array() ) {
		$this->logger->alert( $message, $this->get_context( $context ) );
	}

	public function critical( $message, $context = array() ) {
		$this->logger->critical( $message, $this->get_context( $context ) );
	}

	public function error( $message, $context = array() ) {
		$this->logger->error( $message, $this->get_context( $context ) );
	}

	public function warning( $message, $context = array() ) {
		$this->logger->warning( $message, $this->get_context( $context ) );
	}

	public function notice( $message, $context = array() ) {
		$this->logger->notice( $message, $this->get_context( $context ) );
	}

	public function info( $message, $context = array() ) {
		$this->logger->info( $message, $this->get_context( $context ) );
	}

	public function debug( $message, array $context = array() ) {
		$this->logger->debug( $message, $this->get_context( $context ) );
	}
}
