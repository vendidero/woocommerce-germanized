<?php

namespace baltpeter\Internetmarke;

class Address extends ApiResult {
    /**
     * @var string Address supplement
     */
    protected $additional;
    /**
     * @var string Street
     */
    protected $street;
    /**
     * @var string House number
     */
    protected $houseNo;
    /**
     * @var string Postal code (ZIP code)
     */
    protected $zip;
    /**
     * @var string City
     */
    protected $city;
    /**
     * @var string 3-digit ISO country code
     */
    protected $country;

    /**
     * Address constructor.
     *
     * @param string $additional
     * @param string $street
     * @param string $house_no
     * @param string $zip
     * @param string $city
     * @param string $country
     */
    public function __construct($additional, $street, $house_no, $zip, $city, $country) {
        $this->setAdditional($additional);
        $this->setStreet($street);
        $this->setHouseNo($house_no);
        $this->setZip($zip);
        $this->setCity($city);
        $this->setCountry($country);
    }

    /**
     * @return string
     */
    public function getAdditional() {
        return $this->additional;
    }

    /**
     * @param string $additional
     */
    public function setAdditional($additional) {
        $this->additional = $additional;
    }

    /**
     * @return string
     */
    public function getStreet() {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet($street) {
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getHouseNo() {
        return $this->houseNo;
    }

    /**
     * @param string $houseNo
     */
    public function setHouseNo($houseNo) {
        $this->houseNo = $houseNo;
    }

    /**
     * @return string
     */
    public function getZip() {
        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip($zip) {
        $this->zip = $zip;
    }

    /**
     * @return string
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city) {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country) {
        $this->country = $country;
    }
}
