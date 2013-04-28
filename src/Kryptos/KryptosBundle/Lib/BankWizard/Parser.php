<?php

namespace Kryptos\KryptosBundle\Lib\BankWizard;


class Parser
{
	/**
	 * Reference to the config model class
	 *
	 * @var Kryptos\KryptosBundle\Services\ConfigManager
	 * @access protected
	 */
	protected $configManager = null;
	
	
	public function setConfigManager($configManager) {
		$this->configManager = $configManager;
	}
	public function getConfigManager() {
		return $this->configManager;
	}
	
	public function setLogger($logger) {
		$this->logger = $logger;
	}
	public function getLogger() {
		return $this->logger;
	}

	/**
	 * Function allows us to log a message
	 * 
	 * @param string $type				The type of msg that we want to log. e.g. if its an error
	 * @param String $message 			The message that needs to be logged
	 * @author Nitin Patel
	 */
	public function log($type, $message)
	{
		$logger = $this->getLogger();
		if (!is_null($logger)) {
			$message = 'PID=' . posix_getpid() . ': ' . $message;
			switch(strtolower($type))
			{
				case 'error':
					$logger->err(sprintf('{QueueProcessor} ERROR: %s', $message));
					break;
				default:
					$logger->info(sprintf('{QueueProcessor} INFO: %s', $message));
					break;
			} 
		}
	}

	
	
	public function __construct($configManager)
	{
		$this->setConfigManager($configManager);
	}
	

	
	public function run($items)
	{
		foreach ($items as $key => $item)
		{
			if (isset($item['filename'])) {
				$readyLocation 		= sprintf('%s%s%s', $this->getConfigManager()->get('site|tmp_path'), $this->getConfigManager()->get('batch_convert|ready_location'), $item['filename']);
				$convertedLocation 	= sprintf('%s%s%s', $this->getConfigManager()->get('site|tmp_path'), $this->getConfigManager()->get('batch_convert|converted_location'), $item['filename']);
				$processedLocation 	= sprintf('%s%s%s', $this->getConfigManager()->get('site|tmp_path'), $this->getConfigManager()->get('batch_convert|processed_location'), $item['filename']);
				$statsLocation 		= sprintf('%s%s%s', $this->getConfigManager()->get('site|tmp_path'), $this->getConfigManager()->get('batch_convert|stats_location'), $item['filename']);
				
				$output = array();
				$command = sprintf('java --cp FileScanningUtilityFinalV1.jar:bwint10.jar DataScanMain.Datascan "%s" "%s" "%s"', $readyLocation, $processedLocation, $statsLocation);
				exec($command, $output);
				
				#exec('whoami', $output);
				
				var_dump($command, $output);
				sleep(200);
				exit;
				
			}
		}
		
		return $items;
	}
}
