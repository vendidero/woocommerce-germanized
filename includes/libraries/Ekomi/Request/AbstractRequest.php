<?php
namespace Ekomi\Request;

/**
 * Class AbstractRequest
 * @package Ekomi\Request
 */
abstract class AbstractRequest implements RequestInterface{
    protected $soapExcludeItems = array();

    private $version = 'cust-1.0.0';
    private $type = 'json';
    private $charset = 'utf-8';

    public function unsetItemsInArray(array $queryArray){
        if(count($this->getSoapExcludeItems())>0){
            foreach($this->getSoapExcludeItems() as $value){
                unset($queryArray[$value]);
            }
        }
        return $queryArray;
    }

    /**
     * @return array
     */
    protected function getSoapExcludeItems()
    {
        return $this->soapExcludeItems;
    }

    /**
     * @return string
     */
    protected function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    protected function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    protected function getCharset()
    {
        return $this->charset;
    }
}