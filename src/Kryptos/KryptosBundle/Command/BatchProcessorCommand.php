<?php
namespace Kryptos\KryptosBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Kryptos\KryptosBundle\Lib\BatchProcessor\BatchProcessor;

class BatchProcessorCommand extends ContainerAwareCommand
{
	// the maximum time that we want to allow this script to sleep for
	const MAX_SLEEPTIME = '3600';
	const MEMORY_ALARM = 64000000;
	const EXIT_CODE_TOO_MUCH_MEMORY = 99;

	protected $logger = null;

	/**
	 * @codeCoverageIgnore
	 */
	protected function configure()
	{
		$help =<<<EOF
This script retrieves items from the queue. Every item that is retrieved is processed.

The processing of an item consists of it being passed to a Roller process
Once the detailed data has been retrieved for that item, it is converted and stored in a MongoDB database.

This script can be disabled by passing '--disable=true' as an option in the command line.


=================
Script throttling

This script will run as a continuous daemon process. i.e. it will restart as soon as it has finished executing.
For this reason we have created options to allow us to control the speed that this script runs at.

It is intened that this script will read (x) amount of entries from the queue. (x) will be determined by the option 'read_from_queue'.

The script will run keep running while we have entries in the queue. 
Once the queue is empty we will wait (y) amount of seconds. (y) will be determined by the option 'wait_when_queue_empty'.

The call to retrieve items from the queue is done at the start of the script. If the queue is very large (thousands of entries) 
the script will keeping executing until the queue has no more entries in it. This can potentially add alot of load on our systems.
Therefore, in order to control this load we have created an option called 'wait_inbetween_queue_calls' that will purposely cause the script
to wait after execution when we still have items in the queue. An example of when this may be neccessay is when we do an initial import 
of a project.
EOF;
		
		$this
			->setName('kryptos:batch-processor')
			->setDescription('Reads entries from MongoDB, find batch files to process and send them to the bank wizard batch processors')
			->addOption('read_from_queue', 				null, InputOption::VALUE_OPTIONAL, 'The amount of entries that should be read from the queue at any one time.', 1)
			->addOption('wait_when_queue_empty', 		null, InputOption::VALUE_OPTIONAL, 'The time in seconds that the script should sleep for when the queue is empty.', 20)
			->addOption('wait_inbetween_queue_calls', 	null, InputOption::VALUE_OPTIONAL, 'The time in seconds that the script should sleep inbetween calls to the queue.', 0)
			->addOption('execute_once', 				null, null, 'Run script only once (not as a daemon)')
#			->addOption('domain_id',  null, InputOption::VALUE_OPTIONAL, 'Which domain ID, if any, to limit this process to.')
#			->addOption('object_ids',  null, InputOption::VALUE_OPTIONAL, 'Which object IDs, if any, to limit this process to. CSV (1,2,3) and range (1-100,200-300) formats are accepted.')
#			->addOption('object_type',  null, InputOption::VALUE_OPTIONAL, 'Which object type, if any, to limit this process to.')	
#			->addOption('consumer',  null, InputOption::VALUE_OPTIONAL, 'Consumer unique number.', 1)		
			->addOption('children', null, InputOption::VALUE_OPTIONAL, 'How many workers to spawn?', 1)
			->addOption('safe_mode', null, InputOption::VALUE_NONE, 'If set, will run in safe mode. Children only process one object, only one child can exist at once.')
			
			->setHelp($help)
		;
	}

	
	public function setInput($input)
	{
		$this->input = $input;
	}

	public function getInput()
	{
		return $this->input;
	}	

	public function setOutput($output)
	{
		$this->output = $output;		
	}
	
	public function getOutput()
	{
		return $this->output;
	}

	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// get the logger from the container and set it...
		if ($this->getContainer()) {
			$this->setLogger($this->getContainer()->get('logger'));
		}
		if ($input->getOption('env') != 'cli') {
			$output->writeln('Must be run in the cli environment, try --env=cli');
			return;
		}
		// make the PID file...
		$pidFileName = 'kryptos-batchProcessor.pid';
		$pidFileName = $this->getContainer()->get('kernel')->getRootDir() . '/pid/' . $pidFileName;
		file_put_contents($pidFileName, posix_getpid());
		
