<?php

namespace Kryptos\SageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SageController extends Controller
{
	
	protected $requiredKeys = array(
		'VendorTxCode',
		'VPSTxId',
		'Status',
		'AVSCV2',
		'AddressResult',
		'PostCodeResult',
		'CV2Result',
		'GiftAid',
		'3DSecureStatus',
		'CardType',
		'Last4Digits',
		'VPSSignature',
	);
		
	protected $optionalKeys = array(
		'AddressStatus',		// received if we are handling ebay transaction
		'PayerStatus',			// received if we are handling ebay transaction
		'TxAuthNo',				// received when status OK
		'StatusDetail',			// received when status not OK
		'CAVV',					// received when 3DSecureStatus field is OK
	);

	protected $VendorTxCode;
	
	
	public function notifyAction(Request $request)
	{
		$data = $this->get('request')->request->all();
		$error = '';
		
		// log the notify request
		$logger = $this->get('logger');
		$logger->info(serialize($data));

		foreach ($this->requiredKeys as $requiredKey) {
			if (!isset($data[$requiredKey])) {
				$error .= sprintf('%s is not set.', $requiredKey);
			}
			else {
				$name = '';
				if (is_numeric($requiredKey[0])) {
					$name .= '_';
				}
				$name .= $requiredKey;
				$$name = $data[$requiredKey];
			}
		}
		
		// this variables is used to construct query string for the redirect url
		if (isset($VendorTxCode)) {
			$this->VendorTxCode = $VendorTxCode;
		}
		
		if (!empty($error)) {
			return $this->sendInvalidResponse($request, $error);
		}
		
		foreach ($this->optionalKeys as $optionalKey) {
			$$optionalKey = '';
			if (isset($data[$optionalKey])) {
				$$optionalKey = $data[$optionalKey];
			}
		}

		// find the payment of this user
		$paymentIndex = '';
		$user = $this->get('user_manager')->getUserVendorTxCode($VendorTxCode);
		if (is_array($user) && isset($user['payment']) && is_array($user['payment'])) {
			foreach ($user['payment'] as $key => $payment) {
				if ($payment['VendorTxCode'] == $VendorTxCode) {
					$paymentIndex = $key;
				}
			}
		}
		if ('' === $paymentIndex) {
			return $this->sendInvalidResponse($request, 'VendorTxCode was not found for any user');
		}
		
		
		// check security signature is ok
		$VendorName = $this->get('config_manager')->get('sagepay|Vendor');
		$SecurityKey = $user['payment'][$paymentIndex]['SecurityKey'];
		$ourKey = $VPSTxId . $VendorTxCode . $Status . $TxAuthNo . $VendorName . $AVSCV2 . $SecurityKey . $AddressResult . $PostCodeResult . $CV2Result . $GiftAid . $_3DSecureStatus . $CAVV . $AddressStatus . $PayerStatus . $CardType . $Last4Digits;
		$ourSignature = strtoupper(md5($ourKey));
		if ($ourSignature !== $VPSSignature) {
			return $this->sendInvalidResponse($request, 'TAMPER WARNING! Signatures do not match.');
		}
		
		
		// have we already processed a notification for this user.
		if ('complete' === $user['payment'][$paymentIndex]['action']) {
			return $this->sendOkResponse($request, $user['payment'][$paymentIndex]['status'], 'A notification has already been received and processed for this user');
		}
		
		$fileStatus = '';
		
		// only check status once we have confirmed the security signature
		switch ($Status)
		{
			// OK – Transaction completed successfully with authorisation.
			case 'OK':
				$user['credits'] += $user['payment'][$paymentIndex]['purchase']['credits'];				// increment user credit count
				$user['payment'][$paymentIndex]['creditsNew'] 	= $user['credits'];						// store the new credit count in history
				$user['payment'][$paymentIndex]['TxAuthNo'] 	= $TxAuthNo;
				$this->get('user_manager')->save($user);
				$fileStatus = 'pending';
				break;

			// NOTAUTHED – The Sage Pay system could not authorise the transaction because the details provided by the Customer were incorrect, or not authenticated by the acquiring bank.
			// ABORT 	 – The Transaction could not be completed because the user clicked the CANCEL button on the payment pages, or went inactive for 15 minutes or longer.
			// REJECTED  – The Sage Pay System rejected the transaction because of the rules you have set on your account.
			// ERROR 	 – An error occurred at Sage Pay which meant the transaction could not be completed successfully.
			case 'NOTAUTHED':
			case 'ABORT':
			case 'REJECTED':
			case 'ERROR':
				$user['payment'][$paymentIndex]['statusDetail']	= $StatusDetail;
				$fileStatus = 'payment_failed';
				break;
				
			default:
				return $this->sendInvalidResponse($request, sprintf('Unknown value supplied for Status. Status received as [%s]', $Status));
				break;
		}
		
		
		$user['payment'][$paymentIndex]['completed'] 	= new \MongoDate();
		$user['payment'][$paymentIndex]['action']	 	= 'complete';
		$user['payment'][$paymentIndex]['status']	 	= $Status;
		$user['payment'][$paymentIndex]['cardType']	 	= $CardType;
		$user['currentTrans'] 							= array();
		
			
		// store original data that was posted to us
		$data['user_id'] = $user['_id'];
		$this->get('sage_notification_manager')->save($data);
		
		// add reference to the saved sage_notificaton to the user object
		$user['payment'][$paymentIndex]['sage_notification_ref'] = $data['_id'];
		$this->get('user_manager')->save($user);
		
		
		// check if this payment was done as part of a file upload
		if (isset($user['payment'][$paymentIndex]['purchaseForFile']))
		{
			$fileDate = $this->get('file_manager')->getFileByFilename($user['payment'][$paymentIndex]['purchaseForFile']);
			$lines = 0;
			if (isset($fileDate['status']) && 'awaiting_payment' == $fileDate['status'] && isset($fileDate['approxLines'])) {
				$lines = $fileDate['approxLines'];
				$fileDate['status'] = $fileStatus;
				
				if ('pending' == $fileDate['status']) {
					$this->get('user_manager')->registerCreditsUsed( $user['_id'],  $fileDate['_id'], $user['credits'], ($user['credits']-$lines));
					
					
					#if ($user['credits'] >= $lines) {
					#	$user['credits'] -= $lines;
					#}
				}
				
				$this->get('file_manager')->save($fileDate);
				#$this->get('user_manager')->save($user);
			}
		}
		

		return $this->sendOkResponse($request, $Status);
	}
	
	
	
