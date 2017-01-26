<?php
namespace Ekomi\Request;

/**
 * Class GetDialog
 * @package Ekomi\Request
 */
class GetDialog extends AbstractRequest{
    private $content;
    private $range;
    private $filter;
    private $caching = 'none';

    /**
     * Exclude parameters from SOAP Call
     */
    protected $soapExcludeItems = array('type');

    public function getName(){
        return 'getDialog';
    }

    /**
     * Get API Query
     * @param string $type
     * @return array
     */
    public function getQuery($type='CURL'){
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'content' => $this->getContent(),
            'range' => $this->getRange(),
            'filter' => $this->getFilter(),
            'caching' => $this->getCaching(),
            'charset' => $this->getCharset(),
        );

        if ($type === 'SOAP') {
            return $this->unsetItemsInArray($query);
        }

        return $query;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     * @return GetDialog
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * @param mixed $range
     * @return GetDialog
     */
    public function setRange($range)
    {
        $this->range = $range;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param mixed $filter
     * @return GetDialog
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
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
     * @return GetDialog
     */
    public function setCaching($caching)
    {
        $this->caching = $caching;
        return $this;
    }


}