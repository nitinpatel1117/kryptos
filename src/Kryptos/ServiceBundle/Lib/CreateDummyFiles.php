<?php
namespace Kryptos\ServiceBundle\Lib;


use Guzzle\Http\Client;

class CreateDummyFiles
{
	/**
	 * A reference to a logger instance
	 * 
	 * @var Symfony\Bridge\Monolog\Logger
	 * @access protected
	 */
	protected $logger = null;
	
	/**
	 * reference to the file being imported
	 *
	 * @var string
	 * @access protected
	 */
	protected $file = null;

	/**
	 * Flag to determine if the existing dummy files should be dropped
	 * 
	 * @var boolean
	 * @access protected
	 */
	protected $dropData = null;
	
	/**
	 * Reference to the bank account model class
	 *
	 * @var Kryptos\KryptosBundle\Model\Manager\UserManager
	 * @access protected
	 */
	protected $userManager = null;
	
	/**
	 * Reference to the config model class
	 *
	 * @var Kryptos\KryptosBundle\Services\ConfigManager
	 * @access protected
	 */
	protected $configManager = null;
	
	
	
	/**
	 * Variable to store translation while they are being read.
	 * We store 500 entries at a time, before batch saving.
	 *
	 * @var array
	 */
	protected $translations = array();
	
	
	/**
	 * Expected columns in each row
	 * @var array
	 */
	protected $keys = array(
		'lang',
		'name',
		'value',
	);

	
	
	public function __construct($translationManager, $configManager, $file, $dropData)
	{
		$this->setTranslationManager($translationManager);
		$this->setConfigManager($configManager);
		$this->setFile($file);
		$this->setDropData($dropData);
	}

	public function setTranslationManager($translationManager) {
		$this->translationManager = $translationManager;
	}
	public function getTranslationManager() {
		return $this->translationManager;
	}
	
	public function setConfigManager($configManager) {
		$this->configManager = $configManager;
	}
	public function getConfigManager() {
		return $this->configManager;
	}
	
	public function setFile($file) {
		$this->file = $file;
	}
	public function getFile() {
		return $this->file;
	}
	
	public function setDropData($dropData) {
		$this->dropData = $dropData;
	}
	public function getDropData() {
		return $this->dropData;
	}
	
	public function setLogger($logger) {
		$this->logger = $logger;
	}
	public function getLogger() {
		return $this->logger;
	}
	
	
	public function run()
	{
		$this->processDropData();
		$this->processFile();
	}
	
	
	public function processDropData()
	{
		if ('true' == $this->getDropData()) {
			// TODO:write come to delete files here
		}
	}
	
	
	public function processFile()
	{
		if (true == $this->validateFile()) {
			$row = 0;
			if (($handle = fopen($this->getFile(), "r")) !== FALSE) {
				while (($data = fgetcsv($handle)) !== FALSE) {
					$row++;
					
					// ignore first row of column headers
					if (1 == $row) {
						continue;
					}
					
					$result = $this->processLine($data);
					$this->saveResponse($result);
				}
				fclose($handle);
			}
		}
		else {
			echo "File cannot be read".PHP_EOL;
			exit;
		}
	}
	
	/**
	 * Process each line of the data
	 * 
	 * Expected fields:
	 * 		Type, Country ISO, BBAN 1, BBAN 2, BBAN 3, BBAN 4, IBAN  
	 * 
	 * @param array $data
	 * @return string
	 */
	public function processLine($data)
	{
		$data = $this->cleanRow($data);

		switch(strtolower($data[0])) {
			case 'iban':
				if (!isset($data[6])) {
					// $this->getLogger()->
					return null;
				}
				$result = $this->makeIbanCall($data[6]);
				break;
					
			case 'bban':
				if (!isset($data[1], $data[2], $data[3], $data[4], $data[5])) {
					// $this->getLogger()->
					return null;
				}
				$result = $this->makeBbanCall($data[1], $data[2], $data[3], $data[4], $data[5]);
				break;
				
			default:
				// TODO: log issue
				// $this->getLogger()->
				$result = array(null, null);
				break;
		}

		return $result;
	}
	
	
	public function makeIbanCall($iban)
	{
		$ibanUrl = $this->getConfigManager()->get('dummy_service|iban_creater');

		// repeat for each language
		
		$search  = array('{lang}', '{iban}');
		$replace = array('en',		$iban);
		$ibanUrl = str_replace($search, $replace, $ibanUrl);
		
		return array($ibanUrl, $this->getReponse($ibanUrl));
	}
	
