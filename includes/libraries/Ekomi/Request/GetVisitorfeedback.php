<?php
namespace Ekomi\Request;

/**
 * Class GetVisitorfeedback
 * @package Ekomi\Request
 */
class GetVisitorfeedback extends AbstractRequest{
    private $days = 8;
    private $content = 'feedback,error';

    /**
     * Exclude parameters from SOAP Call
     *
     * @var array
     */
    protected $soapExcludeItems = array('type');

    public function getName(){
        return 'getVisitorfeedback';
    }

    public function getQuery($type='CURL'){
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'content' => $this->getContent(),
            'days' => $this->getDays(),
            'charset' => $this->getCharset()
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
     * @return GetVisitorfeedback
     */
    public function setDays($days)
    {
        $this->days = $days;
        return $this;
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
     * @return GetVisitorfeedback
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

}