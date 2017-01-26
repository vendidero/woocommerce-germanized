<?php
namespace Ekomi\Request;

/**
 * Class GetSettings
 * @package Ekomi\Request
 */
class GetSettings extends AbstractRequest{
    private $content = 'request';

    public function getName(){
        return 'getSettings';
    }

    public function getQuery($type='CURL'){
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'charset' => $this->getCharset(),
            'content' => $this->getContent()
        );

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
     * @return GetSettings
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }


}