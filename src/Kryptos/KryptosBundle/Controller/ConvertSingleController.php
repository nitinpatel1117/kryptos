<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
# use Symfony\Component\HttpFoundation\Session\Session;
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

    	$options = array('country' => null);
    	$countrySelected = '';
    	if ($request->isMethod('POST')) {
    		$formPosted = $request->request->get('ConvertSingleForm');
    		if (isset($formPosted['country'])) {
    			$countrySelected = $formPosted['country'];
    			$options = array('country' => $countrySelected);
    		}
    	}

    	$form = $this->createForm(new ConvertSingleForm(), null, $options);
    	$credits = $this->getAllowedConversions();
    	
    	if ($credits < 1) {
    		$userNote = 'You no not have any conversions available. You will need to purchase conversions in order to proceed.';
    	} else {
    		$userNote = sprintf('You have %s conversions available.', $credits);
    	}
    	
    	if ($request->isMethod('POST')) {
    		$form->bind($request);

    		// do our own validation on required fields. Can't work out how to get symfony 2 to do dynamic validation based on the result of country dropdown
    		$errorExists = false;
    		$bbanMaps = $mappings->getBbanMappings($countrySelected);
    		if (is_array($bbanMaps)) {
	    		foreach ($bbanMaps as $key => $value) {
	    			if (!(isset($formPosted[$key]) && !empty($formPosted[$key]))) {
	    				$errorExists = true;
	    				$form->addError(new FormError(sprintf('%s is required', $value)));
	    			}
	    		}
    		}
    		else {
    			$errorExists = true;
    			$form->addError(new FormError(sprintf('%s is required', 'Country')));
    		}

    		
    		if ($form->isValid() && false == $errorExists) {
    			// retrieve user if we are dealing with signin enabled
    			$this->setupUser();
    			
    			// build our commands
    			$args = array();
    			foreach ($bbanMaps as $key => $value) {
    				$args[] = $formPosted[$key];
    			}
    			
    			$output = $this->runSingleConversionCommand($countrySelected, $args);
    			$output = $this->processOutput($output);
    			list($iban, $bic) = $this->getIbanAndBic($output);
    			
    			if (!is_null($iban) && !is_null($bic)) {
    				$accountValid = true;
    			}

    			
    			#echo "<pre>";
    			#var_dump($iban, $bic);
    	
    			#print_r($output);
    			#exit;
    		}
    	}
    	
        return $this->render('KryptosKryptosBundle:ConvertSingle:index.html.twig', array(
        	'form' 						=> $form->createView(),
        	'location' 					=> 'Single Convert',
        	'btn_submit' 				=> 'Convert',
        	'credits' 					=> $credits,
        	'conversionsRestricted' 	=> $this->conversionsRestricted(),
        	'userNote' 					=> $userNote,
        	'bbanMappings' 				=> $mappings->getBbanMappings(),
        	'countrySelected'			=> $countrySelected,
        	
        	'accountValid'				=> $accountValid,
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
     */
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
    
    
    /**
     * Function takes the output supplied by the single conversion tool and turns it into a 
     * an associative array that can be transversd easily.
     * 
     * @param array $output
     * @return array
     */
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
    }
    
    
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
    	
    	return array($iban, $bic);
    }
}