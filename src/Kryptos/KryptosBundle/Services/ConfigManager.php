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
}