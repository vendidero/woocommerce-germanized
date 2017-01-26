<?php
namespace Ekomi\Request;

/**
 * Class PutOrder
 * @package Ekomi\Request
 */
class PutOrder extends AbstractRequest{
    private $orderId;
    private $productIds;
    private $productIdsUpdateMethod = 'append';

    /**
     * SOAP API Doesn't support it
     * @var
     */
    private $ordertimestamp;

    /**
     * Exclude parameters from SOAP Call
     * SOAP API Doesn't support ordertimestamp parameter
     *
     * @var array
     */
    protected $soapExcludeItems = array('type','ordertimestamp');

    public function getName(){
        return 'putOrder';
    }

    public function getQuery($type='CURL'){
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'order_id' => $this->getOrderId(),
            'product_ids' => $this->getProductIds(),
            'product_ids_update_method' => $this->getProductIdsUpdateMethod(),
            'ordertimestamp' => $this->getOrdertimestamp(),
            'charset' => $this->getCharset()
        );

        if($type==='SOAP'){
            return $this->unsetItemsInArray($query);
        }

        return $query;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param mixed $orderId
     * @return PutOrder
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProductIds()
    {
        return $this->productIds;
    }

    /**
     * @param mixed $productIds
     * @return PutOrder
     */
    public function setProductIds($productIds)
    {
        $this->productIds = $productIds;
        return $this;
    }

    /**
     * @return string
     */
    public function getProductIdsUpdateMethod()
    {
        return $this->productIdsUpdateMethod;
    }

    /**
     * @param string $productIdsUpdateMethod
     * @return PutOrder
     */
    public function setProductIdsUpdateMethod($productIdsUpdateMethod)
    {
        $this->productIdsUpdateMethod = $productIdsUpdateMethod;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrdertimestamp()
    {
        return $this->ordertimestamp;
    }

    /**
     * @param mixed $ordertimestamp
     * @return PutOrder
     */
    public function setOrdertimestamp($ordertimestamp)
    {
        $this->ordertimestamp = $ordertimestamp;
        return $this;
    }
}