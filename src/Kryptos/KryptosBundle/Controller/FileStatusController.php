<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class FileStatusController extends Controller
{
	
	protected $user;
	
    public function indexAction(Request $request)
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');

    	/*
    	// add to the ajax call
    	if ($config->signinRequired() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	*/

    	// retrieve user if we are dealing with signin enabled
    	if ($config->signinRequired()) {
    		$this->setupUser();
    		$userId = $this->getUserId();
    		$result = $this->get('file_manager')->getFilesByUser($userId);
    	}
    	else {
    		$sessionId = $this->get('session')->getId();
    		$result = $this->get('file_manager')->getFilesBySession($sessionId);
    	}

    	
    	$files = array();
    	$date = new \DateTime();
    	
    	while ($result->hasNext()) {
    		$item = $result->getNext();

    		$date->setTimestamp($item['upload_time']->sec);
    		$file = array(
    			'id' 			=> $item['_id']->__toString(),
    			'datetime' 		=> $date->format('d/m/Y H:i:s'),
    			'filename' 		=> $item['originalFilename'],
    			'status' 		=> $item['status'],
    			'downloadable'	=> ('complete' == $item['status']) ? true : false,
    		);
    		
    		$files[] = $file;
    	}

        return $this->render('KryptosKryptosBundle:FileStatus:index.html.twig', array(
        	'location' 		=> 'File status',
        	'files' 		=> $files,
        	'downloadLink' 	=> $this->generateUrl('convert_batch_download'),
        ));
    }
    
    
    
    public function setupUser()
    {
    	$config = $this->get('config_manager');
    	if ($config->signinRequired()) {
    		$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    		$this->user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email']);
    	}
    }
    
    
    public function getUserId()
    {
    	// get user id, if user is setup
    	if (isset($this->user)) {
    		return $this->user['_id']->__toString();
    	}
    	return null;
    }
}