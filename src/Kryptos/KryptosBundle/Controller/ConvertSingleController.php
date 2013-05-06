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
    			
    			$output = array();
    			$single_command = $config->get('bankwizard|single_command');
    			$command = sprintf($single_command, $countrySelected, implode(' ', $args));
    			
    			
    			
    			echo "<pre>";
    			
    			putenv('BW3LIBS=env');
    			putenv('BWTABLES=/var/www/bwtables');
    			putenv('LD_LIBRARY_PATH=$BW3LIBS:$LD_LIBRARY_PATH');
    			
    			exec('export BW3LIBS=env', $output);
    			var_dump($output);
    			
    			exec('export BWTABLES=/var/www/bwtables', $output);
    			var_dump($output);
    			
    			exec('export LD_LIBRARY_PATH=$BW3LIBS:$LD_LIBRARY_PATH', $output);
    			var_dump($output);
    			
    			
    			$command = '(cd /var/www/bankwizard; ./bwibexam GB 400302 81557149)';
    			
    			
    			#$command = 'env |grep "BW3LIBS"';
    			#$command = 'env |grep "LD_LIBRARY_PATH"';
    			
    			#$command = 'whoami';

    			#$command = 'export BW3LIBS=env';
    			#$command = 'export BWTABLES=/var/www/bwtables';
    			#$command = 'export LD_LIBRARY_PATH=$BW3LIBS:$LD_LIBRARY_PATH';
    				
    			exec($command, $output, $return_val);
    			

    			echo "<pre>";
    			var_dump($command);
    			var_dump($output);
    			print_r($output);
    			var_dump($return_val);
    			
    			var_dump('#########################');
    			system('(cd /var/www/bankwizard; ./bwibexam GB 400302 81557149)');
    			exit;
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
}