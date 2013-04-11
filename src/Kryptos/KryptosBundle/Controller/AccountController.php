<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class AccountController extends Controller
{
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
    	
    	$transactions = $this->getUserPayments();
    	$transactions = array_reverse($transactions);
    	foreach ($transactions as $transaction)
    	{
    		if (isset($transaction['status']) && 'OK' == $transaction['status']) {
    			$date->setTimestamp($transaction['started']->sec);
	    		$payment = array(
	    			'id' 			=> $transaction['_id']->__toString(),
	    			'datetime' 		=> $date->format('d/m/Y H:i:s'),
	    			'cardType' 		=> isset($transaction['cardType']) 				? $transaction['cardType'] : '',
	    			'credits' 		=> isset($transaction['purchase']['credits']) 	? $transaction['purchase']['credits'] : '',
	    			'cost' 			=> isset($transaction['purchase']['cost']) 		? number_format($transaction['purchase']['cost'], 2) : '',
	    			'creditsOld' 	=> isset($transaction['creditsOld']) 			? $transaction['creditsOld'] : '',
	    			'creditsNew' 	=> isset($transaction['creditsNew']) 			? $transaction['creditsNew'] : '',
	    		);
	
	    		$payments[] = $payment;
    		}
    	}
    	
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
    		$fields = array('credits'=>1);
    		$user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email'], $fields);
    		
    		if (isset($user['credits']) && is_numeric($user['credits'])) {
    			$credits = $user['credits'];
    		}
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
}