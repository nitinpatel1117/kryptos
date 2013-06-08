<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\KryptosBundle\Form\SettingsUserDetailsForm;
use Kryptos\KryptosBundle\Form\SettingsUserPasswordForm;
use Kryptos\KryptosBundle\Entity\SettingsUserDetails;
use Kryptos\KryptosBundle\Entity\SettingsUserPassword;
use Kryptos\KryptosBundle\Lib\Encryption;
use Kryptos\KryptosBundle\Model\User;
use Symfony\Component\Form\FormError;


class AccountController extends Controller
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
					$session->saveLogin($form->getData()->getEmail(), $form->getData()->getFirstName());				// update the users username in the nav, as well as their email address is the session
					
					
					$this->get('session')->getFlashBag()->add('settingsPersonalUpdated', 'Your personal details have been successfully updated.');
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
						
						$this->get('session')->getFlashBag()->add('settingsPasswordUpdated', 'Your password has been successfully updated.');
						return $this->redirect($this->generateUrl('account_settings'));
					}
					
					$passwordForm->addError(new FormError('Incorrect password |Your old password is not correct. Kryptos passwords are case sensitive. Please check your CAPS lock key.'));
				}
			}
		}
		
		 
		return $this->render('KryptosKryptosBundle:Account:settings.html.twig', array(
			'form' 						=> $form->createView(),
			'passwordForm' 				=> $passwordForm->createView(),
			'location' 					=> 'User Details',
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
    	
        return $this->render('KryptosKryptosBundle:Account:summary.html.twig', array(
        	'location' 	=> 'Account Summary',
        ));
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
    	
    	list ($transactions, $conversionHistory) = $this->getUserPayments();
    	
    	$transactions = array_reverse($transactions);
    	foreach ($transactions as $transaction)
    	{
    		$date->setTimestamp($transaction['started']->sec);
    			
    		$status = 'Pending';
    		if (isset($transaction['status'])) {
    			if ('OK' == $transaction['status']) {
    				$status = 'Ok';
    			} else {
    				$status = 'Failed';
    			}
    		}
    			
	    	$payment = array(
	    		'id' 			=> $transaction['_id']->__toString(),
	    		'datetime' 		=> $date->format('d/m/Y H:i:s'),
	    		'type' 			=> 'Credit',
	    		'credits' 		=> isset($transaction['purchase']['credits']) 	? $transaction['purchase']['credits'] : '',
	    		'cost' 			=> isset($transaction['purchase']['cost']) 		? number_format($transaction['purchase']['cost'], 2) : '',
	    		'status'		=> $status,
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
    		$status = 'Ok';
    		if (isset($history['creditsUsed'])) {
    			$type = 'Debit';
    			$credit = $history['creditsUsed'];
    		} else if (isset($history['creditsRefunded'])) {
    			$type = 'Credit';
    			$credit = $history['creditsRefunded'];
    			$status .= ' - Refund'; 
    		}
    		
    		$date->setTimestamp($history['time']->sec);
    		
    		$id = null;
    		if (isset($history['file'])) {
    			$id = $history['file']->__toString();
    		}
    		else if (isset($history['id'])) {
    			$id = $history['id']->__toString();
    		}
    		$payment = array(
    			'id' 			=> $id,
    			'datetime' 		=> $date->format('d/m/Y H:i:s'),
    			'type' 			=> $type,
    			'credits' 		=> $credit,
    			'cost' 			=> null,
    			'status' 		=> $status,
    		);
    	
    		$payments[] = $payment;
    	}
    	
    	usort($payments, function($a, $b) {
    		return strcmp($a['datetime'], $b['datetime']);
    	});
    	
    	
    	$currency = $this->get('config_manager')->get('sagepay|CurrencySymbol');
    	$currency = utf8_encode(html_entity_decode($currency));
    	
    	return $this->render('KryptosKryptosBundle:Account:history.html.twig', array(
    		'location' => 'Payment History',
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
    	$conversionHistory = array();
    	 
    	if ($this->get('config_manager')->signinRequired()) {
    		$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    		$fields = array('payment'=>1, 'conversionHistory'=>1);
    		$user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email'], $fields);
    		
    		if (isset($user['payment'])) {
    			$payments = $user['payment'];
    		}
    		if (isset($user['conversionHistory'])) {
    			$conversionHistory = $user['conversionHistory'];
    		}
    	}
    	 
    	return array($payments, $conversionHistory);
    }
}