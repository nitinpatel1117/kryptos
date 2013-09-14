<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\KryptosBundle\Controller\LocaleInterface;
use Kryptos\KryptosBundle\Form\RegistrationForm;
use Kryptos\KryptosBundle\Form\SigninForm;
use Kryptos\KryptosBundle\Form\ResetPasswordEmailForm;
use Kryptos\KryptosBundle\Form\ResetPasswordQuestionForm;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\FormError;

class DefaultController extends Controller implements LocaleInterface
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
    	} else {
    		return $this->redirect($this->generateUrl('signin'));
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
				list($status, $user) = $userManager->checkSignin($form->getData());

				if (true === $status) {
					$session->saveLogin($form->getData()->getEmail(), $user->getFirstName(), $user->getId());
					return $this->redirect($this->generateUrl('welcome'));
				}
				
				if ("not_activated" === $status) {
					$form->addError(new FormError('msg_title_account_not_activated|msg_desc_account_not_activated'));
				}

				if (false === $status) {
					$form->addError(new FormError('msg_title_incorrect_email_pass_combi|msg_desc_incorrect_email_pass_combi'));
				}
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
    	if ($this->get('login_validator')->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}

		$form = $this->createForm(new RegistrationForm());

		if ($request->isMethod('POST')) {
			$form->bind($request);

			if ($form->isValid()) {
				$userManager = $this->get('user_manager');
				$user = $userManager->createUserFrom($form->getData());
				$userManager->register($user);

				// TODO: get kryptos company name from config
		    	$this->sendActivationEmail(
	    			$user->getEmail(),
	    			sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
	    			'Kryptos',
	    			$user->getActivation()->getLink(),
		    		$request->isSecure()
	    		);

				return $this->redirect($this->generateUrl('register_submitted'));
			}
		}

        return $this->render('KryptosKryptosBundle:Default:register.html.twig', array(
        	'form' => $form->createView(),
        	'location' => 'register',
        	'captchaMessage' => 'txt_captcha_type_the'
        ));
    }


    public function sendActivationEmail($recipient, $recipientName, $company, $activationUrl, $isSecure)
    {
    	$protocol = 'https';
    	if (!$isSecure) {
    		$protocol = 'http';
    	}
    	
    	$activationUrl = sprintf('%s://%s%s/%s',
    			$protocol,
    			$this->get('config_manager')->get('site|url'), 
    			$this->generateUrl('register_activate'), 
    			$activationUrl
    	);
    		
    	$message = \Swift_Message::newInstance()
	    	->setSubject('Kryptos: Account activation')
	    	->setFrom(array(
	    			$this->get('config_manager')->get('site|email_activate_from') => $this->get('config_manager')->get('site|email_activate_fromname')
	    			))
	    	->setTo($recipient)
	    	->setBody(
	    		$this->renderView('KryptosKryptosBundle:Emails:accountActivation.txt.twig', array(
    					'name' => $recipientName,
    					'company' => $this->get('config_manager')->get('site|name'),
    					'activationUrl' => $activationUrl
	    			)
	    		)
	    	);
    	$this->get('mailer')->send($message);
    }
    
    
    
    public function registerActivateAction(Request $request, $userId, $code1, $code2)
    {
    	$userManager = $this->get('user_manager');
    	$show_signin_link = false;
    	
    	try {
    		$userManager->activateAccount($userId, $code1, $code2);
    		$message = "txt_title_account_activated";
    		$show_signin_link = true;
    	} catch (\Exception $e) {
    		$message = $e->getMessage();
    	}

    	
    	return $this->render('KryptosKryptosBundle:Default:registerSuccess.html.twig', array(
    			'header' 			=> $message,
    			'link_url' 			=> $this->generateUrl('signin'),
    			'link_text' 		=> 'signin',
    			'show_signin_link' 	=> $show_signin_link
    	));
    }


	public function registerSubmittedAction(Request $request)
    {
        return $this->render('KryptosKryptosBundle:Default:registerSubmitted.html.twig', array());
    }


    public function welcomeAction()
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');
    	if (!$config->siginDisabled() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('homepage'));
    	}
    	
    	$userArray = $session->getLoggedInUserDetails();
    	$result = $this->get('file_manager')->getCompletedFilesByUser($userArray['id']);
    	
    	$files = array();
    	$date = new \DateTime();
    	
    	while ($result->hasNext()) {
    		$item = $result->getNext();
    	
    		$date->setTimestamp($item['upload_time']->sec);
    		$file = array(
    			'datetime' 		=> $date->format('d/m/Y'),
    			'conversions' 	=> isset($item['stats']['valid']) 	? $item['stats']['valid'] : 0,
    			'invalid' 		=> isset($item['stats']['invalid']) ? $item['stats']['invalid'] : 0,
    		);
    		$files[] = $file;
    	}
    	
    	if (empty($files)) {
    		$files[] = array('datetime' => '', 'conversions' => 0, 'invalid' => 0);
    	}

    	return $this->render('KryptosKryptosBundle:Default:welcome.html.twig', array(
    		'purchase_conversion_url' 	=> $this->generateUrl('purchase_conversions'),
    		'account_summary_url' 		=> $this->generateUrl('account_summary'),
    		'convert_to_sepa_url_batch' => $this->generateUrl('convert_batch'),
    		'convert_to_sepa_url_single'=> $this->generateUrl('convert_single'),
    		'files' 					=> $files,
    	));
    }


    public function authenicationBarAction($route)
    {
    	$config = $this->get('config_manager');
    	
    	$signin   = false;
    	$register = false;
    	$logout   = false;
    	$allDisabled = false;
    	$username = '';
    	$conversions = $config->get('credits');

    	if (!$config->siginDisabled()) {
    		$session = $this->get('login_validator');
    		if ($session->isLoginValid()) {
    			$userArray = $session->getLoggedInUserDetails();
    			$username = ucfirst($userArray['name']);
    			$conversions = $this->get('user_manager')->getUserCredits($userArray['email']);
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
        	'username'		=> $username,
        	'conversions'	=> $conversions,
        	'current_route' => $route,
        ));
    }
    
    
    public function resetPasswordEmailAction(Request $request)
    {
    	if ($this->get('login_validator')->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	
    	$form = $this->createForm(new ResetPasswordEmailForm());
    	
    	if ($request->isMethod('POST')) {
    		$form->bind($request);
    	
    		if ($form->isValid()) {
    			$userManager = $this->get('user_manager');
    			$formPosted = $request->request->get('ResetPasswordEmailForm');
    			
    			$user = $userManager->getUserByEmail($formPosted['email'], array('_id', 'firstName', 'lastName', 'email'));
    			if (!is_null($user)) {
    				// user was found
    				
    				$passwordResetDetails = $userManager->makePasswordResetDetails($user);
	    	
	    			// TODO: get kryptos company name from config
	    			$this->sendPasswordResetEmail(
	    				$formPosted['email'],
	    				sprintf('%s %s', $user['firstName'], $user['lastName']),
	    				'Kryptos',
	    				$passwordResetDetails['link'],
	    				$request->isSecure()
	    			);
    			}
    	
    			// regardless if user exists or not, we always redirect to success page - to hide the fact that an email address is present in our system
    			return $this->redirect($this->generateUrl('password_reset_email_submitted'));
    		}
    	}
    	
    	return $this->render('KryptosKryptosBundle:Default:resetPasswordEmail.html.twig', array(
    		'form' => $form->createView(),
    	));
    }
    
    
    public function sendPasswordResetEmail($recipient, $recipientName, $company, $passwordResetUrl, $isSecure)
    {
    	$protocol = 'https';
    	if (!$isSecure) {
    		$protocol = 'http';
    	}
    	 
    	$passwordResetUrl = sprintf('%s://%s%s/%s',
    		$protocol,
    		$this->get('config_manager')->get('site|url'),
    		$this->generateUrl('password_reset_question'),
    		$passwordResetUrl
    	);
    
    	$subject = $this->get('translator')->trans('email_subject_password_reset');
    	
    	$message = \Swift_Message::newInstance()
    		->setSubject($subject)
    		->setFrom(array(
    			$this->get('config_manager')->get('site|password_reset_from') => $this->get('config_manager')->get('site|password_reset_fromname')
    		))
    		->setTo($recipient)
    		->setBody(
    			$this->renderView('KryptosKryptosBundle:Emails:passwordReset.txt.twig', array(
    				'name' => $recipientName,
    				'company' => $this->get('config_manager')->get('site|name'),
    				'passwordResetUrl' => $passwordResetUrl
    			)
    		)
    	);
    	$this->get('mailer')->send($message);
    }
    
    public function resetPasswordEmailSubmittedAction(Request $request)
    {
    	return $this->render('KryptosKryptosBundle:Default:resetPasswordEmailSubmitted.html.twig', array());
    }
    
    
    public function resetPasswordQuestionAction(Request $request, $userId, $code1, $code2)
    {
    	$userManager = $this->get('user_manager');
    	
    	try {
    		list ($status, $user) = $userManager->checkPasswordResetAccount($userId, $code1, $code2);
    		
    		if (true == $status) {
    			// we ok to proceed
    		    	
		    	$form = $this->createForm(new ResetPasswordQuestionForm());
		    	 
		    	if ($request->isMethod('POST')) {
		    		$form->bind($request);
		    		 
		    		if ($form->isValid()) {
		    			$formPosted = $request->request->get('ResetPasswordQuestionForm');
		    			
		    			if (!isset($formPosted['password']['password'])) {
		    				throw new \Exception('Password could not be reset');
		    			}
		    			
		    			$userManager->doPasswordReset($user, $formPosted['password']['password']);
		    			
		    			// if worked redirect to new page here
		    			return $this->redirect($this->generateUrl('password_reset_question_success'));
		    		}
		    	}
		    	
    		}
    	}
    	catch (\Exception $e) {
    		$message = $e->getMessage();
    		
    		return $this->render('KryptosKryptosBundle:Default:resetPasswordQuestionFail.html.twig', array(
    			'message' => $message,
    		));
    	}
    	 
    	return $this->render('KryptosKryptosBundle:Default:resetPasswordQuestion.html.twig', array(
    		'form' 		=> $form->createView(),
    		'userId' 	=> $userId,
    		'code1' 	=> $code1,
    		'code2' 	=> $code2,
    	));
    }
    
    
    public function resetPasswordQuestionSuccessAction(Request $request)
    {
    	return $this->render('KryptosKryptosBundle:Default:resetPasswordQuestionSuccess.html.twig', array());
    }
}
