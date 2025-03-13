<?php

namespace baltpeter\Internetmarke;

class LabelSpacing extends ApiResult {
    /**
     * @var int Spacing between labels in the x direction in millimeters
     */
    protected $x;
    /**
     * @var int Spacing between labels in the y direction in millimeters
     */
    protected $y;

    /**
     * Size constructor.
     *
     * @param int $x Spacing between labels in the x direction in millimeters
     * @param int $y Spacing between labels in the y direction in millimeters
     */
    public function __construct($x, $y) {
        $this->setX($x);
        $this->setY($y);
    }

    /**
     * @return int Spacing between labels in the x direction in millimeters
     */
    public function getX() {
        return $this->x;
    }

    /**
     * @param int $x Spacing between labels in the x direction in millimeters
     */
    public function setX($x) {
        $this->x = $x;
    }

    /**
     * @return int Spacing between labels in the y direction in millimeters
     */
    public function getY() {
        return $this->y;
    }

    /**
     * @param int $y Spacing between labels in the y direction in millimeters
     */
    public function setY($y) {
        $this->y = $y;
    }
}