	public function sendOkResponse(Request $request, $status, $msg = '')
	{
		if ('OK' == $status) {
			$redirectUrl = $this ->makePassRedirect();
		} else {
			$redirectUrl = $this ->makeFailRedirect();
		}
		
		return $this->render('KryptosSageBundle:Sage:notify.html.twig', array(
				'Status' 		=> 'OK',
				'RedirectURL' 	=> $redirectUrl,
				'StatusDetail' 	=> $msg,
				'CRLF'			=> "\r\n",
		));
	}
	
	
	public function sendInvalidResponse(Request $request, $error)
	{		
		return $this->render('KryptosSageBundle:Sage:notify.html.twig', array(
				'Status' 		=> 'INVALID',
				'RedirectURL' 	=> $this ->makeFailRedirect(),
				'StatusDetail' 	=> $error,
				'CRLF'			=> "\r\n",
		));
	}
	
	
	public function sendErrorResponse(Request $request, $error)
	{
		return $this->render('KryptosSageBundle:Sage:notify.html.twig', array(
				'Status' 		=> 'ERROR',
				'RedirectURL' 	=> $this ->makeFailRedirect(),
				'StatusDetail' 	=> $error,
				'CRLF'			=> "\r\n",
		));
	}
	
	
	
	
	public function makePassRedirect()
	{
		$baseUrl 		= $this->get('config_manager')->get('site|url');
		$route 			= $this->get('config_manager')->get('sagepay|RedirectURLPass');
		$queryString	= $this->makeQueryString();
		return sprintf('%s%s%s%s', $this->getProtocol(), $baseUrl, $this->generateUrl($route), $queryString);
	}
	
	public function makeFailRedirect()
	{
		$baseUrl 		= $this->get('config_manager')->get('site|url');
		$route 			= $this->get('config_manager')->get('sagepay|RedirectURLFail');
		$queryString	= $this->makeQueryString();
		return sprintf('%s%s%s%s', $this->getProtocol(), $baseUrl, $this->generateUrl($route), $queryString);
	}
	
	public function getProtocol()
	{
		return strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'? 'https://' : 'http://';
	}
	
	
	public function makeQueryString()
	{
		$str = '';
		$data = array();
		
		if (isset($this->VendorTxCode)) {
			$data['v'] = $this->VendorTxCode;
		}
		
		$paramsJoined = array();
		foreach($data as $param => $value) {
		   $paramsJoined[] = "$param=$value";
		}
		$str = implode('&', $paramsJoined);
		
		if (!empty($str)) {
			$str = '?'.$str;
		}
		
		return $str;
	}
}
