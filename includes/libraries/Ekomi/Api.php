<?php

namespace Ekomi;

use Ekomi\Service\Service;
use Ekomi\Request\RequestInterface;
use Ekomi\Service\ServiceInterface;

class Api{
    /**
     * @var ServiceInterface
     */
    public $service;

    /**
     * Api constructor.
     * @param $apiId
     * @param $apiKey
     * @param string $type
     */
    public function __construct($apiId,$apiKey,$type='SOAP'){
        $this->service = Service::getInstance($type);
        $this->service->setAuth($apiId,$apiKey);
    }

    /**
     * @param RequestInterface $apiCall
     * @return mixed
     */
    public function exec(RequestInterface $apiCall){
        return $this->service->exec($apiCall);
    }
}