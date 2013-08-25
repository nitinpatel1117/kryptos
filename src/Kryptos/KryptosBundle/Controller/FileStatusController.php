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
    		list($progressFrom, $progressTo) = $this->getEstimatedCompletionTime($item, $date);
    		
    		$file = array(
    			'id' 			=> $item['_id']->__toString(),
    			'datetime' 		=> $date->format('d/m/Y H:i:s'),
    			'filename' 		=> $item['originalFilename'],
    			'status' 		=> $this->makeStatus($item),
    			'stats' 		=> isset($item['stats']) ? $item['stats'] : null,
    			'downloadable'	=> ('complete' == $item['status']) ? true : false,
    			'progressFrom' => $progressFrom,
    			'progressTo' => $progressTo,
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
    
    
    public function makeStatus(array $item) 
    {
    	$status = null;
    	
    	switch($item['status']) {
    		case 'pending':
    			$status = 'Pending';
    			break;
    		
    		case 'complete':
    			$status = 'Complete';
    			break;
    		
    		case 'payment_failed':
    			$status = 'Payment Failed';
    			break;
    				
    		case 'awaiting_payment':
    		case 'insufficient_funds':
    			$status = 'Insufficient Funds';
    			break;
    	}
    	
    	return $status;
    }
    
    
    public function getEstimatedCompletionTime($item, $date)
    {
    	$estimate = null;
    	
    	$progressFrom = null;
    	$progressTo = null;
    	
    	if ('pending' == $item['status'])
    	{
    		// number of lines divided by 100,000. Plus 1 minute, so that zeros are 1's. value in minutes
    		$timeRequiredForFile = (int) (($item['approxLines']/60000) + 1);
    		
    		// get number of other pending files and their counts
    		// add to time required for $timeRequiredForFile
    		
    		$nowDate = new \DateTime('now');
    		
    		$diff =  $nowDate->diff($date);
    		$timeSinceUpload = (int) $diff->format('%i');
    		
    		$timeToGo = $timeRequiredForFile - $timeSinceUpload;
    		
    		#var_dump($timeToGo, $timeRequiredForFile, $timeSinceUpload);
    		#echo "<br>";
    		
    		if ($timeToGo <= 0) {
    			// something has gone wrong. either File is locked by another process or there is more load on the server meaning that the file is queued
    			
    			// get lock time from config - in seconds
    			$locktime = (int) $this->get('config_manager')->get('batch_convert|locktime');
    			$locktime = ceil($locktime / 60);

    			
    			$timeRequiredForFile += $locktime;
    			$timeToGo = $timeRequiredForFile - $timeSinceUpload;
    		}
    		
    		#var_dump($timeToGo, $timeRequiredForFile, $timeSinceUpload);
    		
    		if ($timeToGo < 0) {
    			// set to pending - somethings is still wrong - set $timeRequiredForFile to 15 and $timeToGo to 15.
    			$timeRequiredForFile = 15;
    			$timeToGo = 15;
    			$progressTo = 'override';
    		}
    	
    		// $timeRequiredForFile - holds the full amount of time requred to process this file - in effect the 100% marker for the progress bar
    		// $timeToGo 			- holds the remaining amount of time to process this file 	 - in effect ($timeRequiredForFile - $timeToGo) gives the amount completed for the progress bar
    		
    		$completed = $timeRequiredForFile - $timeToGo;    		
    		$progressFrom = (int) ($completed / $timeRequiredForFile * 100);
    		if ('override' == $progressTo) {
    			$progressTo = $progressFrom;
    		}
    		else {
    			$progressTo = (int) (($completed+1) / $timeRequiredForFile * 100);
    		}
    	}
    	
    	return array($progressFrom, $progressTo);
    }
}