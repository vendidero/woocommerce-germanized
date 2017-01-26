<?php
namespace Ekomi\Request;

/**
 * Class AssignClientOrder
 * @package Ekomi\Request
 */
class AssignClientOrder extends AbstractRequest{
    private $orderId;
    private $clientId;

    /**
     * This parameter is currently bugged and does not work.
     * @var string
     */
    private $unlink = 'false';

    /**
     * Exclude parameters from SOAP Call
     * @var array
     */
    protected $soapExcludeItems = array('type');

    public function getName(){
        return 'assignClientOrder';
    }

    public function getQuery($type='CURL'){
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'order_id' => $this->getOrderId(),
            'client_id' => $this->getClientId(),
            'unlink' => $this->getUnlink(),
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
     * @return AssignClientOrder
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     * @return AssignClientOrder
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUnlink()
    {
        return $this->unlink;
    }

    /**
     * @param string $unlink
     * @return AssignClientOrder
     */
    public function setUnlink($unlink)
    {
        $this->unlink = $unlink;
        return $this;
    }
}