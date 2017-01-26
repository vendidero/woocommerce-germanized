<?php
namespace Ekomi\Request;

/**
 * Class GetMarketResearch
 * @package Ekomi\Request
 */
class GetMarketResearch extends AbstractRequest{
    private $content = 'summary';
    private $state = 'both';
    private $startts;
    private $endts;

    /**
     * Exclude parameters from SOAP Call
     */
    protected $soapExcludeItems = array('type');

    public function getName(){
        return 'getMarketresearch';
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
            'state' => $this->getState(),
            'startts' => $this->getStartts(),
            'endts' => $this->getEndts(),
            'charset' => $this->getCharset(),
        );

        if($type==='SOAP'){
            return $this->unsetItemsInArray($query);
        }

        return $query;
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
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getStartts()
    {
        return $this->startts;
    }

    /**
     * @param mixed $startts
     */
    public function setStartts($startts)
    {
        $this->startts = $startts;
    }

    /**
     * @return mixed
     */
    public function getEndts()
    {
        return $this->endts;
    }

    /**
     * @param mixed $endts
     */
    public function setEndts($endts)
    {
        $this->endts = $endts;
    }
}