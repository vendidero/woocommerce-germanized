<?php

namespace baltpeter\Internetmarke;

class Position {
    /**
     * @var int Column on the page
     */
    protected $labelX;
    /**
     * @var int Row on the page
     */
    protected $labelY;
    /**
     * @var int Page number
     */
    protected $page;

    /**
     * Position constructor.
     *
     * @param int $label_x
     * @param int $label_y
     * @param int $page
     */
    public function __construct($label_x, $label_y, $page) {
        $this->setLabelX($label_x);
        $this->setLabelY($label_y);
        $this->setPage($page);
    }

    /**
     * @return int
     */
    public function getLabelX() {
        return $this->labelX;
    }

    /**
     * @param int $labelX
     */
    public function setLabelX($labelX) {
        $this->labelX = $labelX;
    }

    /**
     * @return int
     */
    public function getLabelY() {
        return $this->labelY;
    }

    /**
     * @param int $labelY
     */
    public function setLabelY($labelY) {
        $this->labelY = $labelY;
    }

    /**
     * @return int
     */
    public function getPage() {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage($page) {
        $this->page = $page;
    }
}
