<?php
namespace Kryptos\KryptosBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Kryptos\KryptosBundle\Lib\ExportTranslation\ExportTranslations;

class ExportTranslationsXliffCommand extends ContainerAwareCommand
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
This script exports translations from mongodb to xliff files.
EOF;
		
		$this
			->setName('kryptos:export-translations-xliff')
			->setDescription('Export translations to XLIFF format')
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
		$appPath 			= $this->getContainer()->getParameter('kernel.root_dir');

		$exportTranslations = new ExportTranslations($translationManager, $configManager, $appPath);
		$exportTranslations->setLogger($this->getLogger());
		$exportTranslations->run();
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
					$logger->err(sprintf('Error thrown by ExportTranslationsXliff in kryptos: %s', $message));
					break;
				default:
					$logger->info(sprintf('Log from ExportTranslationsXliff in kryptos: %s', $message));
					break;
			} 
		}
	}
}