		/*
		 * Problem: For unknown reasons this whole process gobbles up ram and doesn't release it
		 *
		 * Solution: Do some forking of the code, when it runs out of memory it will detect that and exit.
		 * Then, in the parent, we spawn some more children. Id's a spawning-pit/breeding-ground of errant
		 * run-away brain damaged children.
		 */
		$children = array();
		do {
			$childrenToSpawn = $input->getOption('children');
			if ($input->getOption('safe_mode')) {
				$childrenToSpawn = 1;
			}
			
			if (count($children) < $childrenToSpawn) {
				$childPID = pcntl_fork();
				$children[] = $childPID;
				if ($childPID == 0) {
					$this->log('info', sprintf('BatchProcessorCommand: I am a new child, PID = %s.', posix_getpid()));
					// I am the child, best do some work...
					$this->setInput($input);
					$this->setOutput($output);
					$this->processQueue();
					// the above will never terminate unless we hit a fatal (or run out of memory)
					// exit() to make this look nice...
					exit();
				}
				$this->log('info', sprintf('BatchProcessorCommand: Parent (PID = %s) spawned a new child (PID = %s)', posix_getpid(), $childPID));
			} else {
				$this->log('info', sprintf('BatchProcessorCommand: Parent - spawed enough children, waiting for one of them to do something...'));
				$deadPID = pcntl_wait($status);
				$this->log('info', sprintf('BatchProcessorCommand: Parent (PID = %s) - child (PID = %s) exited with code %s', posix_getpid(), $deadPID, pcntl_wexitstatus($status)));
				$children = array_diff($children, array($deadPID));
			}
		} while (true);
		// } while (pcntl_wexitstatus($status) == self::EXIT_CODE_TOO_MUCH_MEMORY);
		// todo:  think we should exit here, or the code block below should be contained in the else {} of this if, No time to investigate now...
		exit();	
	}
	
	
	/**
	 * Recursive function to process the queue
	 * TODO: make it daemonize-able !!
	 */
	protected function processQueue()
	{
		$readFromQueue 			 = $this->input->getOption('read_from_queue');
		$waitWhenQueueEmpty 	 = $this->input->getOption('wait_when_queue_empty');
		$waitInbetweenQueueCalls = $this->input->getOption('wait_inbetween_queue_calls');
		$executeOnce			 = $this->input->getOption('execute_once');

		do {
			if ($this->input->getOption('safe_mode')) {
				$readFromQueue = 1;
			}
			
			$fileManager = $this->getContainer()->get('file_manager');
			$userManager = $this->getContainer()->get('user_manager');
			$configManager = $this->getContainer()->get('config_manager');
			$batchProcessor = new BatchProcessor($fileManager, $userManager, $configManager, $readFromQueue);
			$batchProcessor->setLogger($this->getLogger());
			$batchProcessor->run();
			
			$itemsRead = $batchProcessor->itemsReadFromQueue();
			if ($readFromQueue == $itemsRead)
			{
				// we have more items in the queue, therefore allow the script to run, unless we have a value for 'wait_inbetween_queue_calls'
				$this->sleep($waitInbetweenQueueCalls);
			}
			else {
				$this->sleep($waitWhenQueueEmpty);
			}
			unset($batchProcessor);
			if (!$executeOnce && memory_get_usage() > self::MEMORY_ALARM) {
				exit(self::EXIT_CODE_TOO_MUCH_MEMORY);
			}
			if ($this->input->getOption('safe_mode')) {
				exit();
			}
		} while($executeOnce===false);
	}
	
	/**
	 * This function initiates a sleep and checks whether the sleep duration is passed as an Integer
	 * 
	 * @param Integer/String $secondsToSleep
	 * @return void
	 * @author Nitin Patel
	 */
	public function sleep($secondsToSleep)
	{		
		if (is_string($secondsToSleep) && is_numeric($secondsToSleep)) {
			$secondsToSleep = (int) $secondsToSleep;
		}
		
		if (is_int($secondsToSleep))
		{	
			// make sure that an extremely long sleep time hasn't been set. Limit to our pre-defined max value
			$secondsToSleep = $this->checkSleepLength($secondsToSleep);
			
			// we want to avoid logging zero second sleeps
			if (0 == $secondsToSleep) {
				return; 
			}
			
			$this->log('info', sprintf('BatchProcessorCommand sleeping for %s seconds.', $secondsToSleep ));
			sleep($secondsToSleep);
			$this->log('info', sprintf('BatchProcessorCommand finished sleeping'));
		}	
	}
	
	
	/**
	 * Function checks that the sleep length is not greater than a prefined value
	 * 
	 * @param integer $secondsToSleep		The number of seconds to sleep for
	 * @return integer
	 * @author Nitin Patel
	 */
	public function checkSleepLength($secondsToSleep)
	{
		if ($secondsToSleep > self::MAX_SLEEPTIME) {
			$secondsToSleep = self::MAX_SLEEPTIME;
		}
		
		return $secondsToSleep;
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
					$logger->err(sprintf('Error thrown by BatchProcessor in kryptos: %s', $message));
					break;
				default:
					$logger->info(sprintf('Log from BatchProcessor in kryptos: %s', $message));
					break;
			} 
		}
	}
}
