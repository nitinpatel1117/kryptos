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
	
	
	
	protected $fileReadyEOF = false;
	protected $fileConvertedEOF = false;
	
	
	
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
			try {
				if (isset($item['filename']))
				{
					$this->setUpPaths($item['filename']);
					
					// start conversion
					$items[$key]['conversion_start_time'] = new \MongoDate();
					$this->convertFile($item);
					$items[$key]['conversion_end_time'] = new \MongoDate();
					$this->log('info', sprintf('Finished the conversion stage of file: %s', $item['_id']));
					
					// start processing
					$items[$key]['processing_start_time'] = new \MongoDate();
					$this->processFile($item);
					$items[$key]['processing_end_time'] = new \MongoDate();
					$this->log('info', sprintf('Finished the processing stage of file: %s', $item['_id']));
				}
			}
			catch (\Exception $e)
			{
				$items[$key]['error_on_processing'] = 1; 
				$this->log('error', sprintf('An exception was thrown with the message [%s] from BankWizard Parser function.', $e->getMessage() ));
			}
		}
		
		return $items;
	}
	
	
	public function setUpPaths($filename)
	{
		$this->readyLocation 		= sprintf('%s%s%s', $this->getConfigManager()->get('site|tmp_path'), $this->getConfigManager()->get('batch_convert|ready_location'), 	 $filename);
		$this->convertedLocation 	= sprintf('%s%s%s', $this->getConfigManager()->get('site|tmp_path'), $this->getConfigManager()->get('batch_convert|converted_location'), $filename);
		$this->statsLocation 		= sprintf('%s%s%s', $this->getConfigManager()->get('site|tmp_path'), $this->getConfigManager()->get('batch_convert|stats_location'), 	 $filename);
		$this->processedLocation 	= sprintf('%s%s%s', $this->getConfigManager()->get('site|tmp_path'), $this->getConfigManager()->get('batch_convert|processed_location'), $filename);
	}
	
	
	/**
	 * Function starts the batch conversion process - call to bank wizard API
	 * 
	 * This process will result in a new file being created at the batch/converted location
	 * 
	 * @param array $item				Details of the file that is being converted. i.e. Array repesentation of the MongoDB item from file collection
	 */
	public function convertFile(array $item)
	{
		$output = array();
		
		/*
		// TODO: this is not working, for now lets just copy from a existing file and pretend it was the original file		
		$batch_command = $this->getConfigManager()->get('bankwizard|batch_command');
		$command = sprintf($batch_command, $this->readyLocation, $this->convertedLocation, $this->statsLocation);
		exec($command, $output);
		
		/*
		var_dump($command);
		print_r($output);
		sleep(200);
		exit;
		*/
		
		// TODO: remove ths once above is working
		$command = "cp ./../tmp/batch/converted/output-50.csv ".$this->convertedLocation;
		exec($command, $output);
		
	}
	
	
	/**
	 * Function maps the results retrieved from the batch conversion back to the original file
	 * 
	 * @param array $item				Details of the file that is being converted. i.e. Array repesentation of the MongoDB item from file collection
	 */
	public function processFile(array $item)
	{
		// open connections to files
		$this->checkPaths($this->readyLocation);
		$this->fileReady = fopen($this->readyLocation, 'r');
		
		$this->checkPaths($this->convertedLocation);
		$this->fileConverted = fopen($this->convertedLocation, 'r');
		
		$this->fileprocessedReady = fopen($this->processedLocation, 'w');
		
		
		$dataReadyIsAhead = false;
		$dataConvertedIsAhead = false;
		$dataReadLine = 0;
		$dataConvertedLine = 0;
		
		// ignore first line of ready file it is headers
		$dataReady = $this->getNextLineFromReady();
		$this->outputToProcessedFile($dataReady);
		
		do {
			if (false == $dataReadyIsAhead) {
				$dataReady = $this->getNextLineFromReady();
			}
			if (false == $dataConvertedIsAhead) {
				$dataConverted = $this->getNextLineFromConverted();
			}
			
			
			// get the current line numbers for both files
			if (false == $this->fileReadyEOF && isset($dataReady[0])) {
				$dataReadLine = (int) $dataReady[0];
			}
			
			if (false == $this->fileConvertedEOF && isset($dataConverted[0])) {
				$dataConverted[0] = str_replace("'", '', $dataConverted[0]);
				$dataConvertedLine = (int) $dataConverted[0];
			}
			
			
			if ($dataReadLine == $dataConvertedLine) {
				// If IBAN present (IBAN is 7th item in converted file) add to dataReady array
				// Note: at this point dataReady, first column is Id, hence we add to 8th column
				if (isset($dataConverted[6])) {
					$dataReady[7] = $dataConverted[6];
				}
				
				$dataReadyIsAhead = false;
				$dataConvertedIsAhead = false;
			}
			else if ($dataReadLine > $dataConvertedLine) {
				$dataReadyIsAhead = true;
				$dataConvertedIsAhead = false;
			}
			else if ($dataReadLine < $dataConvertedLine) {
				$dataReadyIsAhead = false;
				$dataConvertedIsAhead = true;
			}
			
			
			// in case fileConverted has been fully read, and fileReady has entries left to read
			if (true == $dataReadyIsAhead && true == $this->fileConvertedEOF &&  false == $this->fileReadyEOF) {
				$dataReadyIsAhead = false;
			}
			
			
			// only write to output file if the $dataReady file is going to be read in the next loop
			if (false == $dataReadyIsAhead && false == $this->fileReadyEOF) {
				$this->outputToProcessedFile($dataReady);
			}
			
		} while (false == $this->fileReadyEOF);
	}
	
	
	/**
	 * Function checks that the supplied filepath exists and that it can be read
	 * 
	 * @param string $filepath			Path to a path
	 * @throws \Exception
	 */
	public function checkPaths($filepath)
	{
		if (!file_exists($filepath)) {
			throw new \Exception(sprintf('File [%s] does not exist', $filepath));
		}
		
		if (!is_readable($filepath)) {
			// we know file exists, but we cant read it, try setting permissions on it and try again
			chmod($readyLocation, 0777);
			
			if (!is_readable($filepath)) {
				throw new \Exception(sprintf('File [%s] is ot readable', $filepath));
			}
		}
	}
	
	
	/**
	 * Function returns the contents of the next line of the ready file
	 *
	 * @return array|NULL			Retrieves current line of csv as an array. returns null when end of file is reached.
	 */
	public function getNextLineFromReady()
	{
		if (($dataReady = fgetcsv($this->fileReady, 1000, ",")) !== FALSE) {
			return $dataReady;
		}
		
		$this->fileReadyEOF = true;
		
		return null;
	}
	
	
	/**
	 * Function returns the contents of the next line of the converted file
	 * 
	 * @return array|NULL			Retrieves current line of csv as an array. returns null when end of file is reached.   
	 */
	public function getNextLineFromConverted()
	{
		if (($dataConverted = fgetcsv($this->fileConverted, 1000, ",", "'")) !== FALSE) {
			return $dataConverted;
		}
		
		$this->fileConvertedEOF = true;
		
		return null;
	}
	
	
	public function outputToProcessedFile(array $dataReady)
	{
		// remove the first column as it is the id.
		array_shift($dataReady);
		
		fputcsv($this->fileprocessedReady, $dataReady);
	}
}
