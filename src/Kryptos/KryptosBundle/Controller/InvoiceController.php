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
     * @param array $data			The Transaction data.
     */
    public function makeInvoice($customerId, $data)
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
    	$pdf->Cell(40, 4, $this->trans('invoice_company_number').': 08640789', 0, 1);
    	
    	// add company logo on the top right
    	$imagePath = $this->get('kernel')->getRootDir().'/../web/bundles/kryptoskryptos/images/invoice/kryptos_systems_logo.png';
    	$pdf->Image($imagePath, 143, 10, 40);
    	
    	$pdf->Ln();
    	
    	$pdf->SetFont('Arial', 'B', 8);
    	//$pdf->Cell(190, 10, '*** PLEASE PRINT INVOICE OUT AND RETAIN IT FOR FUTURE REFERENCE ***', 0, 1, 'C');
    	$pdf->SetFont('Arial', 'B', 9);
    	
    	$pdf->Ln();
    	
    	$billingStartX = $pdf->getX();
    	$billingStartY = $pdf->getY();
    	
    	$pdf->Cell(40, 4, sprintf('%s %s', $data['BillingFirstnames'], $data['BillingSurname']), 0, 1);
    	$pdf->Cell(40, 4, $data['BillingAddress1'], 0, 1);
    	$pdf->Cell(40, 4, $data['BillingAddress2'], 0, 1);
    	$pdf->Cell(40, 4, $data['BillingCity'], 0, 1);
    	$pdf->Cell(40, 4, $data['BillingPostCode'], 0, 1);
    	
    	$countryCodes = new CountryCodes();
    	$countryList = $countryCodes->getList();
    	if (isset($countryList[$data['BillingCountry']])) {
    		$country = sprintf('txt_country_%s', $data['BillingCountry']);
    		$country = $this->trans($country);
    		$pdf->Cell(40, 4, $country, 0, 1);
    	}
    	
    	$billingEndX = $pdf->getX();
    	$billingEndY = $pdf->getY();
    	
    	$pdf->setY($billingStartY);
    	$pdf->setX($billingStartX + 85);
    	$pdf->Cell(35, 4, $this->trans('invoice_invoice_number').': ', 0, 0);
    	$pdf->Cell(40, 4, $data['_id']->__toString(), 0, 1);
    	$pdf->setX($billingStartX + 85);
    	$pdf->Cell(35, 4, $this->trans('invoice_payment_method').': ', 0, 0);
    	$pdf->Cell(40, 4, $data['cardType'], 0, 1);
    	$pdf->setX($billingStartX + 85);
    	$pdf->Cell(35, 4, $this->trans('invoice_customer_id').': ', 0, 0);
    	$pdf->Cell(40, 4, $customerId, 0, 1);
    	$pdf->setX($billingStartX + 85);
    	$pdf->Cell(35, 4, $this->trans('invoice_order_date').': ', 0, 0);
    	$date = new \DateTime();
    	$date->setTimestamp($data['started']->sec);
    	$pdf->Cell(40, 4, strftime('%d %B %Y %H:%M', $date->getTimestamp()), 0, 1);

    	$pdf->setY($billingEndY);
    	$pdf->setX($billingEndX);
    	
    	$pdf->Ln();
    	$pdf->Ln();
    	$pdf->Ln();
    	
    	$pdf->SetFont('Arial', 'B', 14);
    	$pdf->Cell(190, 10, $this->trans('invoice_items'), 0, 1);
    	$pdf->SetFont('Arial', '', 9);
    	
    	
    	$header = array($this->trans('invoice_description'), $this->trans('invoice_credits'), $this->trans('invoice_line_total'));
    	$tableData = array(
    		array(
    			$data['Description'],
    			$data['purchase']['credits'],
    			sprintf('%s %s', number_format($data['Amount'], 2), $data['Currency'])
    		),
    	);
    	$subtotal = sprintf('%s %s', number_format($data['Amount'], 2), $data['Currency']);
    	$total 	  = sprintf('%s %s', number_format($data['Amount'], 2), $data['Currency']);
    	
    	$this->fancyTable($pdf, $header, $tableData, $subtotal, $total);
    	
    	$pdf->Output();
    	exit;
    }
    
    
    public function fancyTable($pdf, $header, $data, $subtotal, $total)
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
    	$pdf->Cell($w[0]+$w[1], 6, $this->trans('invoice_subtotal'), '', 0, 'R');
    	$pdf->Cell($w[2], 6, $subtotal, 'LRB', 0, 'R');
    	$pdf->Ln();
    	$pdf->Cell($w[0]+$w[1], 6, $this->trans('invoice_total'), '', 0, 'R');
    	$pdf->Cell($w[2], 6, $total, 'LRB', 0, 'R');
    }
    
    
    public function trans($translationName)
    {
    	$utf8_sentence = $this->get('translator')->trans($translationName);

    	// For representation of UTF8 characters in PDF
    	return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $utf8_sentence);
    }
}