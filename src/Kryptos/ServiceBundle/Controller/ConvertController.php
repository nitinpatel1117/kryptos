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
    		$this->getApiResponse()->error('Invalid credentials.', 404);
    	}
    	$this->setupUser($user);


    	if ('' == $iban) {
    		$this->getApiResponse()->error(sprintf('Required fields have not been supplied, an %s must be supplied.', 'IBAN'), 404);
    	}
    	
    		
    	// check that the user has some credits
    	if ($this->conversionsRestricted()) {
    		$credits = $this->getAllowedConversions();
    		if ($credits < 1) {
    			$errorExists = true;
    			$this->getApiResponse()->error('Insufficient credit. You do not have sufficient funds in your account to carry out the check.', 404);
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
    			$this->getApiResponse()->error('Invalid Bank Account. The bank account provided is incorrect.', 404);
    			$chargeUser = true;
    		}
    		else {
    			$accountValid = true;
    			$chargeUser = true;
    		}
	    			
	    	if (true == $chargeUser) {
	    		if ($this->conversionsRestricted()) {
	    			$user = $this->get('user_manager');
	    			$user->reduceCredit($this->getUserId());
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
    		$this->getApiResponse()->error('Invalid credentials.', 404);
    	}
    	$this->setupUser($user);
    	

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
    					$this->getApiResponse()->error(sprintf('Invalid Details. %s is a required field. Please supply a value for %s.', $value, $value), 404);
    				}
    			}
    		}
    	}

    
    	// check that the user has some credits
    	if ($this->conversionsRestricted()) {
    		$credits = $this->getAllowedConversions();
    		if ($credits < 1) {
    			$errorExists = true;
    			$form->addError(new FormError('Insufficient credit|You do not have sufficient funds in your account to carry out the check. Please credit your account and then try again.'));
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
    			$this->getApiResponse()->error('Invalid Bank Account. The bank account provided is incorrect.', 404);
    			$chargeUser = true;
    		}
    		else {
    			$accountValid = true;
    			$chargeUser = true;
    		}
    
    		if (true == $chargeUser) {
    			if ($this->conversionsRestricted()) {
    				$user = $this->get('user_manager');
    				$user->reduceCredit($this->getUserId());
    			}
    		}
    	}
    	
    	
    	$result = new ConversionResult($resultsConversion);
    	return $this->getJsonResponse($result->toArray(), false);
    	
    	 
    	return $this->render('KryptosKryptosBundle:ConvertSingle:index.html.twig', array(
    		'form' 						=> $form->createView(),
    		'location' 					=> 'Single Convert',
    		'btn_submit' 				=> 'Convert',
    		'conversionsRestricted' 	=> $this->conversionsRestricted(),
    		'bbanMappings' 				=> $mappings->getBbanMappings(),
    		'countrySelected'			=> $countrySelected,
    		 
    		'accountValid'				=> $accountValid,
    		'result' 					=> isset($resultsConversion) ? $resultsConversion : null,
    	));
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
    
    
    /**
     * Function runs the single conversion command using the parameters supplied
     * 
     * @param string $countrySelected					The country code
     * @param array $args								The bban arguments
     * @return array									The output that was retrieved from the command
     *
    public function runSingleConversionCommand($countrySelected, array $args)
    {
    	$config = $this->get('config_manager');
    	
    	$single_command = $config->get('bankwizard|single_command');
    	$command = sprintf($single_command, $countrySelected, implode(' ', $args));
    	 
    	putenv('BW3LIBS=/var/www/bankwizard');
    	putenv('BWTABLES=/var/www/bwtables');
    	putenv('LD_LIBRARY_PATH=/var/www/bankwizard:');
    	
    	$output = array();
    	exec($command, $output);
    	
    	return $output;
    }
    */
    
    
    /**
     * Function takes the output supplied by the single conversion tool and turns it into a 
     * an associative array that can be transversd easily.
     * 
     * @param array $output
     * @return array
     *
    public function processOutput(array $output)
    {
    	$data = array();
    	
    	foreach ($output as $lineout) {
    		$lineSplit = explode(' - ' , $lineout);
    		
    		if (isset($lineSplit[0]) && isset($lineSplit[1]))
    		{
    			$key = trim($lineSplit[0]);
    			$value = trim($lineSplit[1]);
    			$value = str_replace("'", '', $value);
    			
    			$data[$key] = $value;
    			
    			unset($key);
    			unset($value);
    		}
    	}
    	
    	return $data;
    }*/
    
    
    /**
     * Function retrieves the IBAN and BIC from the supplied array, which is the processed output from the single conversion tool
     * 
     * @param array $output					The processed output from the single conversion tool
     * @return array						Array consisting of IBAN and BIC
     */
    public function getIbanAndBic(array $output)
    {
    	$iban = null;
    	$bic = null;
    	
    	if (isset($output['IBAN'])) {
    		$iban = $output['IBAN'];
    	}
    	
    	if (isset($output['Field 071'])) {
    		$bic = $output['Field 071'];
    			
    		if (isset($output['Field 072'])) {
    			$bic .= $output['Field 072'];
    		}
    	}
    	
    	return array(
    		'iban' => $iban,
    		'bic'  => $bic,
    	);
    }
}