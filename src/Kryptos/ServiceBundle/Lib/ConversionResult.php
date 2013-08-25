<?php
namespace Kryptos\ServiceBundle\Lib;

class ConversionResult
{
	protected $singleConversion;
	
	
	public function __construct($singleConversion)
	{
		$this->singleConversion = $singleConversion;
	}
	
	
	public function toArray()
	{
		/*
		 'convertByCountry': false,
		'convertByIban': true,
		'countryCode': null,
		'isFatal': false,
		'isValid': true,
		'isTransposed': false,
		'transposedData': [ ],
		'bban1': null,
		'bban2': null,
		'bban3': null,
		'bban4': null,
		'bic': null,
		'iban': null,
		'bankDetails': {
		'bank_name': '',
		'branch_name': '',
		'post_code': '',
		'bank_address': ''
		},
		'creditTransferSupported': true,
		'directDebitsSupported': true,
		'businessDirectDebitsSupported': true,
		'data': [ ],
		'delimitedData': null,
		'fatalMsg': [ ],
		'errorMsg': [ ],
		'warningMsg': [ ]
		*/
		
		
		$data = array();
		
		$data['iban'] 			= $this->singleConversion->iban;
		$data['bic'] 			= $this->singleConversion->bic;
		
		$data['country_code'] 	= $this->singleConversion->countryCode;
		$data['bban1'] 			= $this->singleConversion->bban1;
		$data['bban2'] 			= $this->singleConversion->bban2;
		$data['bban3'] 			= $this->singleConversion->bban3;
		$data['bban4'] 			= $this->singleConversion->bban4;
		
		$data['bank_details'] 	= $this->singleConversion->bankDetails;
		
		$data['is_valid'] 		= $this->singleConversion->isValid;
		$data['is_transposed'] 	= $this->singleConversion->isTransposed;
		
		$data['credit_transfer_supported'] 		= $this->singleConversion->creditTransferSupported;
		$data['direct_debits_supported'] 			= $this->singleConversion->directDebitsSupported;
		$data['business_direct_debits_supported'] 	= $this->singleConversion->businessDirectDebitsSupported;
		
		$data['errors'] 	= $this->singleConversion->errorMsg;
		$data['warnings'] 	= $this->singleConversion->warningMsg;
		
		
		return array($data);
	}
	
}