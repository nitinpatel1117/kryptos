<?php
namespace Kryptos\KryptosBundle\Lib\ImportTranslation;

use Kryptos\KryptosBundle\Lib\BankWizard\Parser as BankWizardParser;

class ImportTranslations
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
	 * Flag to determine if the existing translation data should be dropped
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
	
	
	protected $entriesToRead = 500;
	
	
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
			$this->getTranslationManager()->drop();
		}
	}
	
	
	public function processFile()
	{
		if (true == $this->validateFile()) {

			$row = 1;
			if (($handle = fopen($this->getFile(), "r")) !== FALSE) {
				while (($data = fgetcsv($handle)) !== FALSE) {
					
					// make sure that we have three items being read
					if (3 != count($data)) {
						echo "Error on line $row. Expected 3 columns instead recieved ".count($data)."columns";
						var_dump($data);
						//$this->finalFlushTranslations();
						exit;
					}
					
					$data[0] = trim($data[0]);
					$data[1] = trim($data[1]);
					$data[2] = trim($data[2]);
					
					// make sure value is utf8
					$data[2] = $this->makeUTF8($data[2]);
					
					$translation = array();
					foreach ($this->keys as $key => $value) {
						$translation[$value] = isset($data[$key]) ? $data[$key]  : '' ;
					}
						
					$this->saveTranslation($translation);
					$row++;
				}
				
				//$this->finalFlushTranslations();
				fclose($handle);
			}
		}
		else {
			echo "File cannot be read".PHP_EOL;
			exit;
		}
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
	
	
	public function saveTranslation($translation)
	{
		
		$data = $this->getTranslationManager()->findTranslation($translation);
		
		if (empty($data)) {
			$this->getTranslationManager()->insert($translation);
		}
		else if (1 == count($data)) {
			if (false == $this->getTranslationManager()->areTranslationsSame($translation, $data[0])) {
				
				var_dump('Translation already exists with a different value. Replacing:');
				var_dump($data[0]);
				var_dump('With:');
				var_dump($translation);
				
				$this->getTranslationManager()->remove($data[0]);
				$this->getTranslationManager()->insert($translation);
			}
		}
		else if (1 < count($data)) {
			var_dump('Translation already exists with multiple values. Replacing:');
			var_dump($data);
			var_dump('With:');
			var_dump($translation);
			
			foreach($data as $item) {
				$this->getTranslationManager()->remove($item);
			}
			$this->getTranslationManager()->insert($translation);
		}
		
		
		
		
		
		
		
		
		$this->translations[] = $translation;
	
		if (count($this->translations) > $this->entriesToRead) {
			$this->getTranslationManager()->batchIsert($this->translations);
			$this->translations = array();
		}
	}
	
	public function flushTranslations($translation)
	{
		$this->translations[] = $translation;
	
		if (count($this->translations) > $this->entriesToRead) {
			$this->getTranslationManager()->batchIsert($this->translations);
			$this->translations = array();
		}
	}
	
	public function finalFlushTranslations()
	{
		if (count($this->translations) > 0) {
			$this->getTranslationManager()->batchIsert($this->translations);
		}
	}
	
	
	public function makeUTF8($value)
	{
		if (true !== mb_check_encoding ($value, 'UTF-8')) {
			$value = utf8_encode($value);
		}
			
		return $value;
	}

	
}