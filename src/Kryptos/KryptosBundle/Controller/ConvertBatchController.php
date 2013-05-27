<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    	
    	// if the user pressed cancel on attention required - process this
    	$this->cancelPurchaseRequired($request);

    	$confirmUpload = $this->getConfirmUploadMessage();
    	
    	$attentionUpload = $this->getAttentionUploadMessage();
    	$attentionOnFile = '';
    	if ('' != $attentionUpload) {
    		$sessionStore = $this->get('session');
    		$sessionId = $sessionStore->getId();
    		$fileData = $sessionStore->get($sessionId.'_purchasedRequired');
    		$attentionOnFile = $fileData['filename'];
    	}
    	
    	
    	$form = $this->createForm(new ConvertBatchForm());
    	$credits = $this->getAllowedConversions();
    	
    	if ($credits < 1) {
    		$userNote = 'You no not have any conversions available. You will need to purchase conversions in order to proceed. Please make sure that you purchase sufficient conversions for your batch file.';
    	} else {
    		$userNote = sprintf('You have %s conversions available. If you upload a file with more than %s entries, only the first %s entries will be read and processed.', $credits, $credits, $credits);
    	}
    	
    	
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
	    				throw new \Exception('Invalid file |The first line in the upload file is not valid. Please make sure that the first line contains column headers and that these column headers are unchanged from the values supplied in the original template file.', 101) ;
	    			}
	    			
	    			/*
	    			if ($this->conversionsRestricted()) {
	    				$lines = $this->getLineCount($tmp_path.$newFilename);
	    				$credits = $this->getAllowedConversions();
	    				if ($lines > $credits) {
	    					throw new \Exception(sprintf('The uploaded file contains %s entries. You only have %s conversions available.', $lines, $credits), 102);
	    				}
	    			}
	    			*/
	    			
	    			$linesInFile = $this->getLineCount($tmp_path.$newFilename);
	    			$purchaseRequired = false;
	    			
	    			// check that the user had some credits
	    			if ($this->conversionsRestricted()) {
	    				$credits = $this->getAllowedConversions();
	    				if ($credits < $linesInFile) {
	    					$purchaseRequired = true;
	    					// throw new \Exception('You no not have any conversions available. You will need to purchase conversions in order to proceed.', 101);
	    				}
	    			}

	    			
	    			$fileData = array(
	    				'originalFilename' 	=> $originalFilename,
	    				'filename' 			=> $newFilename,
	    				'sessionId' 		=> is_null($this->getUserId()) ? $this->getSessionId() : null,			# we want to store only the sessionId or the userId
	    				'userId' 			=> $this->getUserId(),
	    				'approxLines'		=> $linesInFile,														# store approx line count so that we can, calculate the aprox time for it to have processed
	    			);
	    			
	    			$additionalData = array(
	    				'credits'					=> $credits,
	    				'conversionsRestricted'		=> $this->conversionsRestricted(),
	    				'newFilename'				=> $newFilename,
	    				'purchaseRequired' 			=> $purchaseRequired,
	    			);
	    			
	    			/*
	    			// batch insert file in 500 intervals
	    			$batchInsertFile = $this->get('batch_insert_file');
	    			$batchInsertFile->process($file, $fileData);
	    			*/
	    			
	    			// upload the batch file
	    			$batchUploadFile = $this->get('batch_upload_file');
	    			$batchUploadFile->process($file, $fileData, $additionalData);
	    			
	    			
	    			if (true == $purchaseRequired) {
	    				$this->get('session')->set(sprintf('%s_purchasedRequired', $this->getSessionId()), $fileData);
	    				$this->get('session')->getFlashBag()->add('attentionUpload', 'attention is required');
	    			}
	    			else {
	    				$this->get('session')->getFlashBag()->add('confirmUpload', 'File has been succesfully received. Processing of this file will start shortly');
	    			}
	    			
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
        	'form' 						=> $form->createView(),
        	'location' 					=> 'Batch Convert',
        	'btn_submit' 				=> 'Upload',
        	'confirmUpload' 			=> $confirmUpload,
        	'credits' 					=> $credits,
        	'conversionsRestricted' 	=> $this->conversionsRestricted(),
        	'userNote' 					=> $userNote,
        	'attentionOnFile' 			=> $attentionOnFile,
        ));
    }
    
    
    public function templateAction(Request $request)
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');

    	/*
    	 * // bypass the checking of whether this user is signed in. we want users to be able to download this file without being signed in. 
    	if ($config->signinRequired() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	*/

    	$templateLocation = $config->get('site|csv_template_server_path');
    	 
    	if (!(file_exists($templateLocation) && is_readable($templateLocation))) {
    		// file does not exist or could not be read
    		throw $this->createNotFoundException('Template file cound not be found.');
    	}
    	 

    	$outputFilename = $config->get('site|csv_template_filename');
    	
    	header('Content-Type: application/csv');
    	header("Content-Disposition: attachment; filename=\"$outputFilename\"");
    	header('Pragma: public');
    	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    	header('Content-Transfer-Encoding: binary');
    	header('Expires: 0');
    	header('Content-Length: ' . filesize($templateLocation));
    	ob_clean();
    	flush();
    	
    	readfile($templateLocation);
    	exit;
    }
    
    
    
    public function downloadAction(Request $request, $fileId)
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');
    	$fileManager = $this->get('file_manager');
    	
    	if ($config->signinRequired() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	
    	// retrieve user if we are dealing with signin enabled
    	$this->setupUser();
    	
    	// get data about this fileId from DB
    	$fileData = $fileManager->getFileById($fileId);
    	
    	// entry for this fileId was not found in DB
    	if (is_null($fileData)) {
    		// return 404. fileId is not valid
    		die('4');
    	}
    	
    	if (!$this->canUserAccessFile($fileData)) {
    		// return 404. not allowed to access the file. either wrong user or sessionid
    		die('1');
    	}
    	
    	if ('complete' != $fileData['status']) {
    		// return 404. file is not ready for download. it is not converted yet
    		die('2');
    	}
    	
    	$filename = $fileData['filename'];
    	$processedLocation 	= sprintf('%s%s%s', $config->get('site|tmp_path'), $config->get('batch_convert|processed_location'), $filename);
    	
    	if (!(file_exists($processedLocation) && is_readable($processedLocation))) {
    		// file does not exist or could not be read
    		die('3');
    	}
    	
    	/*
    	echo "<pre>";
    	print_r($fileData);
    	var_dump($filename, $processedLocation);
    	print_r(get_class_methods($response));
    	exit;
    	*/
    	
    	
    	header('Content-Type: application/csv');
    	$outputFilename = $fileData['originalFilename'];
    	header("Content-Disposition: attachment; filename=\"$outputFilename\"");
    	header('Pragma: public');
    	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

    	header('Content-Transfer-Encoding: binary');
    	header('Expires: 0');
    	header('Content-Length: ' . filesize($processedLocation));
    	ob_clean();
    	flush();
    	
    	readfile($processedLocation);
    	exit;
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
    
    
    public function getAttentionUploadMessage()
    {
    	$msg = '';
    	 
    	$attentionUpload = $this->get('session')->getFlashBag()->get('attentionUpload');
    	if (is_array($attentionUpload) && count($attentionUpload) > 0 ) {
    		$msg = array_shift($attentionUpload);
    	}
    	 
    	return $msg;
    }
    
    public function cancelPurchaseRequired($request)
    {
    	if ($request->query->has('f')) {
    		$filename = $request->query->get('f');
    		$fileDate = $this->get('file_manager')->getFileByFilename($filename);
    		$fileDate['status'] = 'insufficient_funds';
    		$this->get('file_manager')->save($fileDate);
    		
    		$this->get('session')->remove(sprintf('%s_purchasedRequired', $this->getSessionId()));

    	}
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
    			if (7 == count($data) && 'Country ISO' == $data[0]){
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
    	
    	if (!isset($this->user)) {
    		$this->setupUser();
    	}
    	
    	if (isset($this->user['credits'])) {
    		$allowedConversions = $this->user['credits'];
    	}
    	
    	return $allowedConversions;
    }
    
    
    
    
    
    public function canUserAccessFile($fileData)
    {
    	$canAccess = false;
    	
    	// by default use sessionId as prefix. i.e. in case site is running with signin disabled
    	$filePrefix = $this->getSessionId();
    	$type = 'session';
    	
    	// get user id, if user is setup
    	if (!is_null($this->getUserId())) {
    		$filePrefix = $this->getUserId();
    		$type = 'user';
    	}
    	
    	if (strpos($fileData['filename'], $filePrefix.'-') !== false)
    	{
    		if ('session' == $type && $filePrefix == $fileData['sessionId']) {
    			$canAccess = true;
    		}
    		else if ('user' == $type && $filePrefix == $fileData['userId']) {
    			$canAccess = true;
    		} 
    	}

    	return $canAccess;
    }
}