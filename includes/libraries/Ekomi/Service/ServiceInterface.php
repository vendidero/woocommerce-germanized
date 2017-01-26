<?php
namespace Ekomi\Service;

use Ekomi\Request\RequestInterface;

/**
 * Interface ServiceInterface
 * @package Ekomi\Service
 */
interface ServiceInterface{
    /**
     * @param RequestInterface $request
     * @return mixed
     */
    public function exec(RequestInterface $request);
    public function setAuth($apiId,$apiKey);
}