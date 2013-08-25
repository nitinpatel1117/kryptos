<?php
namespace Kryptos\ServiceBundle\Controller;

use Kryptos\ServiceBundle\Lib\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
	/**
	 * a variable to flag whether we are paging the response
	 */
	protected $paging = false;

	/**
	 * A variable to hold the total number of records for a given query
	 * @var Int
	 */
	protected $totalRecords = null;

	protected $logger = null;

	protected $page = null;

	protected $rpp = null;

	protected $extraDiagnostics = array();

	protected $cacheTime = 0;
	
	protected $response = null;

	// TODO: Do this as a listener in future. Listen in on the kernal 
	public function __construct()
	{
		$pageDefault           = 1;
		$resultPerPageDefault  = 10;

		$request = Request::createFromGlobals();
		$this->page = $request->query->get('page', $pageDefault);
		$this->rpp = $request->query->get('rpp', $resultPerPageDefault);

		// 'page' should never be less than 1
		if (1 > $this->page || !is_numeric($this->page)) {
			$this->page = $pageDefault;
		}

		// 'results per page' should never be less than 1 or greater than 100
		if (1 > $this->rpp || !is_numeric($this->rpp) || $this->rpp > 100) {
			$this->rpp = $resultPerPageDefault;
		}

		
	
		
		$this->setApiResponse(new ApiResponse());
		$this->getApiResponse()->setPage((int) $this->page);
		$this->getApiResponse()->setRecordsPerPage((int) $this->rpp);
	}

	
	public function getCacheTime()
	{
		return $this->cacheTime;
	}

	public function setCacheTime($cacheTime)
	{
		$this->cacheTime = $cacheTime;
	}

	private function setResponse(Response $response)
	{
		$this->response = $response;
	}
	
	private function getResponse()
	{
		return $this->response;
	}	

	protected function getApiResponse()
	{
		return $this->api_response;
	}
	
	protected function setApiResponse(APIResponse $response)
	{
		$this->api_response = $response;
	}	
	

	public function getJsonResponse($content, $singleItem = false)
	{
		if (empty($content) && $this->getApiResponse()->noErrors()) {
			$this->getApiResponse()->error('Data could not be found.', 404);
		}
	
		if ($this->getApiResponse()->noErrors()) {
			$this->getApiResponse()->body = $content;
			$this->getApiResponse()->calculatePagingData($singleItem);

			if (isset($this->totalRecords) && !empty($this->totalRecords)) {
				$this->getApiResponse()->total_records = $this->totalRecords;
			}
		}
		
		$this->setResponse(new Response($this->getApiResponse()->create()));
		$this->setResponseHeaders();
		return $this->getResponse();
	}


	/**
	 * Set response headers
	 * 
	 */
	private function setResponseHeaders()
	{
		$this->getResponse()->setStatusCode($this->getApiResponse()->code);
		$this->getResponse()->headers->set('Content-Type', 'application/json');
	}

	
	/**
	 * Set Response Cache header
	 * 
	 *
	private function setResponseCacheHeaders()
	{
		$this->getResponse()->setETag($this->getApiResponse()->computeETag());

		if($this->getApiResponse()->isLastUpdatedAvailable()) {
			$this->getResponse()->setLastModified(new \DateTime($this->getApiResponse()->getLastUpdated()));
		}

		if ($this->getCacheTime()) {
			$cacheTime = $this->getCacheTime();
		} else {
			// TODO: Check why we are setting to 500 here
			$cacheTime = 500;
		}
		
		$this->setNotModified();
		$this->cacheFor($cacheTime);
	}
	/**
	 * Check if request scope is availble and call Response::isNotModified
	 * 
	 *
	public function setNotModified()
	{
		// Need to check the scope as some of our tests bypass the request 
		// e.g.: fullRunBadSnippet
		if($this->container AND $this->container->isScopeActive("request"))
		{
			$this->getResponse()->isNotModified($this->getRequest());
		}
	}
	/**
	 * 
	 * Force no cache / revalidade headers
	 *
	public function noCache()
	{
		// set a custom Cache-Control directive
		$date = new \DateTime();
		$date->modify('-1 year');
		$response->setExpires($date);
		$this->getResponse()->headers->addCacheControlDirective('must-revalidate', true);
		$this->getResponse()->headers->addCacheControlDirective('no-cache', true);
	}
	/**
	 * Set as expires and max-age based on $seconds
	 * @param $seconds
	 *
	public function cacheFor($seconds)
	{
		// set a custom Cache-Control directive
		$date = new \DateTime();
		$date->modify('+' . $seconds . ' seconds');
		$this->getResponse()->setPublic();
		$this->getResponse()->setExpires($date);
		$this->getResponse()->headers->addCacheControlDirective('max-age', $seconds);
	}*/
	


	/**
	 * Function takes an array and returns a paged subset of the data
	 */
	public function getPagedData($data)
	{
		$pagedData = array();
		if (is_array($data) && !empty($data))
		{
			$this->paging = true;
			$counter = 0;
			$startPosition = $this->getStartPosition();
			$endPosition = $startPosition + $this->rpp;

			foreach ($data as $key => $value)
			{
				if ($startPosition <= $counter && $counter < $endPosition )
				{
					$pagedData[] = $value;
				}
				$counter++;
			}
		}
		else {
			$pagedData = $data;
		}

		return $pagedData;    	
	}


	/**
	 * Function calculates the start position of the pointer, so that we only return data from that point onwards
	 * 
	 * @return Integer		The start location 
	 */
	public function getStartPosition()
	{
		return  ($this->page - 1) * $this->rpp;
	}

	public function setLogger() {
		if(is_null($this->getLogger()))
		{
			$this->logger = $this->get('logger');
		}
	}

	public function getLogger() {
		return $this->logger;
	}

	/**
	 * Function allows us to log an message
	 *
	 * @param string $type				The type of msg that we want to log. e.g. if its an error
	 * @param String $message 			The message that needs to be logged
	 */
	public function log($type, $message)
	{
		// WARNING: It doesn't work if we setLogger() in the constructor
		// TODO: Add listener
		$this->setLogger();
		if (!is_null($this->getLogger())) {
			switch(strtolower($type))
			{
				case 'error':
					$this->getLogger()->err(sprintf('{Action} Error thrown by EndPoint: %s', $message));
					break;
				default:
					$this->getLogger()->info(sprintf('{Action} Info from EndPoint: %s', $message));
					break;
			}
		}
	}


	/**
	 * Setter functions to allow us to do full coverage on unit test
	 */
	public function setRPP($rpp) {
		$this->rpp = $rpp;
	}
	public function setPage($page) {
		$this->page = $page;
	}


}
