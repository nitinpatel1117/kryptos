<?php
namespace Kryptos\KryptosBundle\Services;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Kryptos\KryptosBundle\Controller\LocaleInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class LocaleSwitcher
{
	protected $session;
	
	protected $serviceContainer;
	
	protected $configManager;

	protected $request;

    public function __construct($session, $serviceContainer, $configManager)
    {
    	$this->session			= $session;
    	$this->serviceContainer = $serviceContainer;
    	$this->configManager 	= $configManager;
        $this->request 			= $this->serviceContainer->get('request');
    }
    
    
    public function initLocale()
    {
    	// read locale from server session
    	$val = $this->getLocale();
    	
    	// read locale from cookie
    	if (is_null($val)) {
    		$val = $this->getLocaleFromCookie();
    	}
    	
    	// read locale from Accept-Language header in request
    	if (is_null($val)) {
    		$val = $this->request->getPreferredLanguage($this->getSupportedLocales());
    	}
    
    	if (!is_null($val)) {
    		setlocale(LC_ALL, $this->POSIXLocale($val));
    		$this->request->setLocale($val);
    	}
    }
    
    
    public function setLocale($locale)
    {
    	$this->session->set('_locale', $locale);
    	$this->addLocaleToCookie($locale);
    	$this->initLocale();
    }
    
    
    public function getLocale()
    {
    	return $this->session->get('_locale');
    }
    
    
    public function getLocaleFromCookie()
    {
    	$locale = null;
    	
    	$cookieLocale = $this->request->cookies->get($this->getCookieName());
    	if (in_array($cookieLocale, $this->getSupportedLocales())) {
    		$locale = $cookieLocale;
    	}
    	
    	return $locale;
    }
    
    
    public function getSupportedLocales()
    {
    	return $this->configManager->get('supported_locales');
    }
    
    
    public function getCookieName()
    {
    	return $this->configManager->get('locale_cookie_name');
    }
    
    
    public function getCookieTime()
    {
    	return (int) $this->configManager->get('locale_cookie_time');
    }
    
    
    public function addLocaleToCookie($locale)
    {
    	$cookie = new Cookie($this->getCookieName(), $locale, time() + $this->getCookieTime());
    	
		$response = new Response();
		$response->headers->setCookie($cookie);
		$response->sendHeaders();
    }
    
    
    public function POSIXLocale($val)
    {
    	$localeFull = 'en_GB';
    	switch($val) {
    		case 'en':   $localeFull = 'en_GB'; break;
    		case 'de':   $localeFull = 'de_DE'; break;
    		case 'es':   $localeFull = 'es_ES'; break;
    		case 'fr':   $localeFull = 'fr_FR'; break;
    		case 'it':   $localeFull = 'it_IT'; break;
    	}

    	return $localeFull;
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