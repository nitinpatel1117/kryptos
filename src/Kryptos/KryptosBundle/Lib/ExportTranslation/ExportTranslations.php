<?php
namespace Kryptos\KryptosBundle\Lib\ExportTranslation;

use Kryptos\KryptosBundle\Lib\ExportTranslation\Format\XliffExporter;

class ExportTranslations
{
	/**
	 * A reference to a logger instance
	 * 
	 * @var Symfony\Bridge\Monolog\Logger
	 * @access protected
	 */
	protected $logger = null;
	
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
	 * An array to hold the name of each locale in the system.
	 * 
	 * This array is populated during execution time, by running a distinct query in mongodb
	 * 
	 * @var array
	 */
	protected $locales = array();
	
	/**
	 * variable to hold the file exporter class
	 * 
	 * @var FileExporter
	 */
	protected $exporter = null;

	
	public function __construct($translationManager, $configManager, $appPath)
	{
		$this->setTranslationManager($translationManager);
		$this->setConfigManager($configManager);
		$this->setAppPath($appPath);
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
	
	public function setAppPath($appPath) {
		$this->appPath = $appPath;
	}
	public function getAppPath() {
		return $this->appPath;
	}
	
	public function setLocales($locales) {
		$this->locales = $locales;
	}
	public function getLocales() {
		return $this->locales;
	}
	
	public function setLogger($logger) {
		$this->logger = $logger;
	}
	public function getLogger() {
		return $this->logger;
	}
	
	
	
	public function run($exportFormat = 'xliff')
	{
		$this->determineLocales();
		$this->setExporter($exportFormat);
		$this->processFile();
	}
	
	
	public function setExporter($exportFormat)
	{
		switch ($exportFormat) {
			case 'xliff':
				$this->exporter = new XliffExporter($this->getAppPath());
				break;
			
			default:
				throw new \Exception('Unrecognised file format');
				break;
		}
	}
	
	
	public function determineLocales()
	{
		$locales = $this->getTranslationManager()->getDistinctLocales();
		
		if (!is_null($locales)) {
			$this->setLocales($locales);
		}
	}
	
	
	public function processFile()
	{
		foreach ($this->getLocales() as $locale)
		{
			$data = $this->getTranslationManager()->getDataForLocale($locale);
			$this->exporter->run($locale, $data);
		}
	}
}