<?php

namespace baltpeter\Internetmarke;

use function DeepCopy\deep_copy;

class PageLayout extends ApiResult {
    /**
     * @var Size Dimension of the page format in millimeters in the x and y direction
     */
    protected $size;
    /**
     * @var string Page orientation (possible values: 'PORTRAIT' and 'LANDSCAPE')
     */
    protected $orientation;
    /**
     * @var LabelSpacing Spacing between labels in millimeters
     */
    protected $labelSpacing;
    /**
     * @var LabelCount Number of label items in the x and y direction
     */
    protected $labelCount;
    /**
     * @var Margin Inner margin size of the page format in millimeters
     */
    protected $margin;

    /**
     * PageLayout constructor.
     *
     * @param Size $size Dimension of the page format in millimeters in the x and y direction
     * @param string $orientation Page orientation (possible values: 'PORTRAIT' and 'LANDSCAPE')
     * @param LabelSpacing $label_spacing Spacing between labels in millimeters
     * @param LabelCount $label_count Number of label items in the x and y direction
     * @param Margin $margin Inner margin size of the page format in millimeters
     */
    public function __construct($size, $orientation, $label_spacing, $label_count, $margin) {
        $this->setSize($size);
        $this->setOrientation($orientation);
        $this->setLabelSpacing($label_spacing);
        $this->setLabelCount($label_count);
        $this->setMargin($margin);
    }

    /**
     * @return Size Dimension of the page format in millimeters in the x and y direction
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * @param Size $size Dimension of the page format in millimeters in the x and y direction
     */
    public function setSize($size) {
        $this->size = deep_copy($size);
    }

    /**
     * @return string Page orientation (possible values: 'PORTRAIT' and 'LANDSCAPE')
     */
    public function getOrientation() {
        return $this->orientation;
    }

    /**
     * @param string $orientation Page orientation (possible values: 'PORTRAIT' and 'LANDSCAPE')
     */
    public function setOrientation($orientation) {
        $this->orientation = $orientation;
    }

    /**
     * @return LabelSpacing Spacing between labels in millimeters
     */
    public function getLabelSpacing() {
        return $this->labelSpacing;
    }

    /**
     * @param LabelSpacing $labelSpacing Spacing between labels in millimeters
     */
    public function setLabelSpacing($labelSpacing) {
        $this->labelSpacing = deep_copy($labelSpacing);
    }

    /**
     * @return LabelCount Number of label items in the x and y direction
     */
    public function getLabelCount() {
        return $this->labelCount;
    }

    /**
     * @param LabelCount $labelCount Number of label items in the x and y direction
     */
    public function setLabelCount($labelCount) {
        $this->labelCount = deep_copy($labelCount);
    }

    /**
     * @return Margin Inner margin size of the page format in millimeters
     */
    public function getMargin() {
        return $this->margin;
    }

    /**
     * @param Margin $margin Inner margin size of the page format in millimeters
     */
    public function setMargin($margin) {
        $this->margin = deep_copy($margin);
    }
}
