<?php
namespace Kryptos\KryptosBundle\Lib;

use Kryptos\KryptosBundle\Lib\BbanCountryMappings\Mappings;


/**
 * This class is used to process the results from a single conversion
 * 
 * @author Nitin
 *
 */
class SingleConversion
{
	public $convertByCountry = false;
	public $convertByIban = false;
	
	
	public $countryCode = null;
	
	/**
	 * Flag for whether a fatal error occurred with the conversion tool
	 * @var unknown
	 */
	public $isFatal;
	
	/**
	 * Flag for whether the supplied details are valid
	 * @var unknown
	 */
	public $isValid;
	
	/**
	 * Flag to detemine whether the supplied details were transposed
	 */
	public $isTransposed = false;
	
	/**
	 * Array to hold transposed data
	 * @var array
	 */
	public $transposedData = array();
	
	/**
	 * Variable to hold the bban1 detail
	 * @var string
	 */
	public $bban1;

	/**
	 * Variable to hold the bban2 detail
	 * @var string
	 */
	public $bban2;

	/**
	 * Variable to hold the bban3 detail
	 * @var string
	 */
	public $bban3;

	/**
	 * Variable to hold the bban4 detail
	 * @var string
	 */
	public $bban4;
	
	/**
	 * Variable to hold the bic detail
	 * @var string
	 */
	public $bic;
	
	/**
	 * Variable to hold the iban detail
	 * @var string
	 */
	public $iban;
	
	/**
	 * Variable to hold the bank/branch details
	 * @var array
	 */
	public $bankDetails = array();
	
	
	/**
	 * Flag for - Account does not support credit transfers
	 * @var boolean
	 */
	public $creditTransferSupported = true;
	
	/**
	 * Flag for - Branch is not SEPA compliant for Direct Debits (DD)
	 * @var boolean
	 */
	public $directDebitsSupported = true;
	
	/**
	 * Flag for - Account does not support business direct debits
	 * @var boolean
	 */
	public $businessDirectDebitsSupported = true;
	
	
	/**
	 * data returned from single conversion tool
	 * 
	 * @var array
	 */
	public $data = array();
	
	/**
	 * Array containing a subset of the original data.
	 * This data is key value pairs of the original data, each key value is a result of hythen [-] delimited spliting of each line 
	 * @var array
	 */
	public $delimitedData;
	
	
	/**
	 * Array to store fatal messages that were retrieved from the conversion tool
	 * @var array
	 */
	public $fatalMsg = array();
	
	/**
	 * Array to store error messages that were retrieved from the conversion tool
	 * @var array
	 */
	public $errorMsg = array();
	
	/**
	 * Array to store warning messages that were retrieved from the conversion tool
	 * @var array
	 */
	public $warningMsg = array();
	
	
	protected $configManager;
	
	
	public function setData($data) {
		$this->data = $data;
	}
	public function getData() {
		return $this->data;
	}

	
	
	public function __construct($configManager)
	{
		$this->configManager = $configManager;
	}
	
	
	public function runIban($iban)
	{
		$this->convertByIban = true;
		
		$single_command = $this->configManager->get('bankwizard|single_command');
		$command = sprintf($single_command, '', '', '', '', '', '', $iban);

		
		putenv('BW3LIBS=/var/www/bankwizard');
		
		#if ('G' == $iban[0] && 'B' == $iban[1]) {
		#	putenv('BWTABLES=/var/www/bwtables/GB');
		#} else {
			putenv('BWTABLES=/var/www/bwtables');
		#}
		
		putenv('LD_LIBRARY_PATH=/var/www/bankwizard:');
		
		// set locale - this fixes UTF8 characters issues
		$locale = 'en_GB.UTF8';
		setlocale(LC_ALL, $locale);
		putenv('LC_ALL='.$locale);
		
		exec($command, $this->data);
		
		#echo "<pre>"; var_dump($command); print_r($this->data); var_dump($this->data); exit;
		
		$this->findLine();
		
		#print_r($this->getData());
		#var_dump($this->getData());
		#exit;
		
		// process the results
		$this->process();
	}
	
	
	public function runCountry($countryCode, $args)
	{
		$this->convertByCountry = true;
		
		$this->countryCode = $countryCode;
		list($bban1, $bban2, $bban3, $bban4) = $this->extractCountryArgs($args);
		 
		$single_command = $this->configManager->get('bankwizard|single_command');
		$command = sprintf($single_command, $this->countryCode, $bban1, $bban2, $bban3, $bban4, '', '');
		
		
		putenv('BW3LIBS=/var/www/bankwizard');
		
		#if ('GB' == $this->countryCode) {
		#	putenv('BWTABLES=/var/www/bwtables/GB');
		#} else {
			putenv('BWTABLES=/var/www/bwtables');
		#}
		
		putenv('LD_LIBRARY_PATH=/var/www/bankwizard:');
		
		// set locale - this fixes UTF8 characters issues
		$locale = 'en_GB.UTF8';
		setlocale(LC_ALL, $locale);
		putenv('LC_ALL='.$locale);
		
		exec($command, $this->data);
		
		#echo "<pre>"; var_dump($command); print_r($this->data); var_dump($this->data); exit;

		
		$this->findLine();
		
		#print_r($this->getData());
		#var_dump($this->getData());
		#exit;
		
		// process the results
		$this->process();
	}
	
	
	public function findLine()
	{
		$data = array();
		
		foreach ($this->getData() as $line) {
			$pos = strpos($line, 'ref');
			if (0 === $pos) {
				$data = str_getcsv($line);
			}
		}
		
		$this->setData($data);
	}
	
	
	public function extractCountryArgs($args)
	{
		$bban1 = isset($args[0]) ? $args[0] : '';
		$bban2 = isset($args[1]) ? $args[1] : '';
		$bban3 = isset($args[2]) ? $args[2] : '';
		$bban4 = isset($args[3]) ? $args[3] : '';
		
		return array($bban1, $bban2, $bban3, $bban4);
	}
	
	
	public function process()
	{
		if (false == $this->fatalCheck())
		{
			if (false == $this->errorCheck()) {
				$this->warningCheck();
				
				# $this->processHythenDelimited();
				
				$this->processBbans();
				$this->processBic();
				$this->processIban();
				
				$this->processFields();
			}
		}
	}
	
	
	
	
	public function fatalCheck()
	{
		$fatal = false;
		
		$data = $this->getData();
		if (!is_array($data) || empty($data)) {
			$fatal = true;
		}
		else if (isset($data[19]) && ('EXCEPTION' == $data[19] || 'UNSUPPORTED_COUNTRY' == $data[19])) {
			$fatal = true;
		}

		$this->isFatal = $fatal;
		
		return $fatal;
	}
	
	
	public function errorCheck()
	{
		$error = false;
	
		$data = $this->getData();
		if (isset($data[16]) && '' != $data[16])
		{
			$error = true;
			
			// find the error messages
			$errorLines = explode('&', $data[16]);
			foreach ($errorLines as $errorItems) {
				$line = explode('-', $errorItems);
				if (isset($line[1])) {
					$this->errorMsg[] = $line[1];
				}
			}
		}
		
		// to get isValid - we inverse the error flag
		$this->isValid = !$error;
	
		return $error;
	}
	
	
	
