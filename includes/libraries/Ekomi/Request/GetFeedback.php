<?php
namespace Ekomi\Request;

/**
 * Class GetFeedback
 * @package Ekomi\Request
 */
class GetFeedback extends AbstractRequest{
    private $filter = 'all';
    private $caching = 'none';
    private $range = 'all';

    /**
     * List of the fields in the response
     *
     * Additional fields
     * shop_review_title,order_day,firstname,lastname,screenname,client_id
     *
     * @var string
     */
    private $fields = 'date,order_id,rating,feedback,comment';

    /**
     * Exclude parameters from SOAP Call
     *
     * @var array
     */
    protected $soapExcludeItems = array('version','type');

    public function getName(){
        return 'getFeedback';
    }

    public function getQuery($type='CURL'){
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'filter' => $this->getFilter(),
            'range' => $this->getRange(),
            'caching' => $this->getCaching(),
            'fields' => $this->getFields(),
            'mode' => '',
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
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param string $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
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

    /**
     * @return string
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * @param string $range
     */
    public function setRange($range)
    {
        $this->range = $range;
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
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }
}