<?php
namespace Ekomi\Request;

/**
 * Class GetAverage
 * @package Ekomi\Request
 */
class GetAverage extends AbstractRequest{
    private $days = '';
    /**
     * Exclude parameters from SOAP Call
     */
    protected $soapExcludeItems = array('type');

    public function getName(){
        return 'getAverage';
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
            'days' => $this->getDays()
        );

        if($type==='SOAP'){
            return $this->unsetItemsInArray($query);
        }

        return $query;
    }

    /**
     * @return string
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * @param string $days
     * @return GetAverage
     */
    public function setDays($days)
    {
        $this->days = $days;
        return $this;
    }
}