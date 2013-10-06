<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\KryptosBundle\Controller\LocaleInterface;


class ApiController extends Controller implements LocaleInterface
{
    public function detailsAction(Request $request)
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');
    
    	if ($config->signinRequired() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	
    	$publicKey = '';
    	$privateKey = '';
    	
    	if ($this->get('config_manager')->signinRequired()) {
    		$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    		$fields = array('privateKey'=>1, 'publicKey'=>1);
    		$user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email'], $fields);
    		
    		if (isset($user['publicKey'])) {
    			$publicKey = $user['publicKey']->{'$id'};
    		}
    		if (isset($user['privateKey'])) {
    			$privateKey = $user['privateKey'];
    		}
    	}

    	return $this->render('KryptosKryptosBundle:Api:details.html.twig', array(
    		'location' => 'API',
    		'publicKey' => $publicKey,
    		'privateKey' => $privateKey,
    	));
    }
}