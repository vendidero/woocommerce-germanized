<?php

namespace baltpeter\Internetmarke;

class LabelCount extends ApiResult {
    /**
     * @var int Number of label items in the x direction
     */
    protected $labelX;
    /**
     * @var int Number of label items in the y direction
     */
    protected $labelY;

    /**
     * LabelCount constructor.
     *
     * @param int $label_x Number of label items in the x direction
     * @param int $label_y Number of label items in the y direction
     */
    public function __construct($label_x, $label_y) {
        $this->setLabelX($label_x);
        $this->setLabelY($label_y);
    }

    /**
     * @return int Number of label items in the x direction
     */
    public function getLabelX() {
        return $this->labelX;
    }

    /**
     * @param int $labelX Number of label items in the x direction
     */
    public function setLabelX($labelX) {
        $this->labelX = $labelX;
    }

    /**
     * @return int Number of label items in the y direction
     */
    public function getLabelY() {
        return $this->labelY;
    }

    /**
     * @param int $labelY Number of label items in the y direction
     */
    public function setLabelY($labelY) {
        $this->labelY = $labelY;
    }
}
