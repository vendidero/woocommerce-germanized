<?php

namespace baltpeter\Internetmarke;

class Margin extends ApiResult {
    /**
     * @var int Inner top margin size of the page format in millimeters
     */
    protected $top;
    /**
     * @var int Inner bottom margin size of the page format in millimeters
     */
    protected $bottom;
    /**
     * @var int Inner left margin size of the page format in millimeters
     */
    protected $left;
    /**
     * @var int Inner right margin size of the page format in millimeters
     */
    protected $right;

    /**
     * Margin constructor.
     *
     * @param int $top Inner top margin size of the page format in millimeters
     * @param int $bottom Inner bottom margin size of the page format in millimeters
     * @param int $left Inner left margin size of the page format in millimeters
     * @param int $right Inner right margin size of the page format in millimeters
     */
    public function __construct($top, $bottom, $left, $right) {
        $this->setTop($top);
        $this->setBottom($bottom);
        $this->setLeft($left);
        $this->setRight($right);
    }

    /**
     * @return int Inner top margin size of the page format in millimeters
     */
    public function getTop() {
        return $this->top;
    }

    /**
     * @param int $top Inner top margin size of the page format in millimeters
     */
    public function setTop($top) {
        $this->top = $top;
    }

    /**
     * @return int Inner bottom margin size of the page format in millimeters
     */
    public function getBottom() {
        return $this->bottom;
    }

    /**
     * @param int $bottom Inner bottom margin size of the page format in millimeters
     */
    public function setBottom($bottom) {
        $this->bottom = $bottom;
    }

    /**
     * @return int Inner left margin size of the page format in millimeters
     */
    public function getLeft() {
        return $this->left;
    }

    /**
     * @param int $left Inner left margin size of the page format in millimeters
     */
    public function setLeft($left) {
        $this->left = $left;
    }

    /**
     * @return int Inner right margin size of the page format in millimeters
     */
    public function getRight() {
        return $this->right;
    }

    /**
     * @param int $right Inner right margin size of the page format in millimeters
     */
    public function setRight($right) {
        $this->right = $right;
    }
}
