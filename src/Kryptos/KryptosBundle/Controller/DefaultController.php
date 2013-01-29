<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\KryptosBundle\Entity\Register;
use Kryptos\KryptosBundle\Model\User;
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
    	// create a task and give it some dummy data for this example
        $register = new Register();
        /*
        $register->setFirstName('First Name');
        $register->setLastName('Last Name');
        $register->setJobTitle('Jobtitle');
        $register->setCompany('Company');
        $register->setLocation('Location');
        $register->setEmail('Email');
        */


        $form = $this->createFormBuilder($register)
					 ->add('firstName', 'text')
					 ->add('lastName', 'text', array("required" => false))
					 ->add('jobTitle', 'text')
					 ->add('company', 'text', array("required" => false))
					 ->add('location', 'text')
					 ->add('email', 'text')
					 ->getForm();
	
					 
		if ($request->isMethod('POST')) {
			$form->bind($request);

			if ($form->isValid()) {
				// perform some action, such as saving the task to the database

				#$userManager = new UserManager();
				
				$userManager = $this->get('user_manager');
				$user = $userManager->createUserFrom($form->getData());
				
				var_dump($user);
				exit;
				
				var_dump($form->getData());
				
				echo "<BR><BR>";
				
				var_dump(get_class_methods($form));
				exit;
				
				echo "is valid <BR>";
				
				$user = new User();
				
				
				
				##return $this->redirect($this->generateUrl('task_success'));
			}
		}
        
		
        return $this->render('KryptosKryptosBundle:Default:register.html.twig', array(
        	'form' => $form->createView(),
        	'location' => 'register'
        ));
    }
}
