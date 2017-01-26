<?php
namespace Ekomi\Service;

use Ekomi\Request\RequestInterface;

/**
 * Class SOAPService
 * @package Ekomi\Service
 */
class SOAPService extends Service{
    public $url = 'http://api.ekomi.de/v3/disp.soapwsdl.php';

    /**
     * @param RequestInterface $request
     * @return string
     * @throws \Exception
     */
    public function exec(RequestInterface $request){
        try {
            $client = new \SoapClient($this->getUrl(), array('exceptions' => 1, 'trace' => true));
            $requestData = $request->getQuery('SOAP');
            $requestData['auth'] = $this->getAuth();

            $call = call_user_func_array(array($client, $request->getName()), $requestData);
        }
        catch (\Exception $e){
            throw $e;
        }

        return json_decode($this->checkEncodeJson($call));
    }
}
