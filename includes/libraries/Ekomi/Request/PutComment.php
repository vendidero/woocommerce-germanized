<?php
namespace Ekomi\Request;

/**
 * Class PutComment
 * @package Ekomi\Request
 */
class PutComment extends AbstractRequest{
    private $orderId;
    private $update = 'replace';
    private $comment = '';

    public function getName(){
        return 'putComment';
    }

    public function getQuery($type='CURL'){
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'order_id' => $this->getOrderId(),
            'comment' => $this->getComment(),
            'update' => $this->getUpdate(),
            'charset' => $this->getCharset()
        );

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
     * @return PutComment
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUpdate()
    {
        return $this->update;
    }

    /**
     * @param string $update
     * @return PutComment
     */
    public function setUpdate($update)
    {
        $this->update = $update;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return PutComment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

}