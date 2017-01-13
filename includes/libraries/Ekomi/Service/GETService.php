<?php
namespace Ekomi\Service;

use Ekomi\Request\RequestInterface;

/**
 * Class CURLService
 * @package Ekomi\Service
 */
class GETService extends Service
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

        try {
            $stream = stream_context_create(array('http'=>
                array(
                    'timeout' => 1800,
                )
            ));

            $result = file_get_contents($url,false,$stream);
        }
        catch (\Exception $e){
            throw $e;
        }

        return json_decode($this->checkEncodeJson($result));
    }
}
