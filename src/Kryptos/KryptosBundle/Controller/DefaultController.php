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
    public function indexAction()
    {
    	$config = $this->get('config_manager');
    	
    	if ($config->siginDisabled()) {
    		echo "<br> go to home<br>";
    	}
    	else {
    		echo "<br>Redirect user to sign in page.they are not logged in.<BR><BR><br>";
    		
    		// forward to sign in controller
    		#$httpKernel = $this->container->get('http_kernel'); 	
			#$response = $httpKernel->forward('KryptosKryptosBundle:Default:signin');
    	}

        return $this->render('KryptosKryptosBundle:Default:index.html.twig', array('location' => 'homepage'));
    }
    
    
    
	public function signinAction(Request $request)
    {
    	$form = $this->createForm(new SigninForm());
					 
		if ($request->isMethod('POST')) {
			$form->bind($request);

			if ($form->isValid())
			{
				$userManager = $this->get('user_manager');
				$status = $userManager->checkSignin($form->getData());

				if (true == $status) {
					return $this->redirect($this->generateUrl('welcome'));
				}
				
				$form->addError(new FormError('Username and password do not match'));
			}
		}
    	
        return $this->render('KryptosKryptosBundle:Default:signin.html.twig', array(
        	'form' => $form->createView(),
        	'location' => 'signin'
        ));
    }

    
	public function registerAction(Request $request)
    {
		$form = $this->createForm(new RegistrationForm());
					 
		if ($request->isMethod('POST')) {
			$form->bind($request);

			if ($form->isValid())
			{
				$userManager = $this->get('user_manager');
				$user = $userManager->createUserFrom($form->getData());
				$userManager->save($user);
					
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
    	return $this->render('KryptosKryptosBundle:Default:welcome.html.twig', array('location' => 'Welcome page'));
    }
}
