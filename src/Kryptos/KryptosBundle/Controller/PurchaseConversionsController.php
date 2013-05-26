<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Kryptos\KryptosBundle\Form\PurchaseConversionsForm;
use Symfony\Component\Form\FormError;


class PurchaseConversionsController extends Controller
{
    public function indexAction(Request $request)
    {
    	if (!$this->get('login_validator')->isLoginValid()) {
    		return $this->redirect($this->generateUrl('homepage'));
    	}
    	
    	$error = '';
    	
    	$purchaseRequired = $this->checkPurchaseRequired($request);
    	
    	if ($request->isMethod('POST')) {
    		$form = $this->createForm(new PurchaseConversionsForm());
    		$form->bind($request);
    		 
    		if ($form->isValid()) {
    			$conversion = $form->getData()->getConversions();
    			$data = $this->calcRates($conversion);
    			
    			if (!isset($data['body']['error'])) {
    				$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    				$user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email']);
    	
    				$user['currentTrans']['credits'] 	= $conversion;
    				$user['currentTrans']['cost'] 		= round($data['body']['cost'], 2);
    				$user['currentTrans']['vat'] 		= round($data['body']['vat'], 2);
    				$user['currentTrans']['total'] 		= round($user['currentTrans']['cost'] + $user['currentTrans']['vat'], 2);
    				$this->get('user_manager')->save($user);
    	
    				return $this->redirect($this->generateUrl('payment_billing'));
    			}
    			
    			$error = $data['body']['error'];
    		}
    	}
    	

        return $this->render('KryptosKryptosBundle:PurchaseConversions:index.html.twig', array(
        	'location' 			=> 'Purchase Conversions',
        	'request' 			=> $request,
        	'error' 			=> $error,
        	'purchaseRequired' 	=> $purchaseRequired,
        ));
    }
    
    
    public function checkPurchaseRequired($request)
    {
    	$purchaseRequired = 0;
    	
    	$sessionStore = $this->get('session');
    	$sessionId = $sessionStore->getId();
    	
    	if ($request->query->has('f')) {
    		$filename = $request->query->get('f');
    		$fileDate = $this->get('file_manager')->getFileByFilename($filename);
 
    		$lines = 0;
    		if (isset($fileDate['status']) && 'awaiting_payment' == $fileDate['status'])
    		{
    			// get user credits
    			$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    			$credits = $this->get('user_manager')->getUserCredits($userSessionDetails['email']);
    			
    			if (isset($fileDate['approxLines'])) {
    				$lines = $fileDate['approxLines'];
    			}
    			
    			if ($lines > $credits) {
    				$purchaseRequired = $lines - $credits;
    			}
    		}
    		
    		$sessionStore->set($sessionId.'_purchaseForFile', $filename);
    	}
    	else {
    		$sessionStore->set($sessionId.'_purchaseForFile', '');
    	}
    
    	 return $purchaseRequired;
    }
    
    
    public function itemsAction(Request $request, $error)
    {
    	if (!$this->get('login_validator')->isLoginValid()) {
    		return $this->redirect($this->generateUrl('homepage'));
    	}
    	
    	$currency = $this->get('config_manager')->get('sagepay|CurrencySymbol');
    	$currency = utf8_encode(html_entity_decode($currency));
    	$options = array('currency' => $currency);

    	$form = $this->createForm(new PurchaseConversionsForm(), null, $options);
    	
    	if (!empty($error)) {
    		$form->addError(new FormError($error));
    	}
    	
    	$formAction = $this->generateUrl('purchase_conversions');
    	if ($request->query->has('f')) {
    		$formAction .= sprintf('?f=%s', $request->query->get('f'));
    	}
    	
    	return $this->render('KryptosKryptosBundle:PurchaseConversions:items.html.twig', array(
    		'form' 				=> $form->createView(),
    		'btn_calculate' 	=> 'Calculate Costs',
    		'btn_submit' 		=> 'Purchase',
    		'formAction'		=> $formAction,
    	));
    }

    
    public function calculateRateAction(Request $request, $conversionAmount)
    {
    	$data = array();
    	
    	if ($this->get('login_validator')->isLoginValid()) {
    		$data = $this->calcRates($conversionAmount);
    	}
    	
    	$response = new Response(json_encode($data));
    	$response->headers->set('Content-Type', 'application/json');
    	return $response;
    }
    
    
    protected function calcRates($conversionAmount)
    {
    	$data = array();
    	
    	$conversionRate = $this->get('config_manager')->get('purchase_conversions|conversion_rate');
    	$vatRate = $this->get('config_manager')->get('purchase_conversions|vat_rate');
    	
    	$error = false;
    	$error_msg = '';
    	
    	if (is_numeric($conversionAmount)) {
    		$conversionAmount = (int) $conversionAmount;
    	}else {
    		$error = true;
    		$error_msg = 'No. of Conversions must be entered as a number';
    	}
    	
    	if (is_numeric($conversionRate)) {
    		$conversionRate = (float) $conversionRate;
    	}else {
    		$error = true;
    	}
    	
    	// make user VAT rate is between [0 - 100] inclusive
    	if (is_numeric($vatRate)) {
    		$vatRate = (float) $vatRate;
    		if (0 > $vatRate || $vatRate > 100) {
    			$error = true;
    		}
    	}else {
    		$error = true;
    	}
    	
    	if (false == $error){
    		$cost = round ($conversionAmount * $conversionRate, 2);
    		$vat  = round ($cost * ($vatRate / 100), 2);
    		
    		$data['body'] = array('cost' => $cost, 'vat' => $vat);
    		if (1 > $cost + $vat) {
    			$currency = $this->get('config_manager')->get('sagepay|CurrencySymbol');
    			$currency = utf8_encode(html_entity_decode($currency));
    			$data['body']['error'] = sprintf('Increase conversions |Total cost is less than %s1. Please increase the No. of conversions to meet the minimum total of %s1', $currency, $currency);
    		}
    		else if ($cost + $vat > 100000) {
    			$currency = $this->get('config_manager')->get('sagepay|CurrencySymbol');
    			$currency = utf8_encode(html_entity_decode($currency));
    			$data['body']['error'] = sprintf('Increase conversions |Total cost is greater than %s100,000. Please decrease the No. of conversions to meet the maximum expenture total of %s100,000', $currency, $currency);
    		}
    		
    	}
    	else {
    		$data['body'] = array('error' => $error_msg);
    	}
    	
    	return $data;
    }
}
