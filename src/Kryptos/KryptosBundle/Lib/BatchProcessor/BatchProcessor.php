<?php
namespace Kryptos\KryptosBundle\Lib\BatchProcessor;

use Kryptos\KryptosBundle\Lib\BankWizard\Parser as BankWizardParser;

class BatchProcessor
{
	/**
	 * A reference to a logger instance
	 * 
	 * @var Symfony\Bridge\Monolog\Logger
	 * @access protected
	 */
	protected $logger = null;
	
	/**
	 * Number of item to read from queue
	 *
	 * @var integer
	 * @access protected
	 */
	protected $noOfItemsToRead = 1;

	/**
	 * Reference to the bank account model class
	 * 
	 * @var Kryptos\KryptosBundle\Model\Manager\FileManager
	 * @access protected
	 */
	protected $fileManager = null;
	
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
	 * An array to hold the items that have been retrieved from the queue
	 * 
	 * @var array
	 * @access protected
	 */
	protected $queueItems = array();

	/**
	 * An array to hold the items that have been successfully processed and now need to be deleted
	 * 
	 * @var array
	 * @access protected
	 */
	protected $queueItemsToDelete = array();

	/**
	 * An array to hold the items that failed the process stage. These items need to be re-added to the queue with a future timestamp
	 * 
	 * @var array
	 * @access protected
	 */
	protected $failedItems = array();

	/**
	 * An anonymous function (Closure) to retrieve the formula that will be used in order to calculate the future read time of a failed item.
	 * 
	 * @var Anonymous function
	 * @access protected
	 */
	protected $formula = null;



	public function __construct($fileManager, $userManager, $configManager, $noOfItemsToRead = 1)
	{
		$this->setFileManager($fileManager);
		$this->setUserManager($userManager);
		$this->setConfigManager($configManager);
		$this->setNoOfItemsToRead($noOfItemsToRead);
	}

	public function getNewQueueClient($version = null, $consumer = null)
	{
		return new \Queue_Client($version, $consumer);
	}

	
	public function setNoOfItemsToRead($noOfItemsToRead) {
		$this->noOfItemsToRead = $noOfItemsToRead;
	}
	public function getNoOfItemsToRead() {
		return $this->noOfItemsToRead;
	}
	
	public function setFileManager($fileManager) {
		$this->fileManager = $fileManager;
	}
	public function getFileManager() {
		return $this->fileManager;
	}
	
	public function setUserManager($userManager) {
		$this->userManager = $userManager;
	}
	public function getUserManager() {
		return $this->userManager;
	}
	
	public function setConfigManager($configManager) {
		$this->configManager = $configManager;
	}
	public function getConfigManager() {
		return $this->configManager;
	}
	
	public function setQueueItems($queueItems) {
		$this->queueItems = $queueItems;
	}
	public function getQueueItems() {
		return $this->queueItems;
	}
	
	public function setQueueItemsToDelete($queueItemsToDelete) {
		$this->queueItemsToDelete = $queueItemsToDelete;
	}
	public function getQueueItemsToDelete() {
		return $this->queueItemsToDelete;
	}
	
	public function setFailedItems($failedItems) {
		$this->failedItems = $failedItems;
	}
	public function getFailedItems() {
		return $this->failedItems;
	}
	
	public function setFormula($formula) {
		if (is_object($formula)) {
			$this->formula = $formula;
		}
	}
	public function getFormula() {
		return $this->formula;
	}
	
	public function setLogger($logger) {
		$this->logger = $logger;
	}
	public function getLogger() {
		return $this->logger;
	}

	/**
	 * Function allows us to log an message
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

	/**
	 * This function calls methods to read items from the queue, 
	 * process the items and then delete the items if they have been processed successfully.
	 * 
	 * @return void
	 * @author Nitin Patel
	 */
	public function run()
	{	
		$this->retrieveItemsFromQueue();
		$this->processItemsFromQueue();
		$this->saveItemsToQueue();
	}


	/**
	 * This function returns items from the queue, and stores these items in the $queueItems property.
	 * 
	 * @return void
	 * @author Nitin Patel
	 */
	public function retrieveItemsFromQueue()
	{
		$queueEntries = array();

		$itemsToRead = $this->getNoOfItemsToRead();
		if (!is_null($itemsToRead) && !empty($itemsToRead) && is_numeric($itemsToRead)) {
			$itemsToRead = (int) $itemsToRead;
		}
		
		$lockTime = $this->getConfigManager()->get('batch_convert|locktime');
		$lockName = gethostname() . '|' . posix_getpid();

		$fileManager = $this->getFileManager();
		$queueEntries = $fileManager->lockItemsFromQueue($lockTime, $lockName, $itemsToRead);
		
		// log the entries that were retrieved
		if (is_array($queueEntries))
		{
			if (!empty($queueEntries)) {
				$idsRead = array(); 
				foreach($queueEntries as $queueEntry) {
					$idsRead[] = $queueEntry['_id'];
				}
				$this->log('info', sprintf('%s entries were read from the queue, the object ids are as follows: %s', count($queueEntries), implode(", ", $idsRead)  ));
			}
			else {
				$this->log('info', sprintf('%s entries were read from the queue. Empty Result', 0));
			}
		}
		else {
			$this->log('info', sprintf('%s entries were read from the queue.', 0));
		}

		$this->setQueueItems($queueEntries);
	}


