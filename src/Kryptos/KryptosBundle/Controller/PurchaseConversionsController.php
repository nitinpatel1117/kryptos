<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Kryptos\KryptosBundle\Form\PurchaseConversionsForm;


class PurchaseConversionsController extends Controller
{
    public function indexAction()
    {
    	if (!$this->get('login_validator')->isLoginValid()) {
    		return $this->redirect($this->generateUrl('homepage'));
    	} 

        return $this->render('KryptosKryptosBundle:PurchaseConversions:index.html.twig', array('location' => 'Purchase Conversions'));
    }
    
    
    public function itemsAction()
    {
    	if (!$this->get('login_validator')->isLoginValid()) {
    		return $this->redirect($this->generateUrl('homepage'));
    	}
    	
    	$form = $this->createForm(new PurchaseConversionsForm());
    
    	return $this->render('KryptosKryptosBundle:PurchaseConversions:items.html.twig', array(
    		'form' 				=> $form->createView(),
    		'btn_calculate' 	=> 'Calculate Costs',
    	));
    }
    
    
    
    public function paymentAction()
    {
    	if (!$this->get('login_validator')->isLoginValid()) {
    		return $this->redirect($this->generateUrl('homepage'));
    	}
    
    	return $this->render('KryptosKryptosBundle:PurchaseConversions:payment.html.twig', array());
    }
    
    
    public function calculateRateAction(Request $request, $conversionAmount)
    {
    	$data = array();
    	
    	if ($this->get('login_validator')->isLoginValid()) {
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
    			$cost = $conversionAmount * $conversionRate / 100;
    			$data['body'] = array(
    				'cost' 	=> $cost,
    				"vat" 	=> $cost * ($vatRate / 100),
    			);
    		}
    		else {
    			$data['body'] = array('error' => $error_msg);
    		}
    	}
    	
    	$response = new Response(json_encode($data));
    	$response->headers->set('Content-Type', 'application/json');
    	return $response;
    }
}
