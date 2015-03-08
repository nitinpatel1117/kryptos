<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\KryptosBundle\Controller\LocaleInterface;
use Kryptos\KryptosBundle\Form\SettingsUserDetailsForm;
use Kryptos\KryptosBundle\Form\SettingsUserPasswordForm;
use Kryptos\KryptosBundle\Entity\SettingsUserDetails;
use Kryptos\KryptosBundle\Entity\SettingsUserPassword;
use Kryptos\KryptosBundle\Lib\Encryption;
use Kryptos\KryptosBundle\Model\User;
use Symfony\Component\Form\FormError;


class AccountController extends Controller implements LocaleInterface
{
	public function settingsAction(Request $request)
	{
		$config = $this->get('config_manager');
		$session = $this->get('login_validator');
	
		if ($config->signinRequired() && !$session->isLoginValid()) {
			return $this->redirect($this->generateUrl('welcome'));
		}
		
		$personalDetailsUpdated = $this->getFromFlashBag('settingsPersonalUpdated');
		$passwordUpdated 		= $this->getFromFlashBag('settingsPasswordUpdated');
		
		
		$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
		
		$fields = array(
			'title' => 1,
			'firstName' => 1,
			'lastName' => 1,
			'jobTitle' => 1,
			'company' => 1,
			'location' => 1,
			'email' => 1,
		);
		$user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email'], $fields);
		$settingUSerDetails = new SettingsUserDetails();
		$settingUSerDetails->makeFromArray($user);
		$form = $this->createForm(new SettingsUserDetailsForm(), $settingUSerDetails);
		
		$passwordForm = $this->createForm(new SettingsUserPasswordForm());
		
		if ($request->isMethod('POST'))
		{
			if ($request->request->has('SettingsUserDetailsForm')) {
				$form->bind($request);
				if ($form->isValid()) {
					$formData = $form->getData()->toArray();
					$newUserDetails = array_merge($user, $formData);
					
					$this->get('user_manager')->updatePersonalDetails($user['_id']->__toString(), $newUserDetails);
					$session->saveLogin($form->getData()->getEmail(), $form->getData()->getFirstName(), $user['_id']->__toString());				// update the users username in the nav, as well as their email address is the session
					
					
					$this->get('session')->getFlashBag()->add('settingsPersonalUpdated', 'msg_desc_details_updated');
					return $this->redirect($this->generateUrl('account_settings'));
				}
			}
			
			if ($request->request->has('SettingsUserPasswordForm')) {
				$passwordForm->bind($request);
				if ($passwordForm->isValid()) {
					$fields = array(
						'salt' => 1,
						'password' => 1,
					);
					$user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email'], $fields);
					
					$userObject = new User();
					$userObject->setSalt($user['salt']);
					$userObject->setPassword($user['password']);
					
					$encryption = new Encryption();
					$valid = $encryption->isPasswordValid($passwordForm->get('oldPassword')->getData(), $userObject);
					if (true == $valid) {
						$this->get('user_manager')->doPasswordReset($user, $passwordForm->get('password')->getData());
						
						$this->get('session')->getFlashBag()->add('settingsPasswordUpdated', 'msg_desc_password_updated');
						return $this->redirect($this->generateUrl('account_settings'));
					}
					
					$passwordForm->addError(new FormError('msg_title_incorrect_password |msg_desc_incorrect_password'));
				}
			}
		}
		
		 
		return $this->render('KryptosKryptosBundle:Account:settings.html.twig', array(
			'form' 						=> $form->createView(),
			'passwordForm' 				=> $passwordForm->createView(),
			'personalDetailsUpdated'	=> $personalDetailsUpdated,
			'passwordUpdated'			=> $passwordUpdated,
		));
	}
	
	public function getFromFlashBag($name)
	{
		$msg = '';
		 
		$messageData = $this->get('session')->getFlashBag()->get($name);
		if (is_array($messageData) && count($messageData) > 0 ) {
			$msg = array_shift($messageData);
		}
		 
		return $msg;
	}
	
	
    public function summaryAction(Request $request)
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');

    	if ($config->signinRequired() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	
        return $this->render('KryptosKryptosBundle:Account:summary.html.twig', array());
    }
    
    
    public function balanceAction(Request $request)
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');
    
    	if ($config->signinRequired() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	
    	$credits = $this->getUserCredits();
    	$moneyBalance = $this->calculateBalanceFromCredits($credits);
    	
    	$currency = $this->get('config_manager')->get('sagepay|CurrencySymbol');
    	$currency = utf8_encode(html_entity_decode($currency));
    	 
    	return $this->render('KryptosKryptosBundle:Account:balance.html.twig', array(
    		'currency' 				=> $currency,
    		'current_balance' 		=> $moneyBalance,
    		'available_conversion' 	=> $credits,
    	));
    }
    
    
    public function historyAction(Request $request)
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');

