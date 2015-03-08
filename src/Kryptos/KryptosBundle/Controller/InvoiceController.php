<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\KryptosBundle\Controller\LocaleInterface;
use Kryptos\KryptosBundle\Controller\AccountController;
use fpdf\FPDF;


class InvoiceController extends Controller implements LocaleInterface
{
	
	protected $user;
	
    public function indexAction(Request $request, $transactionId)
    {
    	$config = $this->get('config_manager');
    	$session = $this->get('login_validator');
    	
    	// Make sure user is signed in
    	if ($config->signinRequired() && !$session->isLoginValid()) {
    		return $this->redirect($this->generateUrl('welcome'));
    	}
    	
    	$userSessionDetails = $this->get('login_validator')->getLoggedInUserDetails();
    	$userTrans = $this->get('user_manager')->getTransactionDetails($userSessionDetails['email'], '54fb02c7212ebf5e268b4567');
    	
    	if (is_null($userTrans) || !isset($userTrans['payment'][0])) {
    		// throw error - transaction not found
    	}
    	
    	$transactionData = $userTrans['payment'][0];
    	
    	$this->makeInvoice($transactionData);
    	
    	    	
    	echo "<pre>";
    	print_r($transactionData);
    	exit;
    	
    	
    	
    	
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
    			'progressFrom' 	=> $progressFrom,
    			'progressTo' 	=> $progressTo,
    		);
    		
    		$files[] = $file;
    	}

        return $this->render('KryptosKryptosBundle:FileStatus:index.html.twig', array(
        	'files' 		=> $files,
        	'downloadLink' 	=> $this->generateUrl('convert_batch_download'),
        ));
    }
    
    
    public function makeInvoice($transactionData)
    {
    	$pdf = new FPDF();
    	$pdf->AddPage();
    	$pdf->SetFont('Arial', '', 9);
    	
    	// add our address
    	$pdf->Cell(40,4,'29 Hertford Court', 0, 1);
    	$pdf->Cell(40,4,'Meadowfields', 0, 1);
    	$pdf->Cell(40,4,'Northampton', 0, 1);
    	$pdf->Cell(40,4,'Northants', 0, 1);
    	$pdf->Cell(40,4,'NN3 9TD', 0, 1);
    	
    	// add company logo on the top right
    	$imagePath = $this->get('kernel')->getRootDir().'/../web/bundles/kryptoskryptos/images/invoice/kryptos_systems_logo.png';
    	$pdf->Image($imagePath, 160, 10, 40);
    	
    	$pdf->Ln();
    	$pdf->Cell(190, 10, '*** PLEASE PRINT INVOICE OUT AND RETAIN IT FOR FUTURE REFERENCE ***', 0, 1, 'C');
    	
    	
    	
    	/*
    [BillingSurname] => Patel
    [BillingFirstnames] => Nitin
    [BillingAddress1] => asdf
    [BillingAddress2] => asdfa
    [BillingCity] => sfasdf
    [BillingPostCode] => asfa
    [BillingCountry] => GB
    	 */
    
    	
    	$pdf->Output();
    	exit;
    }
    
}