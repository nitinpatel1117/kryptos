<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\KryptosBundle\Form\RegistrationForm;
use Kryptos\KryptosBundle\Form\SigninForm;
use Kryptos\KryptosBundle\Form\ResetPasswordEmailForm;
use Kryptos\KryptosBundle\Form\ResetPasswordQuestionForm;
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

				if (true === $status) {
					$session->saveLogin($form->getData()->getEmail());
					return $this->redirect($this->generateUrl('welcome'));
				}
				
				if ("not_activated" === $status) {
					$form->addError(new FormError('Your account has not been activated. Please click on the activation link that was sent to the email address that you registered with.'));
				}

				if (false === $status) {
					$form->addError(new FormError('Email and password do not match'));
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
        	'captchaMessage' => 'Type the characters you see in the picture below.'
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
    		$message = "Your account has been activated";
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
        return $this->render('KryptosKryptosBundle:Default:registerSubmitted.html.twig', array(
        	'header' => 'Thankyou for your registration'
        ));
    }


    public function welcomeAction()
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');
    	if (!$config->siginDisabled() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('homepage'));
    	}

    	return $this->render('KryptosKryptosBundle:Default:welcome.html.twig', array(
    		'location' 					=> 'Welcome page',
    		'purchase_conversion_url' 	=> $this->generateUrl('purchase_conversions'),
    		'account_summary_url' 		=> $this->generateUrl('account_summary'),
    		'convert_to_sepa_url_batch' => $this->generateUrl('convert_batch'),
    		'convert_to_sepa_url_single'=> $this->generateUrl('convert_single'),
    	));
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
    		'location' => 'Reset Password',
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
    
    	$message = \Swift_Message::newInstance()
    		->setSubject('Kryptos: Password Reset')
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
    	return $this->render('KryptosKryptosBundle:Default:resetPasswordEmailSubmitted.html.twig', array(
    		'header' => 'Password Reset',
    	));
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
    			'header'  => 'Unable to reset password',
    			'message' => $message,
    		));
    	}
    	 
    	return $this->render('KryptosKryptosBundle:Default:resetPasswordQuestion.html.twig', array(
    		'form' 		=> $form->createView(),
    		'location' 	=> 'Reset Password',
    		'userId' 	=> $userId,
    		'code1' 	=> $code1,
    		'code2' 	=> $code2,
    	));
    }
    
    
    public function resetPasswordQuestionSuccessAction(Request $request)
    {
    	return $this->render('KryptosKryptosBundle:Default:resetPasswordQuestionSuccess.html.twig', array(
    		'header' => 'Password reset',
    		'message' => 'Your password has been successfully reset. Please use your new password from now on.',
    	));
    }
}
