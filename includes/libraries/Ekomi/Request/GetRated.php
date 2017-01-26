<?php
namespace Ekomi\Request;

/**
 * Class GetRated
 * @package Ekomi\Request
 */
class GetRated extends AbstractRequest{
    private $days = 25;
    /**
     * Exclude parameters from SOAP Call
     */
    protected $soapExcludeItems = array('type');

    public function getName(){
        return 'getRated';
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
            'days' => $this->getDays(),
            'charset' => $this->getCharset(),
        );

        if($type==='SOAP'){
            return $this->unsetItemsInArray($query);
        }

        return $query;
    }

    /**
     * @return int
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * @param int $days
     * @return GetRated
     */
    public function setDays($days)
    {
        $this->days = $days;
        return $this;
    }


}