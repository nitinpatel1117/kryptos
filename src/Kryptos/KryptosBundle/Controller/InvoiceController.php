<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Kryptos\KryptosBundle\Controller\LocaleInterface;
use Kryptos\KryptosBundle\Controller\AccountController;
use fpdf\FPDF;
use Kryptos\SageBundle\Lib\CountryCodes;


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
    	try {
    		// $transactionId is passed in via the url - user may change it and hence searching for it, may cause mongo to through invalid MongoId
    		$userTrans = $this->get('user_manager')->getTransactionDetails($userSessionDetails['email'], $transactionId);
    	} catch (\Exception $e) {
    		throw $this->createNotFoundException('The Invoice does not exist');
    	}
    	
    	if (is_null($userTrans) || !isset($userTrans['payment'][0]) ||  'OK' != $userTrans['payment'][0]['status']) {
    		throw $this->createNotFoundException('The Invoice does not exist');
    	}
    	
    	$customerId = $userTrans['_id']->__toString();
    	$transactionData = $userTrans['payment'][0];
    	
    	$this->makeInvoice($customerId, $transactionData);
    	exit;
    }
    
    
    /**
     * 
     * @param string $customerId	The User's MongoId
     * @param array $td				The Transaction data. Note: readability '$td' is shorter than '$transactionData'
     */
    public function makeInvoice($customerId, $td)
    {
    	$pdf = new FPDF();
    	$pdf->AddPage();
    	$pdf->SetFont('Arial', '', 9);

    	$pdf->SetLeftMargin(20);
    	$pdf->SetRightMargin(20);

    	// add our address
    	$pdf->Cell(40, 4, '29 Hertford Court', 0, 1);
    	$pdf->Cell(40, 4, 'Meadowfields', 0, 1);
    	$pdf->Cell(40, 4, 'Northampton', 0, 1);
    	$pdf->Cell(40, 4, 'Northants', 0, 1);
    	$pdf->Cell(40, 4, 'NN3 9TD', 0, 1);
    	$pdf->Ln();
    	$pdf->Cell(40, 4, 'Company Number: 08640789', 0, 1);
    	
    	// add company logo on the top right
    	$imagePath = $this->get('kernel')->getRootDir().'/../web/bundles/kryptoskryptos/images/invoice/kryptos_systems_logo.png';
    	$pdf->Image($imagePath, 143, 10, 40);
    	
    	$pdf->Ln();
    	
    	$pdf->SetFont('Arial', 'B', 8);
    	$pdf->Cell(190, 10, '*** PLEASE PRINT INVOICE OUT AND RETAIN IT FOR FUTURE REFERENCE ***', 0, 1, 'C');
    	$pdf->SetFont('Arial', 'B', 9);
    	
    	$pdf->Ln();
    	
    	$billingStartX = $pdf->getX();
    	$billingStartY = $pdf->getY();
    	
    	$pdf->Cell(40, 4, sprintf('%s %s', $td['BillingFirstnames'], $td['BillingSurname']), 0, 1);
    	$pdf->Cell(40, 4, $td['BillingAddress1'], 0, 1);
    	$pdf->Cell(40, 4, $td['BillingAddress2'], 0, 1);
    	$pdf->Cell(40, 4, $td['BillingCity'], 0, 1);
    	$pdf->Cell(40, 4, $td['BillingPostCode'], 0, 1);
    	
    	$countryCodes = new CountryCodes();
    	$countryList = $countryCodes->getList();
    	if (isset($countryList[$td['BillingCountry']])) {
    		$country = sprintf('txt_country_%s', $td['BillingCountry']);
    		$country = $this->get('translator')->trans($country);
    		$pdf->Cell(40, 4, $country, 0, 1);
    	}
    	
    	$billingEndX = $pdf->getX();
    	$billingEndY = $pdf->getY();
    	
    	$pdf->setY($billingStartY);
    	$pdf->setX($billingStartX + 90);
    	$pdf->Cell(30, 4, 'Invoice Number: ', 0, 0);
    	$pdf->Cell(40, 4, $td['_id']->__toString(), 0, 1);
    	$pdf->setX($billingStartX + 90);
    	$pdf->Cell(30, 4, 'Payment Method: ', 0, 0);
    	$pdf->Cell(40, 4, $td['cardType'], 0, 1);
    	$pdf->setX($billingStartX + 90);
    	$pdf->Cell(30, 4, 'Customer Id: ', 0, 0);
    	$pdf->Cell(40, 4, $customerId, 0, 1);
    	$pdf->setX($billingStartX + 90);
    	$pdf->Cell(30, 4, 'Order Date: ', 0, 0);
    	$date = new \DateTime();
    	$date->setTimestamp($td['started']->sec);
    	$pdf->Cell(40, 4, $date->format('d F Y H:i'), 0, 1);

    	$pdf->setY($billingEndY);
    	$pdf->setX($billingEndX);
    	
    	$pdf->Ln();
    	$pdf->Ln();
    	$pdf->Ln();
    	
    	$pdf->SetFont('Arial', 'B', 14);
    	$pdf->Cell(190, 10, 'Items', 0, 1);
    	$pdf->SetFont('Arial', '', 9);
    	
    	
    	$header = array('Description', 'Credits', 'Line Total');
    	$data = array(
    		array(
    			$td['Description'],
    			$td['purchase']['credits'],
    			sprintf('%s %s', $td['Amount'], $td['Currency'])
    		),
    	);
    	$subtotal = sprintf('%s %s', $td['Amount'], $td['Currency']);
    	$total 	  = sprintf('%s %s', $td['Amount'], $td['Currency']);
    	
    	$this->fancyTable($pdf, $header, $data,$subtotal, $total);
    	
    	$pdf->Output();
    	exit;
    }
    
    
    function fancyTable($pdf, $header, $data, $subtotal, $total)
    {
    	// Colors, line width and bold font
    	$pdf->SetFillColor(238, 238, 238);
    	$pdf->SetDrawColor(60, 60, 60);
    	$pdf->SetLineWidth(.3);
    	$pdf->SetTextColor(30);
    	$pdf->SetFont('Arial','');
    	
    	// Header
    	$w = array(83, 50, 30);
    	for($i=0; $i < count($header); $i++) {
    		$pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
    	}
    	$pdf->Ln();

    	// Color and font restoration
    	$pdf->SetFillColor(224,235,255);
    	$pdf->SetTextColor(30);
    	$pdf->SetFont('');
    	
    	// Data
    	$fill = false;
    	foreach($data as $row)
    	{
    		$pdf->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
    		$pdf->Cell($w[1], 6, $row[1], 'LR', 0, 'C', $fill);
    		$pdf->Cell($w[2], 6, $row[2], 'LR', 0, 'R', $fill);
    		$pdf->Ln();
    		$fill = !$fill;
    	}
    	
    	// Closing line
    	$pdf->Cell(array_sum($w), 0, '', 'T');
    	
    	// add total boxes
    	$pdf->Ln();
    	$pdf->Cell($w[0]+$w[1], 6, 'Subtotal', '', 0, 'R');
    	$pdf->Cell($w[2], 6, $subtotal, 'LRB', 0, 'R');
    	$pdf->Ln();
    	$pdf->Cell($w[0]+$w[1], 6, 'Total', '', 0, 'R');
    	$pdf->Cell($w[2], 6, $total, 'LRB', 0, 'R');
    }
    
}