<?php

namespace Vendidero\Shiptastic\Packing;

use DVDoug\BoxPacker\Box;
use DVDoug\BoxPacker\BoxSorter;
use DVDoug\BoxPacker\DefaultBoxSorter;
use ArrayIterator;
use Traversable;

class PackagingList extends \DVDoug\BoxPacker\BoxList {

	/**
	 * @var Box[]
	 */
	private $list = array();

	private $is_sorted = false;

	private $sorter;

	public function __construct( $sorter = null ) {
		$this->sorter = new PackagingSorter();

		parent::__construct( $sorter );
	}

	/**
	 * Do a bulk create.
	 *
	 * @param Box[] $boxes
	 */
	public static function fromArray( array $boxes, bool $pre_sorted = false ): self {
		$list            = new self();
		$list->list      = $boxes;
		$list->is_sorted = $pre_sorted;

		return $list;
	}

	/**
	 * @return Traversable<Box>
	 */
	public function getIterator(): Traversable {
		if ( ! $this->is_sorted ) {
			usort( $this->list, array( $this->sorter, 'compare' ) );
			$this->is_sorted = true;
		}

		return new ArrayIterator( $this->list );
	}

	public function insert( Box $item ): void {
		$this->list[] = $item;
	}
}
