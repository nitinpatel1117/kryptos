<?php
namespace Kryptos\ServiceBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Kryptos\ServiceBundle\Lib\CreateDummyFiles;

class CreateDummyFilesCommand extends ContainerAwareCommand
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
This script creates dummy service files from iban and bban details that are provided from a CSV file declared in the --file parameter.
The systems curls the frontend API and stores the results in the output dummy directory.
The --drop_data=true parameter can be used to remove all existing dummy files.
		
== Usage ==
app/console kryptos:create-dummy-files --file="/var/www/vhosts/kryptossystems.com/sepa.kryptossystems.com/upload/dummy_service.csv"  --env=cli
		
EOF;
		
		$this
			->setName('kryptos:create-dummy-files')
			->setDescription('Import dummy service files from an CSV import of iban and bban details')
			->addOption('file', 		null, InputOption::VALUE_OPTIONAL, 'The file that we want to read from', '')
			->addOption('drop_data', 	null, InputOption::VALUE_OPTIONAL, 'Flag to determine whether all existing dummy files should be deleted', false)
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
		
		$this->setInput($input);
		$this->processImport();
	}
	
	
	
	protected function processImport()
	{
		$translationManager = $this->getContainer()->get('translation_manager');
		$configManager 		= $this->getContainer()->get('config_manager');
		$file 				= $this->getInput()->getOption('file');
		$dropData 			= $this->getInput()->getOption('drop_data');
		
		$dummyFiles = new CreateDummyFiles($translationManager, $configManager, $file, $dropData);
		$dummyFiles->setLogger($this->getLogger());
		$dummyFiles->run();
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
					$logger->err(sprintf('Error thrown by CreateDummyFiles in Kryptos service bundle: %s', $message));
					break;
				default:
					$logger->info(sprintf('Log from CreateDummyFiles in Kryptos service bundle: %s', $message));
					break;
			} 
		}
	}
}
