<?php
namespace Kryptos\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\ServiceBundle\Lib\ConversionResult;
use Kryptos\KryptosBundle\Lib\BbanCountryMappings\Mappings;


class ConvertController extends DefaultController
{
	
	protected $user;
	
    public function ibanAction(Request $request, $publicKey, $iban)
    {
    	$userManager = $this->get('user_manager');
    	
    	$errorExists = false;
    	$accountValid = false;
    	
    	$user = $userManager->getUserByPublicKey($publicKey);
    	if (is_null($user)) {
    		$errorExists = true;
    		return $this->dieImmediately('Invalid credentials.', 404);
    	}
    	$this->setupUser($user);


    	if ('' == $iban) {
    		return $this->dieImmediately(sprintf('Required fields have not been supplied, an %s must be supplied.', 'IBAN'), 404);
    	}
    	
    		
    	// check that the user has some credits
    	if ($this->conversionsRestricted()) {
    		$credits = $this->getAllowedConversions();
    		if ($credits < 1) {
    			$errorExists = true;
    			return $this->dieImmediately('Insufficient credit. You do not have sufficient funds in your account to carry out the check.', 404);
    		}
    	}

    	
    	$resultsConversion = $this->get('single_conversion');
    	if (false == $errorExists)
    	{
    		$chargeUser = false;
    		$resultsConversion->runIban($iban);

    		if (true == $resultsConversion->isFatal) {
    			$this->getApiResponse()->error('Conversion not possible. Our conversion tool is offline, therefore, we are currently not able to process your request. Note: You have not been charged a conversion.', 404);
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
    	
    	$result = new ConversionResult($resultsConversion);
    	return $this->getJsonResponse($result->toArray(), false);
    }
    
    
    
    public function bbanAction(Request $request, $publicKey, $country, $bban1, $bban2, $bban3, $bban4)
    {
    	$userManager = $this->get('user_manager');
    	 
    	$errorExists = false;
    	$accountValid = false;
    	
    	$user = $userManager->getUserByPublicKey($publicKey);
    	if (is_null($user)) {
    		$errorExists = true;
    		return $this->dieImmediately('Invalid credentials.', 404);
    	}
    	$this->setupUser($user);
    	
    	if (is_null($country) || ''== $country) {
    		return $this->dieImmediately(sprintf('Required fields have not been supplied, a %s must be supplied.', 'Country Code'), 404);
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
    					return $this->dieImmediately(sprintf('Invalid Details. %s is a required field. Please supply a value for %s.', $value, $value), 404);
    				}
    			}
    		}
    	}

    
    	// check that the user has some credits
    	if ($this->conversionsRestricted()) {
    		$credits = $this->getAllowedConversions();
    		if ($credits < 1) {
    			$errorExists = true;
    			return $this->dieImmediately('Insufficient credit. You do not have sufficient funds in your account to carry out the check. Please credit your account and then try again.');
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
    			$this->getApiResponse()->error('Conversion not possible. Our conversion tool is offline, therefore, we are currently not able to process your request. Note: You have not been charged a conversion.', 404);
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
    	
    	$result = new ConversionResult($resultsConversion);
    	return $this->getJsonResponse($result->toArray(), false);
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