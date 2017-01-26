<?php
namespace Ekomi\Request;

/**
 * Class GetResearch
 * @package Ekomi\Request
 */
class GetResearch extends AbstractRequest{
    private $content;
    private $range;
    private $campaignId;

    /**
     * Exclude parameters from SOAP Call
     */
    protected $soapExcludeItems = array('version','type');

    public function getName(){
        return 'getResearch';
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
            'campaign_id' => $this->getCampaignId(),
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
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     * @return GetResearch
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
     * @return GetResearch
     */
    public function setRange($range)
    {
        $this->range = $range;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @param mixed $campaignId
     * @return GetResearch
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
        return $this;
    }

}