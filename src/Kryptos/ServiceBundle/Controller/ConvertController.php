<?php
namespace Kryptos\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Kryptos\ServiceBundle\Lib\ConversionResult;
use Kryptos\KryptosBundle\Lib\BbanCountryMappings\Mappings;
use Kryptos\KryptosBundle\Controller\LocaleInterface;


class ConvertController extends DefaultController implements LocaleInterface
{
	
	protected $user;
	
    public function ibanAction(Request $request, $_locale, $publicKey, $iban)
    {
    	$this->get('locale_switcher')->setLocale($_locale);
    	
    	# $userManager = $this->get('user_manager');
    	
    	$errorExists = false;
    	$accountValid = false;
    	
    	/*
    	try {
    		$user = $userManager->getUserByPublicKey($publicKey);
    	}
    	catch (\Exception $e) {
    		if ('Invalid object ID' == $e->getMessage()) {
    			$user = null;
    		}
    	}*/
    	$user = $this->retrieveUser($publicKey);
    	if (is_null($user)) {
    		$errorExists = true;
    		return $this->dieImmediately($this->get('translator')->trans('msg_title_invalid_credentials'), 404);
    	}
    	$this->setupUser($user);


    	if ('' == $iban) {
    		return $this->dieImmediately($this->get('translator')->trans('msg_desc_required_field_iban'), 404);
    	}
    	
    		
    	// check that the user has some credits
    	if ($this->conversionsRestricted()) {
    		$credits = $this->getAllowedConversions();
    		if ($credits < 1) {
    			$errorExists = true;
    			$msg = sprintf('%s %s', $this->get('translator')->trans('msg_title_insufficient_credit'), $this->get('translator')->trans('msg_desc_insufficient_credit'));
    			return $this->dieImmediately($msg, 404);
    		}
    	}

    	
    	$resultsConversion = $this->get('single_conversion');
    	if (false == $errorExists)
    	{
    		$chargeUser = false;
    		$resultsConversion->runIban($iban);

    		if (true == $resultsConversion->isFatal) {
    			$msg = sprintf('%s %s', $this->get('translator')->trans('msg_title_conversion_tool_offline'), $this->get('translator')->trans('msg_desc_conversion_tool_offline'));
    			$this->getApiResponse()->error($msg, 404);
    		} else if (false == $resultsConversion->isValid) {
    			$this->getApiResponse()->error($resultsConversion->getErrorsAsString(), 404);
    			$chargeUser = true;
    		}
    		else {
    			$accountValid = true;
    			$chargeUser = true;
    		}
	    			
	    	if (true == $chargeUser) {
	    		if ($this->conversionsRestricted()) {
	    			$user = $this->get('user_manager');
	    			$user->reduceCredit($this->getUserId(), 'singleApi');
	    		}
	    	}
    	}
    	
    	$result = new ConversionResult($resultsConversion, $this->get('translator'));
    	return $this->getJsonResponse($result->toArray(), false);
    }
    
    
    
