<?php
namespace Ekomi\Service;

/**
 * Class Service
 * @package Ekomi\Service
 */
abstract class Service implements ServiceInterface{
    public $url;
    public $auth;

    /**
     * @param string $serviceName
     * @return ServiceInterface
     */
    public static function getInstance($serviceName='SOAP'){
        if(!class_exists(__NAMESPACE__.'\\'.$serviceName.'Service') || !self::checkExtension($serviceName)){
            $serviceName = 'GET';
        }
        $className = __NAMESPACE__.'\\'.$serviceName.'Service';

        return new $className();
    }

    /**
     * @param $serviceName
     * @return bool
     */
    public static function checkExtension($serviceName){
        if ($serviceName==='SOAP' && !extension_loaded(strtolower($serviceName))) {
            return false;
        }
        else if ($serviceName==='CURL' && !extension_loaded(strtolower($serviceName))){
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * Set API Auth
     *
     * @param $apiId
     * @param $apiKey
     */
    final public function setAuth($apiId,$apiKey)
    {
        $this->auth = $apiId.'|'.$apiKey;
    }

    /**
     * Check and encode JSON
     *
     * @param $result
     * @return string
     */
    public function checkEncodeJson($result){
        if(is_array($result)){
            return json_encode($result);
        }
        else if(isset($result[0]) && $result[0]!=='{' && $result[0]!=='['){
            $result = json_encode(array('response'=>$result));
        }
        return $result;
    }
}