    	if ($config->signinRequired() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    
    	
    	$payments = array();
    	$date = new \DateTime();
    	
    	$transactions 		= $this->getUserPayments();
    	$conversionHistory 	= $this->getUserConversionHistory();
    	
    	$transactions = array_reverse($transactions);
    	foreach ($transactions as $transaction)
    	{
    		$date->setTimestamp($transaction['started']->sec);
    			
    		$status = 'txt_pending';
    		$show_invoice = false;
    		if (isset($transaction['status'])) {
    			if ('OK' == $transaction['status']) {
    				$status = 'txt_ok';
    				$show_invoice = true;
    			} else {
    				$status = 'txt_failed';
    			}
    		}
    			
	    	$payment = array(
	    		'id' 			=> $transaction['_id']->__toString(),
	    		#'datetime' 		=> $date->format('d/m/Y H:i:s'),
	    		'datetime' 		=> clone $date,
	    		'type' 			=> 'txt_credit',
	    		'credits' 		=> isset($transaction['purchase']['credits']) 	? $transaction['purchase']['credits'] : '',
	    		'cost' 			=> isset($transaction['purchase']['total']) 	? number_format($transaction['purchase']['total'], 2) : '',
	    		'status'		=> $status,
	    		'show_invoice'	=> $show_invoice,
	    	#	'creditsOld' 	=> isset($transaction['creditsOld']) 			? $transaction['creditsOld'] : '',
	    	#	'creditsNew' 	=> isset($transaction['creditsNew']) 			? $transaction['creditsNew'] : '',
	    	);
	
	    	$payments[] = $payment;
    	}
    	
    	$conversionHistory = array_reverse($conversionHistory);
    	foreach ($conversionHistory as $history)
    	{
    		$credit = null;
    		$type = null;
    		$status = 'txt_ok';
    		if (isset($history['creditsUsed'])) {
    			$type = 'txt_debit';
    			$credit = $history['creditsUsed'];
    		} else if (isset($history['creditsRefunded'])) {
    			$type = 'txt_credit';
    			$credit = $history['creditsRefunded'];
    			$status = 'txt_ok_refunded'; 
    		}
    		
    		$date->setTimestamp($history['time']->sec);
    		
    		$id = null;
    		if (isset($history['file'])) {
    			$id = $history['file']->__toString();
    		}
    		else if (isset($history['_id'])) {
    			$id = $history['_id']->__toString();
    		}
    		$payment = array(
    			'id' 			=> $id,
    			#'datetime' 		=> $date->format('d/m/Y H:i:s'),
    			'datetime' 		=> clone $date,
    			'type' 			=> $type,
    			'credits' 		=> $credit,
    			'cost' 			=> isset($history['totalCost']) ? number_format($history['totalCost'], 2) : '',
    			'status' 		=> $status,
    			'show_invoice'	=> false,
    		);
    	
    		$payments[] = $payment;
    	}
    	
    	usort($payments, function($a, $b) {
    		return ($a['datetime'] < $b['datetime']) ? -1 : 1;
    	});
    	
    	
    	$currency = $this->get('config_manager')->get('sagepay|CurrencySymbol');
    	$currency = utf8_encode(html_entity_decode($currency));
    	
    	return $this->render('KryptosKryptosBundle:Account:history.html.twig', array(
    		'currency' => $currency,
    		'payments' => $payments,
    	));
    }
    
    
    /**
     * Get credits for the currently signed in user
     * 
     * @return float
     */
    public function getUserCredits()
    {	
    	// set default value to starting amount, in case user has not got credits yet
    	$credits = $this->get('config_manager')->get('credits');
    	
    	if ($this->get('config_manager')->signinRequired()) {
    		$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    		$credits = $this->get('user_manager')->getUserCredits($userSessionDetails['email']);
    	}
    	
    	return $credits;
    }
    
    
    public function calculateBalanceFromCredits($credits)
    {
    	$balance = 0;
    	
    	$conversionRate = $this->get('config_manager')->get('purchase_conversions|conversion_rate');
    	$balance = $conversionRate * $credits;
    	
    	return round($balance, 2);
    }
    

    public function getUserPayments()
    {
    	$payments = array();
    	 
    	if ($this->get('config_manager')->signinRequired()) {
    		$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    		$fields = array('payment'=>1);
    		$user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email'], $fields);
    		
    		if (isset($user['payment'])) {
    			$payments = $user['payment'];
    		}
    	}
    	 
    	return $payments;
    }
    
    
    public function getUserConversionHistory()
    {
    	$conversionHistory = array();
    
    	if ($this->get('config_manager')->signinRequired()) {
    		$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    		$conversionHistory = $this->get('conversion_manager')->getItemsByUserId($userSessionDetails['id']);
    	}
    
    	return $conversionHistory;
    }
}