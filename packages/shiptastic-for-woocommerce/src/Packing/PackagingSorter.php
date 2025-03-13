<?php

namespace Vendidero\Shiptastic\Packing;

class PackagingSorter implements \DVDoug\BoxPacker\BoxSorter {

	public function compare( $box_a, $box_b ): int {
		$box_a_has_restrictions = $box_a->has_shipping_class_restrictions();
		$box_b_has_restrictions = $box_b->has_shipping_class_restrictions();
		$restriction_decider    = ! $box_a_has_restrictions <=> ! $box_b_has_restrictions; // Try boxes with a restriction first

		if ( 0 !== $restriction_decider ) {
			return $restriction_decider;
		}

		$box_a_volume   = $box_a->getInnerWidth() * $box_a->getInnerLength() * $box_a->getInnerDepth();
		$box_b_volume   = $box_b->getInnerWidth() * $box_b->getInnerLength() * $box_b->getInnerDepth();
		$volume_decider = $box_a_volume <=> $box_b_volume; // try smallest box first

		if ( 0 !== $volume_decider ) {
			return $volume_decider;
		}

		$empty_weight_decider = $box_a->getEmptyWeight() <=> $box_b->getEmptyWeight(); // with smallest empty weight

		if ( 0 !== $empty_weight_decider ) {
			return $empty_weight_decider;
		}

		// maximum weight capacity as decider
		$maximum_weight_decider = ( $box_a->getMaxWeight() - $box_b->getEmptyWeight() ) <=> ( $box_b->getMaxWeight() - $box_b->getEmptyWeight() );

		if ( 0 !== $maximum_weight_decider ) {
			return $maximum_weight_decider;
		}

		// costs decider
		$cost_decider = $box_a->get_costs() <=> $box_b->get_costs();

		return $cost_decider;
	}
}
