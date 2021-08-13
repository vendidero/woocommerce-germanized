<?php

namespace baltpeter\Internetmarke;

use function DeepCopy\deep_copy;

class PageFormat extends ApiResult {
    /**
     * @var int Page format ID
     */
    protected $id;
    /**
     * @var bool true if addresses can be printed on the franking marks using the page format
     */
    protected $isAddressPossible;
    /**
     * @var bool true if image can be printed on the franking marks using the page format
     */
    protected $isImagePossible;
    /**
     * @var string Name of the page format (e.g. 'DIN A4 normal paper' or 'letter C5 162 x 229')
     */
    protected $name;
    /**
     * @var string Description of the page format
     */
    protected $description;
    /**
     * @var string Specification of the print medium (possible values: 'REGULARPAGE', 'ENVELOPE', 'LABELPRINTER', 'LABELPAGE')
     */
    protected $pageType;
    /**
     * @var PageLayout Description of the page layout in a structured format
     */
    protected $pageLayout;

    /**
     * PageFormat constructor.
     *
     * @param int $id
     * @param bool $is_address_possible
     * @param bool $is_image_possible
     * @param string $name
     * @param string $description
     * @param string $page_type
     * @param PageLayout $page_layout
     */
    public function __construct($id, $is_address_possible, $is_image_possible, $name, $description, $page_type, $page_layout) {
        $this->setId($id);
        $this->setIsAddressPossible($is_address_possible);
        $this->setIsImagePossible($is_image_possible);
        $this->setName($name);
        $this->setDescription($description);
        $this->setPageType($page_type);
        $this->setPageLayout($page_layout);
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return boolean
     */
    public function isIsAddressPossible() {
        return $this->isAddressPossible;
    }

    /**
     * @param boolean $isAddressPossible
     */
    public function setIsAddressPossible($isAddressPossible) {
        $this->isAddressPossible = $isAddressPossible;
    }

    /**
     * @return boolean
     */
    public function isIsImagePossible() {
        return $this->isImagePossible;
    }

    /**
     * @param boolean $isImagePossible
     */
    public function setIsImagePossible($isImagePossible) {
        $this->isImagePossible = $isImagePossible;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getPageType() {
        return $this->pageType;
    }

    /**
     * @param string $pageType
     */
    public function setPageType($pageType) {
        $this->pageType = $pageType;
    }

    /**
     * @return PageLayout
     */
    public function getPageLayout() {
        return $this->pageLayout;
    }

    /**
     * @param PageLayout $pageLayout
     */
    public function setPageLayout($pageLayout) {
        $this->pageLayout = deep_copy($pageLayout);
    }
}
