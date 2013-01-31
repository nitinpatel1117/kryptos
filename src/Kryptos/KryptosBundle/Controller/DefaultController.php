<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\KryptosBundle\Entity\Register;
use Kryptos\KryptosBundle\Form\RegistrationForm;
use Kryptos\KryptosBundle\Model\Manager\UserManager;

class DefaultController extends Controller
{
    public function indexAction()
    {
    	$config = $this->get('config_manager');
    	
    	if ($config->siginDisabled()) {
    		echo "<br> go to home<br>";
    	}
    	else {
    		echo "<br> go to sign in page<br>";
    		
    		// forward to sign in controller
    		#$httpKernel = $this->container->get('http_kernel'); 	
			#$response = $httpKernel->forward('KryptosKryptosBundle:Default:signin');
    	}

        return $this->render('KryptosKryptosBundle:Default:index.html.twig', array('location' => 'homepage'));
    }
    
    
    
	public function signinAction()
    {
        return $this->render('KryptosKryptosBundle:Default:signin.html.twig', array('location' => 'signin'));
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
					
				return $this->redirect($this->generateUrl('register_success'));
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
}