    public function bbanAction(Request $request, $_locale, $publicKey, $country, $bban1, $bban2, $bban3, $bban4)
    {
    	$this->get('locale_switcher')->setLocale($_locale);
    	
    	#$userManager = $this->get('user_manager');
    	 
    	$errorExists = false;
    	$accountValid = false;
    	
    	/*
    	try {
    		$user = $userManager->getUserByPublicKey($publicKey);
    	}
    	catch (\Exception $e) {
    		if ('Invalid object ID' == $e->getMessage()) {
    			$user = null;
    		}
    	}*/
    	
    	$user = $this->retrieveUser($publicKey);
    	if (is_null($user)) {
    		$errorExists = true;
    		return $this->dieImmediately($this->get('translator')->trans('msg_title_invalid_credentials'), 404);
    	}
    	$this->setupUser($user);
    	
    	if (is_null($country) || ''== $country) {
    		return $this->dieImmediately($this->get('translator')->trans('msg_desc_required_field_country_code'), 404);
    	}
    	$country = strtoupper($country);
    	

    	// check that all the bban fields for the supplied country code are provided
    	$mappings = new Mappings();
    	$bbanMaps = $mappings->getBbanMappings($country);
    	$bbanOptional = $mappings->getBbanMappingsOptional($country);
    	
    	if (is_array($bbanMaps)) {
    		foreach ($bbanMaps as $key => $value) {
    			if (!in_array($key, $bbanOptional))
    			{
    				if (!(isset($$key) && !empty($$key))) {
    					$errorExists = true;
    					$msg = sprintf('%s. %s', $this->get('translator')->trans('msg_title_invalid_details'), $this->get('translator')->trans('msg_desc_bban_required', array('{{ bban_fieldname }}' => $value)));
    					return $this->dieImmediately($msg, 404);
    				}
    			}
    		}
    	}

    
    	// check that the user has some credits
    	if ($this->conversionsRestricted()) {
    		$credits = $this->getAllowedConversions();
    		if ($credits < 1) {
    			$errorExists = true;
    			$msg = sprintf('%s %s', $this->get('translator')->trans('msg_title_insufficient_credit'), $this->get('translator')->trans('msg_desc_insufficient_credit'));
    			return $this->dieImmediately($msg);
    		}
    	}
    
    
    	if (false == $errorExists)
    	{
    		$chargeUser = false;
    		$resultsConversion = $this->get('single_conversion');
    			 
    		// build our commands
    		$args = array();
    		if (is_array($bbanMaps)) {
    			foreach ($bbanMaps as $key => $value) {
    				$args[] = $$key;
    			}
    
    			$resultsConversion->runCountry($country, $args);
    		}
    		
    			 
    		if (true == $resultsConversion->isFatal) {
    			$msg = sprintf('%s %s', $this->get('translator')->trans('msg_title_conversion_tool_offline'), $this->get('translator')->trans('msg_desc_conversion_tool_offline'));
    			$this->getApiResponse()->error($msg, 404);
    		} else if (false == $resultsConversion->isValid) {
    			$this->getApiResponse()->error($resultsConversion->getErrorsAsString(), 404);
    			$chargeUser = true;
    		}
    		else {
    			$accountValid = true;
    			$chargeUser = true;
    		}
    
    		if (true == $chargeUser) {
    			if ($this->conversionsRestricted()) {
    				$user = $this->get('user_manager');
    				$user->reduceCredit($this->getUserId(), 'singleApi');
    			}
    		}
    	}
    	
    	$result = new ConversionResult($resultsConversion, $this->get('translator'));
    	return $this->getJsonResponse($result->toArray(), false);
    }
    

    public function ibanDummyAction(Request $request, $_locale, $publicKey, $iban)
    {
    	$user = $this->retrieveUser($publicKey);
    	if (is_null($user)) {
    		$errorExists = true;
    		return $this->dieImmediately($this->get('translator')->trans('msg_title_invalid_credentials'), 404);
    	}

    	return $this->makeDummyResponse($request, $publicKey);
    }
    
    
    public function bbanDummyAction(Request $request, $_locale, $publicKey, $country, $bban1, $bban2, $bban3, $bban4)
    {
    	$user = $this->retrieveUser($publicKey);
    	if (is_null($user)) {
    		$errorExists = true;
    		return $this->dieImmediately($this->get('translator')->trans('msg_title_invalid_credentials'), 404);
    	}
    	$this->setupUser($user);
    	 
    	if (is_null($country) || ''== $country) {
    		return $this->dieImmediately($this->get('translator')->trans('msg_desc_required_field_country_code'), 404);
    	}
    	$country = strtoupper($country);
    	
    	// check that all the bban fields for the supplied country code are provided
    	$mappings = new Mappings();
    	$bbanMaps = $mappings->getBbanMappings($country);
    	$bbanOptional = $mappings->getBbanMappingsOptional($country);
    	
    	if (is_array($bbanMaps)) {
    		foreach ($bbanMaps as $key => $value) {
    			if (!in_array($key, $bbanOptional))
    			{
    				if (!(isset($$key) && !empty($$key))) {
    					$errorExists = true;
    					$msg = sprintf('%s. %s', $this->get('translator')->trans('msg_title_invalid_details'), $this->get('translator')->trans('msg_desc_bban_required', array('{{ bban_fieldname }}' => $value)));
    					return $this->dieImmediately($msg, 404);
    				}
    			}
    		}
    	}

    	return $this->makeDummyResponse($request, $publicKey);
    }


