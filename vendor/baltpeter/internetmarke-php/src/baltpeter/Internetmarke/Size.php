<?php

namespace baltpeter\Internetmarke;

class Size extends ApiResult {
    /**
     * @var int Width in millimeters
     */
    protected $x;
    /**
     * @var int Height in millimeters
     */
    protected $y;

    /**
     * Size constructor.
     *
     * @param int $x Width in millimeters
     * @param int $y Height in millimeters
     */
    public function __construct($x, $y) {
        $this->setX($x);
        $this->setY($y);
    }

    /**
     * @return int Width in millimeters
     */
    public function getX() {
        return $this->x;
    }

    /**
     * @param int $x Width in millimeters
     */
    public function setX($x) {
        $this->x = $x;
    }

    /**
     * @return int Height in millimeters
     */
    public function getY() {
        return $this->y;
    }

    /**
     * @param int $y Height in millimeters
     */
    public function setY($y) {
        $this->y = $y;
    }
}
