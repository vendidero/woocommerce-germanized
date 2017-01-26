<?php
namespace Ekomi\Request;

/**
 * Class PutProduct
 * @package Ekomi\Request
 */
class PutProduct extends AbstractRequest{
    private $productId;
    private $productName;
    private $productOther = null;
    private $productCanonicalLink;

    /**
     * Exclude parameters from SOAP Call
     *
     * @var array
     */
    protected $soapExcludeItems = array('type');

    public function getName(){
        return 'putProduct';
    }

    public function getQuery($type='CURL'){
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'product_id' => $this->getProductId(),
            'product_name' => $this->getProductName(),
            'product_other' => json_encode($this->getOther()->getQuery()),
            'charset' => $this->getCharset(),
            'product_canonical_link' => $this->getProductCanonicalLink()
        );

        if($type==='SOAP'){
            return $this->unsetItemsInArray($query);
        }

        return $query;
    }

    /**
     * Init PutProductOther if it's null and get
     * @return PutProductOther
     */
    public function getOther(){
        if($this->getProductOther() === null){
            $this->setProductOther(new PutProductOther());
        }
        return $this->getProductOther();
    }

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param mixed $productId
     * @return PutProduct
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * @param mixed $productName
     * @return PutProduct
     */
    public function setProductName($productName)
    {
        $this->productName = $productName;
        return $this;
    }

    /**
     * @return null
     */
    public function getProductOther()
    {
        return $this->productOther;
    }

    /**
     * @param null $productOther
     * @return PutProduct
     */
    public function setProductOther($productOther)
    {
        $this->productOther = $productOther;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProductCanonicalLink()
    {
        return $this->productCanonicalLink;
    }

    /**
     * @param mixed $productCanonicalLink
     * @return PutProduct
     */
    public function setProductCanonicalLink($productCanonicalLink)
    {
        $this->productCanonicalLink = $productCanonicalLink;
        return $this;
    }

}