<?php

namespace Vendidero\Shiptastic\Labels;

use Exception;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\Shipment;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Automation {

	/**
	 * Init the package - load the REST API Server class.
	 */
	public static function init() {
		add_action( 'woocommerce_shiptastic_shipment_before_status_change', array( __CLASS__, 'set_automation' ), 10, 2 );

		// Watch shipment creations - e.g. default status is set to shipped - needs to trigger label generation
		add_action( 'woocommerce_shiptastic_new_shipment', array( __CLASS__, 'set_after_create_automation' ), 10, 2 );
		add_action( 'woocommerce_shiptastic_new_return_shipment', array( __CLASS__, 'set_after_create_automation' ), 10, 2 );

		// After a label has been successfully created - maybe update shipment status
		add_action( 'woocommerce_shiptastic_shipment_created_label', array( __CLASS__, 'maybe_adjust_shipment_status' ), 10 );
		add_action( 'woocommerce_shiptastic_label_auto_sync_callback', array( __CLASS__, 'auto_sync_callback' ) );

		// Make sure the return label is being created before sending the return email to the customer
		add_filter( 'woocommerce_email_attachments', array( __CLASS__, 'maybe_force_return_email_attachments' ), 10, 4 );
	}

	/**
	 * @param array $attachments
	 * @param string $email_id
	 * @param \WC_Order $order
	 * @param \WC_STC_Email_Customer_Return_Shipment $email
	 *
	 * @return array
	 */
	public static function maybe_force_return_email_attachments( $attachments, $email_id, $shipment, $email ) {
		if ( 'customer_return_shipment' === $email_id ) {
			$shipment = $email->shipment;

			if ( ! $shipment->has_label() && $shipment->needs_label( false ) ) {
				if ( $provider = $shipment->get_shipping_provider_instance() ) {
					$auto_status = $provider->get_label_automation_shipment_status( $shipment );

					if ( $provider->automatically_generate_label( $shipment ) && $auto_status === $shipment->get_status() ) {
						self::cancel_deferred_sync( array( 'shipment_id' => $shipment->get_id() ) );
						self::create_label( $shipment->get_id(), $shipment );

						if ( $shipment->has_label() ) {
							$label = $shipment->get_label();

							if ( $file = $label->get_file() ) {
								$attachments[] = $file;
							}
						}
					}
				}
			}
		}

		return $attachments;
	}

	public static function auto_sync_callback( $shipment_id ) {
		/**
		 * Maybe cancel duplicate deferred syncs.
		 */
		self::cancel_deferred_sync( array( 'shipment_id' => $shipment_id ) );

		Package::log( 'Starting shipment #' . $shipment_id . ' label sync (deferred)' );

		self::create_label( $shipment_id );

		return true;
	}

	public static function cancel_deferred_sync( $args ) {
		$queue = WC()->queue();

		/**
		 * Cancel outstanding events and queue new.
		 */
		$queue->cancel_all( 'woocommerce_shiptastic_label_auto_sync_callback', $args, 'woocommerce-shiptastic-label-sync' );
	}

	/**
	 * @param Shipment $shipment
	 */
	public static function maybe_adjust_shipment_status( $shipment ) {
		if ( $provider = $shipment->get_shipping_provider_instance() ) {
			if ( $provider->automatically_set_shipment_status_shipped( $shipment ) ) {
				$shipment->set_status( 'shipped' );
			} else {
				$shipment->set_status( 'ready-for-shipping' );
			}
		}
	}

	public static function set_after_create_automation( $shipment_id, $shipment ) {
		self::init_automation( $shipment, array( 'is_hook' => false ) );
	}

	/**
	 * @param Shipment $shipment
	 * @param boolean $is_hook
	 */
	protected static function init_automation( $shipment, $args = array() ) {
		$label_type = ( 'return' === $shipment->get_type() ? 'return_label' : 'label' );

		$args = wp_parse_args(
			$args,
			array(
				'is_hook'             => true,
				'allow_deferred_sync' => wc_shiptastic_allow_deferred_sync( $label_type ),
			)
		);

		$disable = false;

		if ( ! $shipment->needs_label( false ) ) {
			$disable = true;
		}

		/**
		 * Filter that allows to disable automatically creating DHL labels for a certain shipment.
		 *
		 * @param boolean  $disable True if you want to disable automation.
		 * @param Shipment $shipment The shipment object.
		 *
		 */
		$disable = apply_filters( 'woocommerce_shiptastic_disable_label_auto_generate', $disable, $shipment );

		if ( $disable ) {
			return;
		}

		if ( $provider = $shipment->get_shipping_provider_instance() ) {
			$hook_prefix = 'woocommerce_shiptastic_' . ( 'return' === $shipment->get_type() ? 'return_' : '' ) . 'shipment_status_';
			$auto_status = $provider->get_label_automation_shipment_status( $shipment );

			if ( $provider->automatically_generate_label( $shipment ) && ! empty( $auto_status ) ) {
				$status = $auto_status;

				if ( $args['is_hook'] ) {
					add_action(
						$hook_prefix . $status,
						function ( $shipment_id, $shipment ) use ( $args ) {
							self::maybe_create_label( $shipment_id, $shipment, $args );
						},
						5,
						2
					);
				} elseif ( $shipment->has_status( $status ) ) {
					self::maybe_create_label( $shipment->get_id(), $shipment, $args );
				}
			}
		}
	}

	/**
	 * @param $shipment_id
	 * @param Shipment $shipment
	 */
	public static function set_automation( $shipment_id, $shipment ) {
		self::init_automation( $shipment );
	}

	public static function create_label( $shipment_id, $shipment = false ) {
		if ( ! $shipment ) {
			$shipment = wc_stc_get_shipment( $shipment_id );

			if ( ! $shipment ) {
				return;
			}
		}

		if ( ! $shipment->has_label() ) {
			$result = $shipment->create_label();

			if ( is_wp_error( $result ) ) {
				$result = wc_stc_get_shipment_error( $result );
			}

			if ( is_wp_error( $result ) ) {
				if ( $result->is_soft_error() ) {
					Package::log( sprintf( 'Info while automatically creating label for %1$s: %2$s', $shipment->get_shipment_number(), wc_print_r( $result->get_error_messages(), true ) ) );
				} else {
					Package::log( sprintf( 'Error while automatically creating label for %1$s: %2$s', $shipment->get_shipment_number(), wc_print_r( $result->get_error_messages(), true ) ) );
				}
			}
		}
	}

	protected static function maybe_create_label( $shipment_id, $shipment = false, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'allow_deferred_sync' => wc_shiptastic_allow_deferred_sync( 'label' ),
			)
		);

		$defer_args = array(
			'shipment_id' => $shipment_id,
		);

		/**
		 * Cancel outstanding events and queue new.
		 */
		self::cancel_deferred_sync( $defer_args );

		/**
		 * In case the shipment has  automatically been created within the same request
		 * and is already marked as shipped (e.g. due to default status set to shipped)
		 * make sure to prevent a deferred sync as the label would not get attached to the email.
		 */
		if ( $the_shipment = wc_stc_get_shipment( $shipment_id ) ) {
			if ( $the_shipment->has_status( 'shipped' ) ) {
				$args['allow_deferred_sync'] = false;
			}
		}

		if ( $args['allow_deferred_sync'] ) {
			Package::log( 'Deferring shipment #' . $shipment_id . ' label sync' );
			$queue = WC()->queue();

			$queue->schedule_single(
				time() + 50,
				'woocommerce_shiptastic_label_auto_sync_callback',
				$defer_args,
				'woocommerce-shiptastic-label-sync'
			);
		} else {
			self::create_label( $shipment_id, $shipment );
		}
	}
}
