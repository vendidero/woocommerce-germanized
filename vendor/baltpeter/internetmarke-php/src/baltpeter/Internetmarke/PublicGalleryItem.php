<?php

namespace baltpeter\Internetmarke;

class PublicGalleryItem extends ApiResult {
    /**
     * @var string Technical designation of the image category
     */
    protected $category;
    /**
     * @var string Category and type of image
     */
    protected $categoryDescription;
    /**
     * @var int Technical ID, which uniquely identifies the category
     */
    protected $categoryId;
    /**
     * @var array An array of the images
     */
    protected $images;

    /**
     * PublicGalleryItem constructor.
     *
     * @param string $category
     * @param string $category_description
     * @param int $category_id
     * @param array $images
     */
    public function __construct($category, $category_description, $category_id, array $images = null) {
        $this->setCategory($category);
        $this->setCategoryDescription($category_description);
        $this->setCategoryId($category_id);
        $this->setImages($images);
    }

    /**
     * @return string
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory($category) {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getCategoryDescription() {
        return $this->categoryDescription;
    }

    /**
     * @param string $categoryDescription
     */
    public function setCategoryDescription($categoryDescription) {
        $this->categoryDescription = $categoryDescription;
    }

    /**
     * @return int
     */
    public function getCategoryId() {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     */
    public function setCategoryId($categoryId) {
        $this->categoryId = $categoryId;
    }

    /**
     * @return array
     */
    public function getImages() {
        return $this->images;
    }

    /**
     * @param array $images
     */
    public function setImages($images) {
        $this->images = $images;
    }
}
