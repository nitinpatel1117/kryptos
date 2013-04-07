<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Kryptos\KryptosBundle\Form\ConvertBatchForm;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Kryptos\KryptosBundle\Lib\BatchInsertFile;

class ConvertBatchController extends Controller
{
	
	protected $user;
	
    public function indexAction(Request $request)
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');

    	if ($config->signinRequired() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}

    	$confirmUpload = $this->getConfirmUploadMessage();
    	
    	$form = $this->createForm(new ConvertBatchForm());
    	try {
	    	if ($request->isMethod('POST')) {
	    		$form->bind($request);
	    		if ($form->isValid()) {
	    			// retrieve user if we are dealing with signin enabled
	    			$this->setupUser();
	    			
	    			$file = $form['attachment']->getData();
	    			
	    			$tmp_path = $config->get('site|tmp_path');
	    			$originalFilename = $file->getClientOriginalName();
	
	    			$newFilename = $this->getNewFilename();
	    			
	    			$file->move($tmp_path, $newFilename);
	    			
	    			$file = new UploadedFile(sprintf('%s/%s' , $tmp_path, $newFilename), $newFilename);
	    			
	    			if (!$this->isFirstLineValid($file)) {
	    				throw new \Exception('The first line in the upload file is not valid. Please make sure that the first line contains column headers and that these column headers are unchanged from the values supplied in the original template file.', 101) ;
	    			}
	    			
	    			if ($this->conversionsRestricted()) {
	    				$lines = $this->getLineCount($tmp_path.$newFilename);
	    				$credits = $this->getAllowedConversions();
	    				if ($lines > $credits) {
	    					throw new \Exception(sprintf('The uploaded file contains %s entries. You only have %s conversions available.', $lines, $credits), 102);
	    				}
	    			}
	    			
	    			
	    			$fileData = array(
	    				'originalFilename' 	=> $originalFilename,
	    				'filename' 			=> $newFilename,
	    				'sessionId' 		=> is_null($this->getUserId()) ? $this->getSessionId() : null,			# we want to store only the sessionId or the userId
	    				'userId' 			=> $this->getUserId(),
	    			);
	    			
	    			// batch insert file in 500 intervals
	    			$batchInsertFile = $this->get('batch_insert_file');
	    			$batchInsertFile->process($file, $fileData);
	    			
	    			$this->get('session')->getFlashBag()->add('confirmUpload', 'File has been succesfully received. Processing of this file will start shortly');
	    			return $this->redirect($this->generateUrl('convert_batch'));
	    		}
	    	}
    	}
    	catch (\Exception $e) {
    		switch ($e->getCode()){
    			case 102:
    				$form->addError(new FormError($e->getMessage()));
    				break;
    				
    			case 101:
    			default:
    				$form->addError(new FormError($e->getMessage()));
    				break;
    				
    		}
    	}

    	
        return $this->render('KryptosKryptosBundle:ConvertBatch:index.html.twig', array(
        	'form' 			=> $form->createView(),
        	'location' 		=> 'Batch Convert',
        	'btn_submit' 	=> 'Upload',
        	'confirmUpload' => $confirmUpload,
        ));
    }
    
    
    public function getConfirmUploadMessage()
    {
    	$msg = '';
    	
    	$confirmUpload = $this->get('session')->getFlashBag()->get('confirmUpload');
    	if (is_array($confirmUpload) && count($confirmUpload) > 0 ) {
    		$msg = array_shift($confirmUpload);
    	}
    	
    	return $msg;
    }
    
    
    public function setupUser()
    {
    	$config = $this->get('config_manager');
    	if ($config->signinRequired()) {
    		$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    		$this->user = $this->get('user_manager')->getUserByEmail($userSessionDetails['email']);
    	}
    }
    
    
    public function getNewFilename()
    {
    	// by default use sessionId as prefix. i.e. in case site is running with signin disabled
    	$filePrefix = $this->getSessionId();
    	
    	// get user id, if user is setup
    	if (!is_null($this->getUserId())) {
    		$filePrefix = $this->getUserId();
    	}
    	 
    	// make new filename
    	$mongoId = new \MongoId();
    	return sprintf('%s-%s', $filePrefix, $mongoId->__toString());
    }
    
    
    
    public function getSessionId()
    {
    	return $this->get('session')->getId();
    }
    
    
    public function getUserId()
    {
    	// get user id, if user is setup
    	if (isset($this->user)) {
    		return $this->user['_id']->__toString();
    	}
    	return null;
    }
    
    
    public function getLineCount($filePath)
    {	
    	// Get number of lines	- this actually counts the number of new lines in the file
    	$lines = intval(exec('wc -l ' . $filePath));
    	
    	/* This does not work very well
    	if ($this->doesLastLineContainNewLine($filePath)) {
    		$lines--;
    	}
    	*/
    	
    	// ignore the first line, as this will be the column headers
    	return $lines;
    }
    

    /**
     * Function checks if the lsat line in the supplied file contains a new line character
     * 
     * @param string $filePath
     * @return boolean
     */
    public function doesLastLineContainNewLine($filePath)
    {
    	$status = false;

    	$lastLine = exec("tail -1 $filePath");
    	$newLineChars = array("\n", "\r\n", "\r");
    	
    	foreach($newLineChars as $newLineChar) {
    		if (false !== strpos($lastLine, $newLineChar)) {
    			$status = true;
    		}
    	}
    	
    	return $status;
    }
    
    
    /**
     * first line of csv should contain the column headers
     * confirm that this is present
     */
    public function isFirstLineValid($file)
    {
    	$valid = false;
    	
    	if ($file->isReadable()) {
    		$splFileObject = $file->openFile('r');
    		$data = $splFileObject->fgetcsv();
    		if (is_array($data)) {
    			if (8 == count($data) && 'Country ISO' == $data[0]){
    				$valid = true;
    			}
    		}
    	}

    	return $valid;
    }
    
    
    /**
     * If user signin is enabled, then the number of conversions are restricted
     */
    public function conversionsRestricted()
    {
    	return $this->get('config_manager')->signinRequired();
    }
    
    
    /**
     * Gets the number of credits available for the user, or 0 if none available 
     * @return number
     */
    public function getAllowedConversions()
    {
    	$allowedConversions = 0;
    	if (isset($this->user['credits'])) {
    		$allowedConversions = $this->user['credits'];
    	}
    	
    	return $allowedConversions;
    }
}