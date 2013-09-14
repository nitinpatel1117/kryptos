<?php
namespace Kryptos\KryptosBundle\Services;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Kryptos\KryptosBundle\Controller\LocaleInterface;

class LocaleSwitcher
{
	protected $session;
	
	protected $serviceContainer;

	protected $request;

    public function __construct($session, $serviceContainer)
    {
    	$this->session			= $session;
    	$this->serviceContainer = $serviceContainer;
        $this->request 			= $this->serviceContainer->get('request');
    }
    
    
    public function initLocale()
    {
    	$val = $this->getLocale();
    	if (!is_null($val)) {
    		$this->request->setLocale($val);
    	}
    }
    
    
    public function setLocale($locale)
    {
    	$this->session->set('_locale', $locale);
    	$this->initLocale();
    }
    
    public function getLocale()
    {
    	return $this->session->get('_locale');
    }
    
    
    
    public function onKernelController(FilterControllerEvent $event)
    {
    	$controller = $event->getController();
    
    	/*
    	 * $controller passed can be either a class or a Closure. This is not usual in Symfony2 but it may happen.
    	* If it is a class, it comes in array format
    	*/
    	if (!is_array($controller)) {
    		return;
    	}
    
    	if ($controller[0] instanceof LocaleInterface) {
    		$this->initLocale();
    	}
    }
}