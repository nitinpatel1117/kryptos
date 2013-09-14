<?php
namespace Kryptos\KryptosBundle\Lib\ExportTranslation;

abstract class FileExporter
{
	protected $fileHandle;
	
	protected $appPath;
	
	protected $filePath;
	
	protected $locale;
	
	protected $data;
	
	
	public function __construct($appPath)
	{
		$this->setAppPath($appPath);
	}
	
	
	public function setAppPath($appPath) {
		$this->appPath = $appPath;
	}
	public function getAppPath() {
		return $this->appPath;
	}
	
	public function setFilePath($filePath) {
		$this->filePath = $filePath;
	}
	public function getFilePath() {
		return $this->filePath;
	}
	
	public function setLocale($locale) {
		$this->locale = $locale;
	}
	public function getLocale() {
		return $this->locale;
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	public function getData() {
		return $this->data;
	}
	
	
	public function run($locale, $data)
	{
		$this->setLocale($locale);
		$this->setData($data);
		
		$this->setFilePath(sprintf('%s/Resources/translations/messages.%s.xliff', $this->getAppPath(), $this->getLocale())) ;
		$this->renameIfPresent();
		
		
		$this->outputHeader();
		$this->outputMainData();
		$this->outputFooter();
		
	}
	
	
	public function renameIfPresent()
	{
		if (file_exists($this->getFilePath())) {
			$filePathNew = sprintf('%s.bak.%s', $this->getFilePath(), date('Ymd_His'));
			rename($this->getFilePath(), $filePathNew);
		}
	}
	
	
    // Force Extending class to define this method
    abstract public function outputHeader();
    abstract public function outputMainData();
    abstract public function outputFooter();
}