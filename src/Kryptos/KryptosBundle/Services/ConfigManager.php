<?php

namespace Kryptos\KryptosBundle\Services;


class ConfigManager
{
	protected $serviceContainer;

	protected $parameterBag;

    public function __construct($serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;

    }

    public function siginDisabled()
    {
    	$sigin_disabled = false;

    	if ($this->serviceContainer->hasParameter('disable_sigin')) {
    		if ('yes' == $this->serviceContainer->getParameter('disable_sigin')) {
    			$sigin_disabled = true;
    		}
    	}

    	return $sigin_disabled;
    }


    public function get($name)
    {
    	$value = null;
    	$params = explode('|' ,$name);
    	
    	$param = array_shift($params);
    	if ($this->serviceContainer->hasParameter($param)) {
    		
    		$value = $this->serviceContainer->getParameter($param);

    		foreach ($params as $param)
    		{
    			if (isset($value[$param])) {
    				$value = $value[$param];
    				continue;
    			}
    			
    			break;
    		}
    	}

    	return $value;
    }
}