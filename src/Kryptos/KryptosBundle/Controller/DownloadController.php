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

class DownloadController extends Controller
{
    public function helpPrepareFileAction(Request $request)
    {
    	$this->checkPermissions();
    	$this->outputFile('application/pdf', 'site|download|help_prepare_file|server_path', 'site|download|help_prepare_file|filename');
    }
    
    
    public function helpUploadFileAction(Request $request)
    {
    	$this->checkPermissions();
    	$this->outputFile('application/pdf', 'site|download|help_upload_file|server_path', 'site|download|help_upload_file|filename');
    }
    
    
    public function checkPermissions()
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');
    	
    	/*
    	 * // bypass the checking of whether this user is signed in. we want users to be able to download this file without being signed in.
    	if ($config->signinRequired() && !$session->isLoginValid()) {
    	return $this->redirect($this->generateUrl('welcome'));
    	}
    	*/
    }
    
    
    
    public function outputFile($mimeType, $config_server_path, $config_filename)
    {
    	$config = $this->get('config_manager');
    	
    	$templateLocation = $config->get($config_server_path);
    	if (!(file_exists($templateLocation) && is_readable($templateLocation))) {
    		throw $this->createNotFoundException('File cound not be found.');
    	}
    	
    	$outputFilename = $config->get($config_filename);
    	
    	header("Content-Type: $mimeType");
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
}