	/**
	 * Function loops throught the items that have been returned from the queue and calls the Roller process for each.
	 * Successfullly processed items are marked for deletion and then saved in the queueItemsToDelete property.
	 * 
	 * @return void
	 * @author Nitin Patel
	 */
	public function processItemsFromQueue()
	{
		$queueItems = $this->getQueueItems();

		if (!empty($queueItems))
		{
			$itemsToDelete = array();
			$failedItems = array();
			
			$parser = new BankWizardParser($this->getConfigManager());
			$parser->setLogger($this->getLogger());
			
			$queueItems = $parser->run($queueItems);
			
			$this->log('info', sprintf('%s entries were processed from the queue', count($queueItems)));
			
			/*
			foreach ($queueItems as $queueItem)
			{
				$nh = $this->getNotificationHandler();

				try {
					// call the Roller process
					$nh->registerNotification($queueItem->domainId, $queueItem->objectType, $queueItem->objectId, $queueItem->actionType);

					// item was successfully processed, therefore add it to the list of items to delete
					$itemsToDelete[$queueItem->id] = $queueItem->objectId;
					$processState = 'success';
				}
				catch (\Exception $e)
				{
					$this->log('error', sprintf('Consumer id %s. An exception was thrown with the message [%s] from registerNotification function for the following input parameters (%s, %s, %s, %s): ', $this->getConsumer(), $e->getMessage(), $queueItem->domainId, $queueItem->objectType, $queueItem->objectId, $queueItem->actionType ));

					$failedItems[] = $queueItem;
					$processState = 'failed';
				}

				$this->log('info', sprintf('Consumer id %s. Object with id %s was processed and returned with the status: %s', $this->getConsumer(), $queueItem->objectId, $processState ));
			}
			$this->setQueueItemsToDelete($itemsToDelete);
			$this->setFailedItems($failedItems);
			*/
			
			$this->setQueueItems($queueItems);
		}
	}


	/**
	 * Function deletes the items that have been marked for deletion.
	 * 
	 * @return void
	 * @author Nitin Patel
	 */
	public function saveItemsToQueue()
	{
		$queueItems = $this->getQueueItems();
		
		$saveErrors = 0;

		if (!empty($queueItems))
		{
			$fileManager = $this->getFileManager();
			
			foreach($queueItems as $queueItem)
			{
				if (isset($queueItem['error_on_processing'])) {
					if (!isset($queueItem['error_count'])) {
						$queueItem['error_count'] = 0;
					}
					$queueItem['error_count']++;
					$unixTime = $this->calculateNextReadTime($queueItem['error_count']);
					
					$queueItem['locktime'] = new \MongoDate($unixTime);
					$queueItem['lockname'] = '';
					
					unset($queueItem['error_on_processing']);
				}
				else {
					$queueItem['status'] = 'complete';
					if (isset($queueItem['stats']['conversions_refund']) && is_int($queueItem['stats']['conversions_refund']) && ($queueItem['stats']['conversions_refund'] > 0))
					{
						// if this batch upload belongs to a registered user, update their credits
						if (isset($queueItem['userId'])) {
							$userManager = $this->getUserManager();
							$userManager->refundCredits($queueItem['userId'], $queueItem['_id'], $queueItem['stats']['conversions_refund']);
						}
					}
				}
				
				try {
					$fileManager->save($queueItem);
				} catch (\Exception $e) {
					$this->log('error', sprintf('Error saving item back to queue. Item id: %s.  Message: %s', $queueItem['_id'], $e>getMessage ()));
					$saveErrors++;
				}
			}
			
			$this->log('info', sprintf('%s entries were saved back to the queue', (count($queueItems) - $saveErrors) ));
		}
	}
	
	
	/**
	 * Function returns the number of items that were last returned from the queue.
	 * 
	 * @return Integer
	 * @author Nitin Patel
	 */
	public function itemsReadFromQueue()
	{
		return count($this->getQueueItems());
	}

	public function init()
	{
		ini_set('memory_limit', '1024M');
		ini_set('max_execution_time', 0);
	}
	
	
	/**
	 * Function return a timestamp, which is calculated from a formula for a given item count integer
	 *
	 * @param Integer $itemFailedCount		An integer representing the number of times an item has failed to be processed
	 * @return Integer						A time in the future returned as a unix timestamp
	 * @author Nitin Patel
	 */
	public function calculateNextReadTime($itemFailedCount)
	{
		if (is_null($this->getFormula())) {
			$this->setFormula($this->makeFormula());
		}
		$formula = $this->getFormula();
	
		return $formula($itemFailedCount);
	}
	
	
	/**
	 * Function retrieves a formula, which will be used to calculate an unix timestamp. The timestamp is of a time in the future.
	 *
	 * @return Anonymous function				The formula that we be used to calculate a timestamp
	 * @author Nitin Patel
	 */
	protected function makeFormula()
	{
		// new formula for time : $time = $retry*(pow(1.6,$retry))
		return function ($itemFailedCount) {
			return time()+ round( $itemFailedCount * (pow (1.6, $itemFailedCount)) * 60 );
		};
	}
}
