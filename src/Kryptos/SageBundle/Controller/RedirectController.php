<?php

namespace Kryptos\SageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Kryptos\KryptosBundle\Controller\LocaleInterface;

class RedirectController extends Controller implements LocaleInterface
{
	public function passAction(Request $request)
	{
		/*
		if (!$this->get('login_validator')->isLoginValid()) {
			return $this->redirect($this->generateUrl('homepage'));
		}
		*/
		
		$error = '';

		$VendorTxCode = $request->query->get('v');

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
			return $this->redirect($this->generateUrl('homepage'));
		}
		
		// check if this payment was for a uploaded file
		$lines = 0;
		if (isset($user['payment'][$paymentIndex]['purchaseForFile'])) {
			$fileDate = $this->get('file_manager')->getFileByFilename($user['payment'][$paymentIndex]['purchaseForFile']);
			if (isset($fileDate['approxLines'])) {
				$lines = $fileDate['approxLines'];
			}
		}
		
		
		return $this->render('KryptosSageBundle:Redirect:pass.html.twig', array(
			'credits_added' => $user['payment'][$paymentIndex]['purchase']['credits'],
			'credits_total' => $user['credits'],
			'lines'			=> $lines,
		));
	}
	
	
	
	public function failAction(Request $request)
    {
    	/*
    	if (!$this->get('login_validator')->isLoginValid()) {
    		return $this->redirect($this->generateUrl('homepage'));
    	}
    	*/
    	
    	$msg = '';

		$VendorTxCode = $request->query->get('v');

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
			return $this->redirect($this->generateUrl('homepage'));
		}
		
		
		$status = '';
		if (isset($user['payment'][$paymentIndex]['status'])) {
			$status = $user['payment'][$paymentIndex]['status'];
		}
		switch ($status)
		{
			// NOTAUTHED – The Sage Pay system could not authorise the transaction because the details provided by the Customer were incorrect, or not authenticated by the acquiring bank.
			case 'NOTAUTHED':
				$msg = $this->get('translator')->trans('txt_sage_error_status_notauthed');
				break;
			
			// ABORT 	 – The Transaction could not be completed because the user clicked the CANCEL button on the payment pages, or went inactive for 15 minutes or longer.
			case 'ABORT':
				$msg = $this->get('translator')->trans('txt_sage_error_status_abort');
				break;
				
			// REJECTED  – The Sage Pay System rejected the transaction because of the rules you have set on your account.
			case 'REJECTED':
				$msg = $this->get('translator')->trans('txt_sage_error_status_rejected');
				break;
				
			// ERROR 	 – An error occurred at Sage Pay which meant the transaction could not be completed successfully.
			case 'ERROR':
			default:
				$msg = $this->get('translator')->trans('txt_sage_error_status_error');
				break;
		}
		
    	
    	return $this->render('KryptosSageBundle:Redirect:fail.html.twig', array(
    		'msg' 		=> $msg,
    	));
    }
}