	public function makeBbanCall($country, $bban1, $bban2, $bban3, $bban4)
	{
		$bban1 = empty($bban1) ? 'null' : $bban1;
		$bban2 = empty($bban2) ? 'null' : $bban2;
		$bban3 = empty($bban3) ? 'null' : $bban3;
		$bban4 = empty($bban4) ? 'null' : $bban4;
		
		$bbanUrl = $this->getConfigManager()->get('dummy_service|bban_creater');

		$search  = array('{lang}', '{country_iso}', '{bban1}', '{bban2}', '{bban3}', '{bban4}');
		$replace = array('en',	   $country, 		$bban1,    $bban2,    $bban3,    $bban4);
		$bbanUrl = str_replace($search, $replace, $bbanUrl);

		$bbanUrl = $this->removeTrailingNulls($bbanUrl);
		
		return array($bbanUrl, $this->getReponse($bbanUrl));
	}
	
	/**
	 * Remove trailing '/null' from the end of url
	 * 
	 * e.g.
	 * 		http://sepa.kryptossystems.com/services/en/convert/bban/521a53c1632ed4931100000a/GB/070116/21132249/null/null
	 * to
	 * 		http://sepa.kryptossystems.com/services/en/convert/bban/521a53c1632ed4931100000a/GB/070116/21132249
	 * 
	 * @param string $url
	 * @return string
	 */
	public function removeTrailingNulls($url)
	{
		for ($x = 0; $x < 4; $x++) {
			if ('/null' == substr($url, -5)) {
				$url = substr($url, 0, -5);
			}
		}
		
		return $url;
	}
	
	
	/**
	 * Make http request and return the result
	 * 
	 * @param string $url
	 * @return string
	 */
	public function getReponse($url) {
		$client = new Client();
		$client = $client->get($url);
		$response = $client->send();
		
		return $response->getBody();
	}

	
	public function validateFile()
	{
		$valid = true;
		$file = $this->getFile();
		
		if (!file_exists($file)) {
			$valid = false;
		}
		
		if (!is_readable($file)) {
			$valid = false;
		} 
		
		return $valid;
	}
	
	/**
	 * Remove spaces from all array values
	 * 
	 * @param array $data		Array to clean
	 * @return array			Cleaned array
	 */
	public function cleanRow($data) {
		foreach ($data as $key =>$value) {
			$data[$key] = str_replace(' ', '', $value);
		}
		
		return $data;
	}

	
	/**
	 * Save the response to file
	 * 
	 * @param array $result 	first item in array is the url that was called, second item in array is the response from the call
	 */
	public function saveResponse($result)
	{
		list($url, $response) = $result;
		$publicKey = $this->getConfigManager()->get('dummy_service|public_key');

		$search = array (
			'http://sepa.kryptossystems.com',
			'/services',
			'/app.php',
			'/nitin_dev.php',
			'/',
			$publicKey,
		);
		$replace = array(
			'',
			'/dummy_services',
			'',
			'',
			'__',
			'publicKey',
		);
		$requestFile = str_replace($search, $replace, $url);
		
		// make filepath for dummy file
		$dummyPath = $this->getConfigManager()->get('dummy_service|filepath');
		$dummyFilepath = sprintf('%s/%s.json', $dummyPath, $requestFile);
		
		file_put_contents ($dummyFilepath, $response); 
	}
}