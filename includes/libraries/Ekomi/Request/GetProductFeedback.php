<?php
namespace Ekomi\Request;

/**
 * Class GetProductFeedback
 * @package Ekomi\Request
 */
class GetProductFeedback extends AbstractRequest{
    private $product = '';
    private $caching = 'none';
    private $range = 'all';

    /**
     * List of the fields in the response
     *
     * Additional fields
     * product_review_title,order_day,firstname,lastname,screenname,client_id
     *
     * @var string
     */
    private $fields = 'date,order_id,product_id,rating,feedback';

    /**
     * Exclude parameters from SOAP Call
     *
     * @var array
     */
    protected $soapExcludeItems = array('version','type');

    public function getName(){
        return 'getProductfeedback';
    }

    public function getQuery($type='CURL'){
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'product' => $this->getProduct(),
            'range' => $this->getRange(),
            'caching' => $this->getCaching(),
            'fields' => $this->getFields(),
            'charset' => $this->getCharset()
        );

        if($type==='SOAP'){
            return $this->unsetItemsInArray($query);
        }

        return $query;
    }

    /**
     * @return string
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param string $product
     * @return GetProductFeedback
     */
    public function setProduct($product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return string
     */
    public function getCaching()
    {
        return $this->caching;
    }

    /**
     * @param string $caching
     * @return GetProductFeedback
     */
    public function setCaching($caching)
    {
        $this->caching = $caching;
        return $this;
    }

    /**
     * @return string
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * @param string $range
     * @return GetProductFeedback
     */
    public function setRange($range)
    {
        $this->range = $range;
        return $this;
    }

    /**
     * @return string
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string $fields
     * @return GetProductFeedback
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
        return $this;
    }

}