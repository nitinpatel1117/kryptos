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
	 * Variable to hold the iban detail
	 * @var string
	 */
	public $iban;
	
	/**
	 * Variable to hold the bic detail
	 * @var string
	 */
	public $bic;
	
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
	
	
	
	public function getData() {
		return $this->data;
	}

	
	
	public function __construct($configManager)
	{
		$this->configManager = $configManager;
	}
	
	
	public function run($countryCode, $args)
	{
		$this->countryCode = $countryCode;
		 
		$single_command = $this->configManager->get('bankwizard|single_command');
		$command = sprintf($single_command, $this->countryCode, implode(' ', $args));
		
		putenv('BW3LIBS=/var/www/bankwizard');
		putenv('BWTABLES=/var/www/bwtables');
		putenv('LD_LIBRARY_PATH=/var/www/bankwizard:');
		
		exec($command, $this->data);
		
		// process the results
		$this->process();
	}
	
	
	public function process()
	{
		if (false == $this->fatalCheck())
		{
			if (false == $this->errorCheck()) {
				$this->warningCheck();
				
				$this->processHythenDelimited();
				
				$this->processIban();
				$this->processBic();
				$this->processFields();
			}
		}
	}
	
	
	
	
	public function fatalCheck()
	{
		$fatal = false;
		
		foreach ($this->getData() as $line) {
			$pos = strpos($line, 'Fatal');
			if ($pos !== false) {
				$fatal = true;
				
				// dont 'break' we want to capture the fatal messages
				$this->fatalMsg[] = $line;
			}
		}
		$this->isFatal = $fatal;
		
		return $fatal;
	}
	
	
	public function errorCheck()
	{
		$initialised = false;
		$error = false;
	
		foreach ($this->getData() as $line) {
			
			$pos = strpos($line, 'Initialisation Suceeded');
			if ($pos !== false) {
				$initialised = true;
			}
			
			// we only look for error messages after the 'Initialisation Suceeded' text
			if (true == $initialised) {
				
				// we only look for 'Error (1-20)'
				for ($x = 1; $x < 21; $x++) {
					$searchMsg = sprintf('Error (%s)', $x); 
					
					$pos = strpos($line, $searchMsg);
					if ($pos !== false) {
						$error = true;
						
						// dont 'break' we want to capture the error messages
						$this->errorMsg[] = $line;
					}
				}
			}	
		}
		
		// to get isValid - we inverse the error flag
		$this->isValid = !$error;
	
		return $error;
	}
	
	
	
	public function warningCheck()
	{
		$initialised = false;
		$readNextlineAsTransposedTo = false;
		
		foreach ($this->getData() as $line)
		{
			$pos = strpos($line, 'Initialisation Suceeded');
			if ($pos !== false) {
				$initialised = true;
			}
				
			// we only look for warning messages after the 'Initialisation Suceeded' text
			if (true == $initialised)
			{
				// Warning (18): Account does not support credit transfers
				if (strpos($line, 'Warning (18)') !== false) {
					$this->creditTransferSupported = false;
					$this->warningMsg[] = $line;
				}
				
				// Warning (20): Branch is not SEPA compliant for Direct Debits (DD)
				if (strpos($line, 'Warning (20)') !== false) {
					$this->directDebitsSupported = false;
					$this->warningMsg[] = $line;
				}
			
				// Warning (47): Account does not support business direct debits
				if (strpos($line, 'Warning (47)') !== false) {
					$this->businessDirectDebitsSupported = false;
					$this->warningMsg[] = $line;
				}
				
				// Warning (1): Account details were not in standard form and have been transposed
				if (strpos($line, 'Warning (1)') !== false) {
					$this->isTransposed = true;
					$this->warningMsg[] = $line;
				}
				
				# Transposed from/to
				if (true == $this->isTransposed) {
					if (strpos($line, 'Transposed from') !== false) {
						$this->transposedData['from'] =  $this->getBbanFromTransposed($line);
						$readNextlineAsTransposedTo = true;
					}
					else if (true == $readNextlineAsTransposedTo) {
						$this->transposedData['to'] =  $this->getBbanFromTransposed($line);
						$readNextlineAsTransposedTo = false;
					}
				}
			}
		}
	}
	

	public function getBbanFromTransposed($line)
	{
		$bban = array();
		
		$data = explode (':', $line);
		if (isset($data[1])) {
			$data[1] = trim($data[1]);
			$bban = explode (' ', $data[1]);
			
			foreach ($bban as $key => $bbanData) {
				$bban[$key] = str_replace("'", '', $bbanData);
			}
		}
		
		return $bban;
	}
	
	
	
	/**
	 * Function takes the output supplied by the single conversion tool and turns it into a
	 * an associative array that can be transversd easily.
	 *
	 * @return array
	 */
	public function processHythenDelimited()
	{
		$data = array();
			
		foreach ($this->getData() as $lineout) {
			$lineSplit = explode(' - ' , $lineout);
	
			if (isset($lineSplit[0]) && isset($lineSplit[1]))
			{
				$key = trim($lineSplit[0]);
				$key = str_replace('Field ', '1', $key);
	
				$value = trim($lineSplit[1]);
				$value = str_replace("'", '', $value);
					
				$data[$key] = $value;
					
				unset($key);
				unset($value);
			}
		}
	
		$this->delimitedData = $data;
	}
	
	
	public function processIban()
	{
		if (isset($this->delimitedData['IBAN'])) {
			$this->iban = utf8_encode($this->delimitedData['IBAN']);
		}
	}
	
	public function processBic()
	{
		$bic = null;
		
		return $bic;
	}
	
	public function processFields()
	{
		$data = array();
		$mappings = new Mappings();
		$countryBankMaps = $mappings->getConversionBankDetailsMappings($this->countryCode);

		foreach ($countryBankMaps as $key => $fieldList) {
			$details = array();
			
			foreach ($fieldList as $field) {
				if (isset($this->delimitedData[$field])) {
					$details[] = $this->delimitedData[$field];
				}
			}
			$data[$key] = utf8_encode(implode(' ', $details));
		}
		
		$this->bankDetails = $data;
	}
}