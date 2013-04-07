<?php
namespace Kryptos\KryptosBundle\Lib;

class BatchInsertFile
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
		'bban5',
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
	
	
	
	public function __construct($config_manager, $file_manager, $bank_account_manager, $file_error_manager)
	{
		$this->config_manager 		= $config_manager;
		$this->file_manager 		= $file_manager;
		$this->bank_account_manager = $bank_account_manager;
		$this->file_error_manager 	= $file_error_manager;
	}
	

	public function process($file, array $fileData)
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
			
			// ignore first line, it contains column headers
			$data = $splFileObject->fgetcsv();
			
			while (!$splFileObject->eof()) {
				$lineCount++;
				$results = array();
				$bankAccount = array();
				
				$data = $splFileObject->fgetcsv();
				if (is_array($data) && 3 < count($data)  && count($data) < count($this->keys)+1) {			// count is between 4 and 8
					
					$results = array();
					foreach ($this->keys as $key => $value) {
						$results[$value] = isset($data[$key]) ? $data[$key]  : '' ;
					}
					#$results = array_combine($this->keys, $data);
					$bankAccount = array_merge($bankAccountTemplate, $results);
					$bankAccount['lineNo'] = $lineCount;  // store line number so that we can add entry to file_error collection in future
					
					$this->flushBankAccount($bankAccount);
				}
				else {
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
			
			$this->finalFlushBankAccount();
			$this->finalFlushFileError();
		}
	}
	
	
	public function flushBankAccount($bankAccount)
	{
		$this->bankAccounts[] = $bankAccount; 
		
		if (count($this->bankAccounts) > $this->entriesToRead) {
			$this->bank_account_manager->batchIsert($this->$bankAccounts);
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