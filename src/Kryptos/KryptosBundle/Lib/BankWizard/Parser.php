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
					$this->clearConversionStats();
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
					
					$items[$key]['stats'] = $this->getConversionStats();
					
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
	
	
	/**
	 * Function clears the stats counters, sets them back to zero
	 */
	public function clearConversionStats()
	{
		$this->stats_processed = 0;
		$this->stats_valid = 0;
		$this->stats_not_validated = 0;
		$this->stats_invalid = 0;
		$this->stats_reconfirm_iban = 0;
		$this->stats_conversions_refund = 0;
	}
	
	
	/**
	 * Function Retrieves the stats counters as an array
	 * 
	 * @return array
	 */
	public function getConversionStats()
	{
		return array (
			'processed' 			=> $this->stats_processed,
			'valid' 				=> $this->stats_valid,
			'not_validated' 		=> $this->stats_not_validated,
			'invalid' 				=> $this->stats_invalid,
			'reconfirm_iban' 		=> $this->stats_reconfirm_iban,
			'conversions_refund' 	=> $this->stats_conversions_refund,
		);
	}
	
	
	public function setUpPaths($filename)
	{
		$this->readyLocation 		= sprintf('%s%s%s', $this->getConfigManager()->get('site|tmp_path'), $this->getConfigManager()->get('batch_convert|ready_location'), 	 $filename);
		$this->convertedLocation 	= sprintf('%s%s%s', $this->getConfigManager()->get('site|tmp_path'), $this->getConfigManager()->get('batch_convert|converted_location'), $filename);
		# $this->statsLocation 		= sprintf('%s%s%s', $this->getConfigManager()->get('site|tmp_path'), $this->getConfigManager()->get('batch_convert|stats_location'), 	 $filename);
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
		
		// TODO: this is not working, for now lets just copy from a existing file and pretend it was the original file		
		$batch_command = $this->getConfigManager()->get('bankwizard|batch_command');
		$command = sprintf($batch_command, $this->readyLocation, $this->convertedLocation, '');
		exec($command, $output);
		
		/*
		var_dump($command);
		print_r($output);
		sleep(200);
		exit;
		*/
		
		/*
		// TODO: remove ths once above is working
		$command = "cp ./../tmp/batch/converted/output-50.csv ".$this->convertedLocation;
		exec($command, $output);
		*/
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
		#$dataReady = $this->getNextLineFromReady();
		
		// output header info
		# $file_headers = "0,Country ISO,BBAN 1,BBAN 2,BBAN 3,BBAN 4,BIC,IBAN,Status";
		$file_headers = "0,Country ISO,BBAN 1,BBAN 2,BBAN 3,BBAN 4,BIC,IBAN,BankName,BranchName,Address1,Address2,Address3,Address4,Address5,PostalCode,Status";
		
		$dataReady = explode(',', $file_headers);
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
				
				$dataReady[2] = isset($dataConverted[2]) ? $dataConverted[2] : '';		// BBAN1 - in case anything has been transposed
				$dataReady[3] = isset($dataConverted[3]) ? $dataConverted[3] : '';		// BBAN2 - in case anything has been transposed
				$dataReady[4] = isset($dataConverted[4]) ? $dataConverted[4] : '';		// BBAN3 - in case anything has been transposed
				$dataReady[5] = isset($dataConverted[5]) ? $dataConverted[5] : '';		// BBAN4 - in case anything has been transposed
				
				// If BIC present (BIC is 8th item in converted file) add to dataReady array
				// Note: at this point dataReady, first column is Id, hence we add to 7th column
				if (isset($dataConverted[7])) {
					$dataReady[6] = $dataConverted[7];
				}
				
				// If IBAN present (IBAN is 7th item in converted file) add to dataReady array
				// Note: at this point dataReady, first column is Id, hence we add to 8th column
				if (isset($dataConverted[6])) {
					$dataReady[7] = $dataConverted[6];
				}
				
				$dataReady[8]  = isset($dataConverted[8])  ? $dataConverted[8] : '';		// BankName
				$dataReady[9]  = isset($dataConverted[9])  ? $dataConverted[9] : '';		// BranchName
				$dataReady[10] = isset($dataConverted[10]) ? $dataConverted[10] : '';		// Address1
				$dataReady[11] = isset($dataConverted[11]) ? $dataConverted[11] : '';		// Address2
				$dataReady[12] = isset($dataConverted[12]) ? $dataConverted[12] : '';		// Address3
				$dataReady[13] = isset($dataConverted[13]) ? $dataConverted[13] : '';		// Address4
				$dataReady[14] = isset($dataConverted[14]) ? $dataConverted[14] : '';		// Address5
				$dataReady[15] = isset($dataConverted[15]) ? $dataConverted[15] : '';		// PostalCode
				
				// If Status present (Status is 19th item in converted file) add to dataReady array
				// Note: at this point dataReady, first column is Id, and columns 16,17,18 are error,warning,info flags, hence we add to 16th column
				if (isset($dataConverted[19])) {
					$dataReady[16] = $this->retrieveStatus($dataConverted[19]);
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
	 * Convert the status from the api into a status message of our own
	 *  
	 * @param string $state			status message from API
	 * @return string				Our status message
	 */
	public function retrieveStatus($state)
	{
		$newState = '';
				
		switch($state)
		{
			case 'OK':
				$newState = 'Valid';
				$this->stats_valid++;
				break;
			
			case 'UNSUPPORTED_COUNTRY':
				$newState = 'Not Validated';
				$this->stats_not_validated++;
				$this->stats_conversions_refund++;
				break;

			case 'INVALID_ACCOUNT':
				$newState = 'Invalid';
				$this->stats_invalid++;
				break;

			case 'UNKNOWN_BANK_OR_BRANCH':
				$newState = 'Invalid';
				$this->stats_invalid++;
				break;
							
			case 'CHECK_IBAN':
				$newState = 'Reconfirm IBAN';
				$this->stats_reconfirm_iban++;
				$this->stats_conversions_refund++;
				break;
			
			case 'EXCEPTION':
				$newState = 'Not Validated';
				$this->stats_not_validated++;
				$this->stats_conversions_refund++;
				break;
		}
		
		$this->stats_processed++;
		
		return $newState;
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
				throw new \Exception(sprintf('File [%s] is not readable', $filepath));
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
		if (($dataConverted = fgetcsv($this->fileConverted, 1000, ",", '"')) !== FALSE) {
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