	public function warningCheck()
	{
		$data = $this->getData();
		
		if (isset($data[17]) && '' != $data[17])
		{
			// find the warning messages
			$warningLines = explode('&', $data[17]);
			foreach ($warningLines as $warningItems) {
				$line = explode('-', $warningItems);
				if (isset($line[0]) && isset($line[1])) {
					
					switch ($line[0]) {
						// Warning (18): Account does not support credit transfers
						case '18':
							$this->creditTransferSupported = false;
							$this->warningMsg[] = $line[1];
							break;
						
						// Warning (20): Branch is not SEPA compliant for Direct Debits (DD)
						case '20':
							$this->directDebitsSupported = false;
							$this->warningMsg[] = $line[1];
							break;
						
						// Warning (47): Account does not support business direct debits
						case '47':
							$this->businessDirectDebitsSupported = false;
							$this->warningMsg[] = $line[1];
							break;
						
						// Warning (1): Account details were not in standard form and have been transposed
						case '1':
							$this->isTransposed = true;
							$this->warningMsg[] = $line[1];
							break;
					}
				}
			}
		}
	}
	

	
	
	
	
	public function processBbans()
	{
		$data = $this->getData();
		
		if (isset($data[2]) && '' != $data[2]) {
			$this->bban1 = $data[2];
		}
		
		if (isset($data[3]) && '' != $data[3]) {
			$this->bban2 = $data[3];
		}
		
		if (isset($data[4]) && '' != $data[4]) {
			$this->bban3 = $data[4];
		}
		
		if (isset($data[5]) && '' != $data[5]) {
			$this->bban4 = $data[5];
		}
		
	}
	
	public function processBic()
	{
		$data = $this->getData();
	
		if (isset($data[7]) && '' != $data[7]) {
			$this->bic = $data[7];
		}
	}
	
	public function processIban()
	{
		$data = $this->getData();
		
		if (isset($data[6]) && '' != $data[6]) {
			$this->iban = $data[6];
		}
	}
	
	public function processFields()
	{
		$bank = array();

		$data = $this->getData();
		
		$bank['bank_name'] 		= isset($data[8])  ? $data[8] : ''; 
		$bank['branch_name'] 	= isset($data[9])  ? $data[9] : '';
		$bank['post_code']		= isset($data[15]) ? $data[15] : '';
		
		$address = array();
		(isset($data[10]) && '' != $data[10])  ? $address[] = $data[10] : '';	// address line 1
		(isset($data[11]) && '' != $data[11])  ? $address[] = $data[11] : '';	// address line 2
		(isset($data[12]) && '' != $data[12])  ? $address[] = $data[12] : '';	// address line 3
		(isset($data[13]) && '' != $data[13])  ? $address[] = $data[13] : '';	// address line 4
		(isset($data[14]) && '' != $data[14])  ? $address[] = $data[14] : '';	// address line 5
		(isset($data[15]) && '' != $data[15])  ? $address[] = $data[15] : '';	// add the postcode
		$bank['bank_address'] 	= implode(', ', $address);
		
		$this->bankDetails = $bank;
	}
	
	public function getErrorsAsString()
	{
		$msg = '';
		
		if (count($this->errorMsg) > 0) {
			$msg = implode('. ', $this->errorMsg);
			$msg .= '.';
		}
		
		return $msg;
	}
}