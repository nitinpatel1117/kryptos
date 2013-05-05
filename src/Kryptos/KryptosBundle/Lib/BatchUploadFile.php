<?php
namespace Kryptos\KryptosBundle\Lib;


/**
 * This class is used to when we upload a batch file and want to read add the entries of the batch file into the mongodb database 
 * 
 * @author Nitin
 *
 */
class BatchUploadFile
{
	/**
	 * MongoDB classes
	 * @var unknown
	 */
	protected $config_manager;
	protected $file_manager;
	protected $bank_account_manager;
	protected $file_error_manager;
	
	
	/**
	 * The file currently being read
	 * @var Symfony\Component\HttpFoundation\File\UploadedFile;
	 */
	protected $file;
	
	
	/**
	 * Expected columns in each row
	 * @var array
	 */
	protected $keys = array(
		'country',
		'bban1',
		'bban2',
		'bban3',
		'bban4',
	#	'bban5',
		'iban',
		'bic',
	);
	
	
	/**
	 * Variable to store bank account numbers while they are being read. 
	 * We store 500 entries at a time, before batch saving. 
	 * 
	 * @var array
	 */
	protected $bankAccounts = array();
	
	
	/**
	 * Variable to store file errors while they are being read.
	 * We store 500 entries at a time, before batch saving.
	 *
	 * @var array
	 */
	protected $fileErrors = array();
	
	
	protected $entriesToRead = 500;
	
	
	
	public function __construct($config_manager, $file_manager, $bank_account_manager, $file_error_manager, $user_manager)
	{
		$this->config_manager 		= $config_manager;
		$this->file_manager 		= $file_manager;
		$this->bank_account_manager = $bank_account_manager;
		$this->file_error_manager 	= $file_error_manager;
		$this->user_manager 		= $user_manager;
	}
	

	public function process($file, array $fileData, array $additionalData)
	{
		$this->file = $file;
		
		// add entry to files collections
		$fileData['upload_time'] = new \MongoDate();
		$fileData['status'] = 'pending';
		$this->file_manager->insert($fileData);
		
		
		$bankAccountTemplate = array(
			'file' 		=> $fileData['_id'],
			'sessionId' => $fileData['sessionId'],
	    	'userId'	=> $fileData['userId'],
			'type'		=> 'batch',
			'status' 	=> 'pending',
		);
		
		$fileErrorTemplate = array(
			'file_id' 	=> $fileData['_id'],
		);

		$lineCount = 1;
		
		if ($this->file->isReadable()) {
			$splFileObject = $this->file->openFile('r');
			
			// add file to ready location
			$readyLocation = sprintf('%s%s%s', $this->config_manager->get('site|tmp_path'), $this->config_manager->get('batch_convert|ready_location'), $additionalData['newFilename']);
			touch($readyLocation);
			chmod($readyLocation, 0777);
			$fileOutReady = fopen($readyLocation, 'w');
			
			// add first line to output, it should be empty line
			fputcsv($fileOutReady, array());
			
			// add second line to output, it contains column headers
			$data = $splFileObject->fgetcsv();
			$data = $this->makeFileHeaderData($data);
			fputcsv($fileOutReady, $data);
			
			// get credits for the user
			$credits = $additionalData['credits'];
			
			while (!$splFileObject->eof() && $credits > 0) {
				$lineCount++;
				
				$data = $splFileObject->fgetcsv();
				if (is_array($data) && 3 < count($data)  && count($data) < count($this->keys)+1) {			// count is between 4 and 8
					
					$this->writeCsvData($fileOutReady, $data, $lineCount);
					/*
					array_unshift($data, $lineCount);
					$data = $this->makeCsvData($data);
					fputcsv($fileOutReady, $data);
					*/
					
					if ($additionalData['conversionsRestricted']) {
						$credits--;
					}
				}
				else {
					// write to mongodb every entry that was incorrectly formatted
					$fileError = $fileErrorTemplate;
					$fileError['lineNo'] = $lineCount;
					
					if (is_array($data)) {
						$data = implode(',', $data);
					}
					if (is_string($data)) {
						$fileError['text'] = $data;
					}
					$this->flushFileError($fileError);
				}
			}
			
			fclose($fileOutReady);
			
			#$this->finalFlushBankAccount();
			$this->finalFlushFileError();
			
			if ($additionalData['conversionsRestricted']) {
				$this->user_manager->registerCreditsUsed($fileData['userId'], $fileData['_id'], $additionalData['credits'], $credits);
			}
		}
	}
	
	
	/**
	 * Function makes the column headers.
	 * 
	 * The headers should contain no spaces in the column names.
	 * add #ID as the first column 
	 * 
	 * @param array $data
	 * @return aray
	 */
	public function makeFileHeaderData($data)
	{
		for ($x=0, $count=count($data); $x < $count; $x++) {
			$data[$x] = str_replace(' ', '', $data[$x]);
		}
		array_unshift($data, '#ID');
		
		return $data;
	}
	
	
	
	public function writeCsvData($fileOutReady, $data, $lineCount)
	{
		$data = $this->makeCsvData($data);
		if (!empty($data)) {
			array_unshift($data, $lineCount);
			fputcsv($fileOutReady, $data);
		}
	}
	
	
	/**
	 * Function makes sure that the csv file is always set to a preset size
	 * 
	 * @param array $data
	 * @return array
	 */
	public function makeCsvData($data)
	{
		// TODO: This is hard coded for now. The csv output we produce must contain 8 columns of data
		$columns = 8;
		
		$data = array_pad($data, $columns, '');
		
		if ($columns < count($data)) {
			$data = array_slice($data, 0, $columns);
		}
		
		return $data;
	}
	
	
	public function flushBankAccount($bankAccount)
	{
		$this->bankAccounts[] = $bankAccount; 
		
		if (count($this->bankAccounts) > $this->entriesToRead) {
			$this->bank_account_manager->batchIsert($this->bankAccounts);
			$this->bankAccounts = array();
		}
	}
	
	public function finalFlushBankAccount()
	{
		if (count($this->bankAccounts) > 0) {
			$this->bank_account_manager->batchIsert($this->bankAccounts);
		}
	}
	
	
	
	public function flushFileError($fileError)
	{
		$this->fileErrors[] = $fileError;
	
		if (count($this->fileErrors) > $this->entriesToRead) {
			$this->file_error_manager->batchIsert($this->fileErrors);
			$this->fileErrors = array();
		}
	}
	
	public function finalFlushFileError()
	{
		if (count($this->fileErrors) > 0) {
			$this->file_error_manager->batchIsert($this->fileErrors);
		}
	}		
}