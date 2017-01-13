<?php
namespace Ekomi\Request;

/**
 * Class GetProduct
 * @package Ekomi\Request
 */
class GetProduct extends AbstractRequest{

    private $product;
    private $content = 'none';
    private $caching = 'none';

    /**
     * Exclude parameters from SOAP Call
     *
     * @var array
     */
    protected $soapExcludeItems = array('type');

    public function getName(){
        return 'getProduct';
    }

    /**
     * Get API Query
     *
     * @param string $type
     * @return array
     */
    public function getQuery($type='CURL'){
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'product' => $this->getProduct(),
            'type' => $this->getType(),
            'content' => $this->getContent(),
            'caching' => $this->getCaching(),
            'charset' => $this->getCharset(),
        );

        if($type==='SOAP'){
            return $this->unsetItemsInArray($query);
        }

        return $query;
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param mixed $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
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
     */
    public function setCaching($caching)
    {
        $this->caching = $caching;
    }
}