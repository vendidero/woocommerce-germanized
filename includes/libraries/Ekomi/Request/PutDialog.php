<?php
namespace Ekomi\Request;

/**
 * Class PutDialog
 * @package Ekomi\Request
 */
class PutDialog extends AbstractRequest
{
    private $orderId;
    private $message;

    /**
     * Exclude parameters from SOAP Call
     * @var array
     */
    protected $soapExcludeItems = array('type');

    public function getName()
    {
        return 'putDialog';
    }

    public function getQuery($type = 'CURL')
    {
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'order_id' => $this->getOrderId(),
            'message' => $this->getMessage(),
            'charset' => $this->getCharset()
        );

        if ($type === 'SOAP') {
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
     * @return PutDialog
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     * @return PutDialog
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

}