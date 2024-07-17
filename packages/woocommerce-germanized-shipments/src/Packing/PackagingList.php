<?php

namespace Vendidero\Germanized\Shipments\Packing;

use DVDoug\BoxPacker\BoxSorter;

class PackagingList extends \DVDoug\BoxPacker\BoxList {

	public function __construct( $sorter = null ) {
		$sorter = new PackagingSorter();

		parent::__construct( $sorter );
	}
}
