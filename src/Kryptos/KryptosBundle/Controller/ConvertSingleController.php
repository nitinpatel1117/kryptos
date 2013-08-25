<?php
namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\KryptosBundle\Form\ConvertSingleForm;
use Symfony\Component\Form\FormError;
use Kryptos\KryptosBundle\Lib\BbanCountryMappings\Mappings;


class ConvertSingleController extends Controller
{
	
	protected $user;
	
    public function indexAction(Request $request)
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');

    	if ($config->signinRequired() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	
    	$accountValid = false;
    	$mappings = new Mappings();
    	
    	// get selected country an iban fields from the POST request
    	$options = array('country' => null);
    	$countrySelected = '';
    	$ibanEntered = '';
    	if ($request->isMethod('POST')) {
    		$formPosted = $request->request->get('ConvertSingleForm');
    		if (isset($formPosted['country'])) {
    			$countrySelected = $formPosted['country'];
    			$options = array('country' => $countrySelected);
    		}
    		if (isset($formPosted['iban'])) {
    			$ibanEntered = trim($formPosted['iban']);
    		}
    	}

    	$form = $this->createForm(new ConvertSingleForm(), null, $options);
    	$credits = $this->getAllowedConversions();
    	
    	
    	if ($request->isMethod('POST')) {
    		$form->bind($request);
    		
    		// do our own validation on required fields. Can't work out how to get symfony 2 to do dynamic validation based on the result of country dropdown
    		$errorExists = false;
    		$bbanMaps = $mappings->getBbanMappings($countrySelected);
    		$bbanOptional = $mappings->getBbanMappingsOptional($countrySelected);
    		if (is_array($bbanMaps)) {
	    		foreach ($bbanMaps as $key => $value) {
	    			
	    			if (!in_array($key, $bbanOptional))
	    			{
	    				if (!(isset($formPosted[$key]) && !empty($formPosted[$key]))) {
	    					$errorExists = true;
	    					$form->get($key)->addError(new FormError(sprintf('Invalid Details |%s is a required field. Please supply a value for %s and try again.', $value, $value)));
	    				}
	    			}
	    		}
    		}
    		else {
    			if ('' == $ibanEntered) {
    				$errorExists = true;
    				// $form->addError(new FormError(sprintf('Invalid Details| %s is a required field. Please select a country and try again.', 'Country')));
    				$form->addError(new FormError(sprintf('Invalid Details| Required fields have not been completed, either an %s must be supplied or a %s must be selected. Please try again.', 'IBAN', 'Country')));
    			}
    		}
    		
    		
    		// retrieve user if we are dealing with signin enabled
    		$this->setupUser();
    		
    		// check that the user has some credits
    		if ($this->conversionsRestricted()) {
    			$credits = $this->getAllowedConversions();
    			if ($credits < 1) {
    				$errorExists = true;
    				$form->addError(new FormError('Insufficient credit|You do not have sufficient funds in your account to carry out the check. Please credit your account and then try again.'));
    			}
    		}

    		
    		if ($form->isValid() && false == $errorExists)
    		{
    			$chargeUser = false;
    			
    			
    			$resultsConversion = $this->get('single_conversion');
    			
    			// build our commands
    			$args = array();
    			if (is_array($bbanMaps)) {
	    			foreach ($bbanMaps as $key => $value) {
	    				$args[] = $formPosted[$key];
	    			}
	    			
	    			$resultsConversion->runCountry($countrySelected, $args);
    			}
    			else if ('' != $ibanEntered) {
    				$resultsConversion->runIban($ibanEntered);
    			}
    			
    			
    			
	    			
	    		if (true == $resultsConversion->isFatal) {
	    			$form->addError(new FormError('Conversion not possible |Our conversion tool is offline, therefore, we are currenlty not able to process your request. Note: You have not been charged a conversion.'));
	    		} else if (false == $resultsConversion->isValid) {
	    			$form->addError(new FormError('Invalid Bank Account |The bank account provided is incorrect. Please check and try again.'));
	    			$chargeUser = true;
	    		}
	    		else {
	    			$accountValid = true;
	    			$chargeUser = true;
	    				
	    			if (true == $resultsConversion->isTransposed && true == $resultsConversion->convertByCountry)
	    			{
	    				$formNew = $this->createForm(new ConvertSingleForm(), null, $options);
	    				$formNew->get('country')->setData($form->get('country')->getData());
	    				$formNew->get('bban1')->setData($resultsConversion->bban1);
	    				$formNew->get('bban2')->setData($resultsConversion->bban2);
	    				$formNew->get('bban3')->setData($resultsConversion->bban3);
	    				$formNew->get('bban4')->setData($resultsConversion->bban4);
	    				$form = $formNew;
	    			}
	    		}
	    			
	    			
	    		if (true == $chargeUser) {
	    			if ($this->conversionsRestricted()) {
	    				$user = $this->get('user_manager');
	    				$user->reduceCredit($this->getUserId());
	    			}
	    		}
    		}
    	}
    	
    	
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
    
    
    public function setupUser()
    {
    	$config = $this->get('config_manager');
    	if ($config->signinRequired()) {
    		$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    		$this->user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email']);
    	}
    }

    
    public function getSessionId()
    {
    	return $this->get('session')->getId();
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
    	
    	if (!isset($this->user)) {
    		$this->setupUser();
    	}
    	
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