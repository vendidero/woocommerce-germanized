<?php
namespace Ekomi\Service;

use Ekomi\Request\RequestInterface;

/**
 * Class CURLService
 * @package Ekomi\Service
 */
class CURLService extends Service
{
    public $url = 'http://api.ekomi.de/v3/';

    /**
     * @param RequestInterface $request
     * @return mixed
     * @throws \Exception
     */
    public function exec(RequestInterface $request)
    {
        $requestData = $request->getQuery();
        $requestData['auth'] = $this->getAuth();

        $url = $this->getUrl() . $request->getName() . '?' . http_build_query($requestData);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 600);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 600);

        try {
            $result = curl_exec($curl);
        }
        catch (\Exception $e){
            throw $e;
        }

        return json_decode($this->checkEncodeJson($result));
    }
}
