<?php

namespace Kryptos\SageBundle\Lib;


class HttpGetter
{
    private $url;
    private $config;
    private $responseBody;
    private $httpStatus;

    public function __construct()
    {
    }
   
    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function execute($post = array())
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $this->responseBody = curl_exec($ch);
        
        $info = curl_getinfo($ch);        
        $this->httpStatus = $info['http_code'];
        
        curl_close($ch);
    }

    public function getStatusCode()
    {
        return $this->httpStatus;
    }

    public function getResponse()
    {
        return $this->responseBody;
    }
}