    protected function makeDummyResponse(Request $request, $publicKey)
    {
    	/* Converts: 
    	 * 		/dummy_services/en/convert/iban/521a53c1632ed4931100000a/GB71NAIA07011621132249
    	 * to
    	 * 		__dummy_services__en__convert__iban__publicKey__GB71NAIA07011621132249
    	 *
    	 * 
    	 * 		/dummy_services/en/convert/bban/521a53c1632ed4931100000a/GB/070116/21132249
    	 * to
    	 * 		__dummy_services__en__convert__bban__publicKey__GB__070116__21132249
    	 */
    	$search = array (
    		'/app.php',
    		'/nitin_dev.php',
    		'/',
    		$publicKey,
    	);
    	$replace = array(
    		'',
    		'',
    		'__',
    		'publicKey',
    	);
    	$requestFile = str_replace($search, $replace, $request->getRequestUri());

    	// make filepath for dummy file
    	$configManager = $this->get('config_manager');
    	$dummyPath = $configManager->get('dummy_service|filepath');
    	$dummyFilepath = sprintf('%s/%s.json', $dummyPath, $requestFile);

    	// check file system for file
    	if (!(file_exists($dummyFilepath) && is_readable($dummyFilepath))) {
    		die('dummy service is not available for this API endpoint');
    		// get translation for below
    		return $this->dieImmediately($this->get('translator')->trans('msg_desc_required_field_iban'), 404);
    	
    	}

    	$data = file_get_contents($dummyFilepath);
    	 
    	// get status code of saved response
    	$statusCode = 200;
    	$decoded = json_decode($data, true);
    	if (isset($decoded['diagnostics']['code'])) {
    		$statusCode = $decoded['diagnostics']['code'];
    	}
    	 
    	$response = new Response();
    	$response->setContent($data);
    	$response->setStatusCode($statusCode);
    	$response->headers->set('Content-Type', 'application/json');
    	 
    	return $response;
    }
    
    
    /**
     * 
     */
    protected function retrieveUser($publicKey)
    {
    	$userManager = $this->get('user_manager');
    	
    	try {
    		$user = $userManager->getUserByPublicKey($publicKey);
    	}
    	catch (\Exception $e) {
    		if ('Invalid object ID' == $e->getMessage()) {
    			$user = null;
    		}
    	}
    	
    	return $user;
    }
    
    
    public function setupUser($user = null)
    {
    	$config = $this->get('config_manager');
    	if ($config->signinRequired()) {
    		$this->user = $user;
    	}
    }
    
    
    public function getUserId()
    {
    	// get user id, if user is setup
    	if (isset($this->user)) {
    		return $this->user['_id']->__toString();
    	}
    	return null;
    }
    
    
    /**
     * If user signin is enabled, then the number of conversions are restricted
     */
    public function conversionsRestricted()
    {
    	return $this->get('config_manager')->signinRequired();
    }
    
    
    /**
     * Gets the number of credits available for the user, or 0 if none available 
     * @return number
     */
    public function getAllowedConversions()
    {
    	$allowedConversions = 0;
    	
    	if (isset($this->user['credits'])) {
    		$allowedConversions = $this->user['credits'];
    	}
    	
    	return $allowedConversions;
    }
}