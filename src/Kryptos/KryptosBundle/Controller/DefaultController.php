<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\KryptosBundle\Entity\Register;
use Kryptos\KryptosBundle\Form\RegistrationForm;
use Kryptos\KryptosBundle\Form\SigninForm;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\FormError;

class DefaultController extends Controller
{
	
	protected function getSessionId()
	{
		$session = new Session();
		$session->start();
		return $session->getId();
	}
	
	
    public function indexAction()
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');

    	if ($config->siginDisabled() || $session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	
    	// forward to sign in controller
    	#$httpKernel = $this->container->get('http_kernel'); 	
		#$response = $httpKernel->forward('KryptosKryptosBundle:Default:signin');

        return $this->render('KryptosKryptosBundle:Default:index.html.twig', array('location' => 'homepage'));
    }
    
    
    
	public function signinAction(Request $request)
    {
    	$session = $this->get('login_validator');
    	if ($session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	
    	$form = $this->createForm(new SigninForm());
					 
		if ($request->isMethod('POST')) {
			$form->bind($request);

			if ($form->isValid())
			{
				$userManager = $this->get('user_manager');
				$status = $userManager->checkSignin($form->getData());

				if (true == $status) {
					$session->saveLogin($form->getData()->getEmail());
					return $this->redirect($this->generateUrl('welcome'));
				}
				
				$form->addError(new FormError('Email and password do not match'));
			}
		}
    	
        return $this->render('KryptosKryptosBundle:Default:signin.html.twig', array(
        	'form' => $form->createView(),
        	'location' => 'signin'
        ));
    }
    
    
    public function logoutAction()
    {
    	$session = $this->get('login_validator');
    	$session->logout();
    	return $this->redirect($this->generateUrl('homepage'));
    	
    }

    
	public function registerAction(Request $request)
    {
	    $session = $this->get('login_validator');
    	if ($session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	
		$form = $this->createForm(new RegistrationForm());
					 
		if ($request->isMethod('POST')) {
			$form->bind($request);

			if ($form->isValid()) {
				$userManager = $this->get('user_manager');
				$user = $userManager->createUserFrom($form->getData());
				$userManager->save($user);
				
				$session->saveLogin($user->getEmail());
					
				return $this->redirect($this->generateUrl('welcome'));
			}
		}
		
        return $this->render('KryptosKryptosBundle:Default:register.html.twig', array(
        	'form' => $form->createView(),
        	'location' => 'register',
        	'captchaMessage' => 'Type the characters you see in the picture below.'
        ));
    }
    
    
	public function registerSuccessAction(Request $request)
    {
        return $this->render('KryptosKryptosBundle:Default:registerSuccess.html.twig', array(
        	'header' => 'You have registered successfully',
        	'link_url' => $this->generateUrl('signin'),
        	'link_text' => 'signin',
        ));
    }
    
    
    public function welcomeAction()
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');
    	if (!$config->siginDisabled() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('homepage'));
    	}
    	
    	return $this->render('KryptosKryptosBundle:Default:welcome.html.twig', array('location' => 'Welcome page'));
    }
    
    
    public function authenicationBarAction()
    {
    	$signin   = false;
    	$register = false;
    	$logout   = false;
    	$allDisabled = false;
    	
    	$config = $this->get('config_manager');
    	if (!$config->siginDisabled()) {
    		$session = $this->get('login_validator');
    		if ($session->isLoginValid()) {
    			$logout   = true;
    		}
    		else {
    			$signin   = true;
    			$register = true;
    		}
    	}
    	else {
    		$allDisabled = true;
    	}

        return $this->render('KryptosKryptosBundle:Default:authenicationBar.html.twig', array(
        	'signin' 		=> $signin,
        	'register' 		=> $register,
        	'logout' 		=> $logout,
        	'allDisabled'	=> $allDisabled,
        ));
    }
}
