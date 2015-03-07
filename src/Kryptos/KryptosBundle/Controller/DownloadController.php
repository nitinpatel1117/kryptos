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
    	$this->outputFile('application/pdf', 'site|download|help_prepare_file|server_path', 'site|download|help_prepare_file|filename', 'site|download|help_upload_file|extension');
    }
    
    
    public function helpUploadFileAction(Request $request)
    {
    	$this->checkPermissions();
    	$this->outputFile('application/pdf', 'site|download|help_upload_file|server_path', 'site|download|help_upload_file|filename', 'site|download|help_upload_file|extension');
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
    
    
    
    public function outputFile($mimeType, $config_server_path, $config_filename, $config_extension)
    {
    	$config = $this->get('config_manager');
    	
    	$outputTemplate  = $this->makeTemplate($config_server_path);
    	$outputFilename  = $this->makeFilename($config_filename);
    	$outputExtension = $this->makeExtension($config_extension);
    	
    	header("Content-Type: $mimeType");
    	header("Content-Disposition: attachment; filename=\"$outputFilename.$outputExtension\"");
    	header('Pragma: public');
    	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    	header('Content-Transfer-Encoding: binary');
    	header('Expires: 0');
    	header('Content-Length: ' . filesize($outputTemplate));
    	ob_clean();
    	flush();
    	
    	readfile($outputTemplate);
    	exit;
    }
    
    
    public function makeTemplate($config_server_path)
    {
    	$templateLocation = $this->get('config_manager')->get($config_server_path);
    	 
    	// get locale
    	$selectedLocale = $this->getRequest()->getLocale();
    	 
    	// make filepaths
    	$templateFallback = str_replace('{_locale}/', '', 			  $templateLocation);
    	$templateLocation = str_replace('{_locale}', $selectedLocale, $templateLocation);
    	 
    	// Determine template to show
    	$template = $templateLocation;
    	if (!(file_exists($templateLocation) && is_readable($templateLocation))) {
    		if (!(file_exists($templateFallback) && is_readable($templateFallback))) {
    			throw $this->createNotFoundException('File cound not be found.');
    		}
    	
    		$template = $templateFallback;
    	}
    	
    	return $template;
    }
    
    
    public function makeFilename($config_filename)
    {
    	$outputFilename = $this->get('config_manager')->get($config_filename);
    	
    	$parts = explode('|', $outputFilename);
    	foreach ($parts as $key => $part) {
    		$parts[$key] = $this->get('translator')->trans($part);
    	}
    	
    	// Converts [Help - 1. Prepare your file]  to  [Help - Upload your file] 
    	if (isset($parts[1])) {
    		$parts[1] = substr($parts[1], 3);
    	}
    	
    	return implode(' - ', $parts);
    }
    
    
    public function makeExtension($config_extension)
    {
    	return $this->get('config_manager')->get($config_extension);
    }
}