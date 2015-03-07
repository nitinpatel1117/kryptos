<?php
namespace Kryptos\ServiceBundle\Lib;

use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
	public $status = 'success';
	public $code = 200;
	public $execution_time = null;
	public $message = '';
	public $records_returned = 0;
	public $page = 1;
	public $records_per_page = null;
	public $total_records = 0;
	public $extraDiagnostics = array();	
	public $body = array();


	protected $startTime = null;
	protected $endTime = null;
	protected $response = null;



	public function __construct()
	{
		$this->setStartTime(microtime(true));
	}


	/**
	 * Setters and getters for allowing us to have full unittest code coverage
	 */
	public function setStartTime($startTime) {
		$this->startTime = $startTime;
	}
	public function getStartTime() {
		return $this->startTime;
	}
	public function setEndTime($endTime) {
		$this->endTime = $endTime;
	}
	public function getEndTime() {
		return $this->endTime;
	}
	public function setRecordsPerPage($recordsPerPage) {
		$this->records_per_page = $recordsPerPage;
	}
	public function getRecordsPerPage() {
		return $this->records_per_page;
	}
	public function setPage($page) {
		$this->page = $page;
	}
	public function getPage() {
		return $this->page;
	}

	protected function getResponse()
	{
		return $this->response;
	}
	
	protected function setResponse($response)
	{
		$this->response = $response;
	}

	protected function getResponseBody()
	{
		return $this->response['body'];
	}
	
	/**
	 * Create the response structure, return json by default.
	 * 
	 * @param boolean $json 		Return json or array.
	 * @return mixed Json string or array.
	 */
	public function create($json = true)
	{
		$response = array(
			'diagnostics' => array(
				'status'			=> $this->status,
				'code'				=> $this->code,
				'execution_time'	=> $this->calcExecutionTime(),
				'api_message'		=> $this->message,
		#		'records_returned'	=> $this->records_returned,
		#		'page'				=> $this->page,
		#		'records_per_page'	=> $this->records_per_page,
		#		'total_records'		=> $this->total_records,
			),
			'body'=> $this->body,
		);

		$this->setResponse($response);
		
		return $json ? json_encode($this->getResponse()) : $this->getResponse();
	}


	/**
	 * Function makes sure that we are returning the right amount of data in our response. Based on the records per page and paging values.
	 * @param boolean singleItem 	Determines whether we are returning a single item or an array of items
	 */
	public function calculatePagingData($singleItem = false)
	{
		if ($singleItem == false)
		{
			$this->records_returned = count($this->body);
		}
		else {
			// if are returning a single item,  bypass the paging calculations
			$this->records_returned = 1;
			$this->page = 1;
			$this->total_records = 1;
		}
	}


	/**
	 * Function calculates the execution time so far. Time is returned in milliseconds
	 * 
	 * @return Integer
	 */
	public function calcExecutionTime()
	{
		$this->setEndTime(microtime(true));
		return round(($this->getEndTime() - $this->getStartTime()) * 1000);
	}


	/**
	 * Function adds an error to the API response
	 * 
	 * @param String $message		The message for this error
	 * @param Integer $code				The status code fo this error message
	 */
	public function error($message, $code = 500)
	{
		$this->status = 'error';
		$this->code = $code;
		if (!empty($this->message)) {
			$this->message .= ' ';
		}
		$this->message .= $message;
	}


	/**
	 * Function checks whether an error has been added to the API respinse.
	 * returns true if no errors have been logged in the response status
	 * 
	 * @return Boolean
	 */
	public function noErrors()
	{
		if ($this->status != 'error') {
			return true;
		}
		return false;
	}
	
	/**
	 * Generates a md5 of the object being returned
	 * 
	 * @return string
	 */
	public function computeETag()
	{
		return md5(json_encode($this->getResponseBody()));
	}
}
