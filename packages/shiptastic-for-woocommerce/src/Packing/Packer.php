<?php
namespace Vendidero\Shiptastic\Packing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Packer {
	protected $packer = null;

	public function __construct() {
		$this->packer = new \DVDoug\BoxPacker\InfalliblePacker();
	}

	public function set_max_boxes_to_balance_weight( $max_boxes ) {
		$this->packer->setMaxBoxesToBalanceWeight( $max_boxes );
	}

	public function set_boxes( $boxes ) {
		if ( ! is_a( $boxes, 'Vendidero\Shiptastic\Packing\PackagingList' ) ) {
			$first_box = ! empty( $boxes ) ? array_values( $boxes )[0] : false;

			if ( ! empty( $boxes ) && ! is_a( $first_box, 'Vendidero\Shiptastic\Packing\PackagingBox' ) ) {
				$boxes = PackagingList::fromArray( Helper::get_packaging_boxes( $boxes ) );
			} else {
				$boxes = PackagingList::fromArray( $boxes );
			}
		}

		$this->packer->setBoxes( $boxes );
	}

	public function set_items( $items ) {
		$this->packer->setItems( $items );
	}

	public function pack() {
		return $this->packer->pack();
	}

	public function get_unpacked_items() {
		return $this->packer->getUnpackedItems();
	}
}
