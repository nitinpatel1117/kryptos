<?php

namespace Kryptos\SageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Kryptos\KryptosBundle\Controller\LocaleInterface;
use Kryptos\SageBundle\Form\PaymentBillingForm;
use Kryptos\SageBundle\Entity\PaymentBilling;
use Kryptos\SageBundle\Lib\SageRegisterPayment;
use Symfony\Component\Form\FormError;

class PaymentController extends Controller implements LocaleInterface
{
	public function billingAction(Request $request)
	{
		if (!$this->get('login_validator')->isLoginValid()) {
			return $this->redirect($this->generateUrl('homepage'));
		}

		$error = '';
		
		$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
		$user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email']);
		
		if ($request->isMethod('POST')) {
			$form = $this->createForm(new PaymentBillingForm());
			$form->bind($request);
			 
			if ($form->isValid()) {
				
				$paymentBilling = $form->getData();
				$paymentBilling->updateDelivery();
				$data = $paymentBilling->toArray();
				
				$sagePayment = new SageRegisterPayment($this->get('config_manager'));
				list($data, $user) = $sagePayment->makePaymentParameters($data, $user, $this->getNotificationUrl($request));

				$this->get('user_manager')->save($user);
				
				$sageResult = $sagePayment->registerPayment($data);

				$response= $sagePayment->processResult($sageResult);

				if (isset($response['Status']) && 'OK' == $response['Status'])
				{
					$lastTrans = array_pop($user['payment']);
					$lastTrans['VPSTxId'] 		= $response['VPSTxId'];
					$lastTrans['SecurityKey'] 	= $response['SecurityKey'];
					$lastTrans['NextURL']		= $response['NextURL'];
					
					// is this purchase part of a file upload
					$purchaseForFile = $this->purchaseForFile();
					if ('' != $purchaseForFile ) {
						$lastTrans['purchaseForFile'] = $purchaseForFile;
					}
					
					array_push($user['payment'], $lastTrans);
					$this->get('user_manager')->save($user);
					
					return $this->redirect($response['NextURL']);
				}
				else {
					// major error happened.. log and notifiy user
					$logger = $this->get('logger');
					$logger->err(sprintf('Error from payment gateway; Tried to register a payment request for user [%s]. Received Sagepay response : %s' , $user['_id'], $sageResult));
					
					$error = $this->get('translator')->trans('txt_sagepay_error_payment_register');
				}
			}
		}
		
		$currency = $this->get('config_manager')->get('sagepay|CurrencySymbol');
		$currency = utf8_encode(html_entity_decode($currency));
		
		return $this->render('KryptosSageBundle:Payment:billing.html.twig', array(
			'request' 	=> $request,
			'error' 	=> $error,
			'currency' 	=> $currency,
			'credits' 	=> $user['currentTrans']['credits'],
			'cost' 		=> round($user['currentTrans']['cost'], 2),
			'vat' 		=> round($user['currentTrans']['vat'], 2),
			'total' 	=> round($user['currentTrans']['total'], 2),
		));
	}
	
	
	
	public function billingFormAction(Request $request, $error)
    {
    	if (!$this->get('login_validator')->isLoginValid()) {
    		return $this->redirect($this->generateUrl('homepage'));
    	}
    	
    	$form = $this->createForm(new PaymentBillingForm());
    	
    	// if form was posted add posted values to form
    	if ($request->isMethod('POST')) {
    		$form->bind($request);
    		$paymentBilling = $form->getData();
    		if (!empty($error)) {
    			$form->addError(new FormError($error));
    		}
    	}
    	else {
    		// get users firstname and lastname and add to the form
    		$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    		$user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email']);
    		 
    		$paymentBilling = new PaymentBilling();
    		$paymentBilling->setBillingSurname($user['lastName']);
    		$paymentBilling->setBillingFirstnames($user['firstName']);
    		$form->setData($paymentBilling);
    	}
    	
    	return $this->render('KryptosSageBundle:Payment:billingForm.html.twig', array(
    		'form' 			=> $form->createView(),
    	));
    }
    
    
    
    
    public function getNotificationUrl(Request $request)
    {
    	$baseUrl 		= $this->get('config_manager')->get('site|url');
    	$route 			= $this->get('config_manager')->get('sagepay|NotificationURL');
    	return sprintf('%s%s%s', $this->getProtocol($request), $baseUrl, $this->generateUrl($route));
    }
    
    public function getProtocol(Request $request)
    {
    	return $request->isSecure() ? 'https://' : 'http://';
    }
    
    
    public function purchaseForFile()
    {
    	$sessionStore = $this->get('session');
    	$sessionId = $sessionStore->getId();
    	return $sessionStore->get($sessionId.'_purchaseForFile');
    }
}
