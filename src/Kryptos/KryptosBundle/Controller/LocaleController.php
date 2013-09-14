<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Kryptos\ServiceBundle\Lib\ApiResponse;

class LocaleController extends Controller
{
	public function switchAction(Request $request)
	{
		$selectedLocale = $request->getLocale();
		$this->get('locale_switcher')->setLocale($selectedLocale);
		
		// default to fail, internal server 500 error
		$status = 'failed';
		$statusCode = '500';
		
		// the selected locale matches whats in the session
		if ($selectedLocale === $this->get('locale_switcher')->getLocale()) {
			$status = 'success';
			$statusCode = '200';
		}
		
		
		$responseData = array(
			'body' => array(
				'status' => $status,
				'locale' => $request->getLocale(),
			),
		); 
		
		$response  = new Response(json_encode($responseData));
		$response->setStatusCode($statusCode);
		$response->headers->set('Content-Type', 'application/json');
		
		return $response;
	}
}