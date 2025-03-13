<?php

namespace baltpeter\Internetmarke;

use function DeepCopy\deep_copy;

class OrderItem extends ApiResult {
    /**
     * @var int Deutsche Post’s internal product ID for the selected product
     */
    protected $productCode;
    /**
     * @var int ID of the image to be printed
     */
    protected $imageId;
    /**
     * @var AddressBinding Optional address details for Internet stamps in the address zone
     */
    protected $address;
    /**
     * @var Position Optional specification of the position of the Internet stamp on the PDF document
     */
    protected $position;
    /**
     * @var string Layout of the Internet stamp (possible values: 'AddressZone' and 'FrankingZone')
     */
    protected $voucherLayout;

    /**
     * OrderItem constructor.
     *
     * @param int $product_code
     * @param int $image_id
     * @param AddressBinding $address_binding
     * @param Position $position
     * @param string $voucher_layout
     */
    public function __construct($product_code, $image_id, $address_binding, $position, $voucher_layout) {
        $this->setProductCode($product_code);
        $this->setImageId($image_id);
        $this->setAddress($address_binding);
        $this->setPosition($position);
        $this->setVoucherLayout($voucher_layout);
    }

    /**
     * @return int
     */
    public function getProductCode() {
        return $this->productCode;
    }

    /**
     * @param int $productCode
     */
    public function setProductCode($productCode) {
        $this->productCode = $productCode;
    }

    /**
     * @return int
     */
    public function getImageId() {
        return $this->imageId;
    }

    /**
     * @param int $imageId
     */
    public function setImageId($imageId) {
        $this->imageId = $imageId;
    }

    /**
     * @return AddressBinding
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * @param AddressBinding $address
     */
    public function setAddress($address) {
        $this->address = deep_copy($address);
    }

    /**
     * @return Position
     */
    public function getPosition() {
        return $this->position;
    }

    /**
     * @param Position $position
     */
    public function setPosition($position) {
        $this->position = deep_copy($position);
    }

    /**
     * @return string
     */
    public function getVoucherLayout() {
        return $this->voucherLayout;
    }

    /**
     * @param string $voucherLayout
     */
    public function setVoucherLayout($voucherLayout) {
        $this->voucherLayout = $voucherLayout;
    }
}
