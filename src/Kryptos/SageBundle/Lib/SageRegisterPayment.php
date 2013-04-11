<?php
namespace Kryptos\SageBundle\Lib;

use Kryptos\SageBundle\Lib\HttpGetter;

class SageRegisterPayment
{
	protected $config_manager; 
	
	
	public function __construct($config_manager)
	{
		$this->config_manager = $config_manager;
	}
	

	public function makePaymentParameters($data, $user, $notificationURL)
	{
		$mongoId = new \MongoId();
		$id = $mongoId->__toString();

		$data['_id']				= $mongoId;
		$data['action']				= 'inprogress';
		$data['VPSProtocol'] 		= $this->config_manager->get('sagepay|VPSProtocol');
		$data['TxType'] 			= 'PAYMENT';
		$data['Vendor'] 			= $this->config_manager->get('sagepay|Vendor');
		$data['VendorTxCode'] 		= $id;
		$data['Amount'] 			= number_format($user['currentTrans']['total'], 2, '.', '');
		$data['Currency'] 			= $this->config_manager->get('sagepay|Currency');
		$data['Description'] 		= $this->config_manager->get('sagepay|Description');
		$data['NotificationURL'] 	= $notificationURL;
		
		if (!isset($user['payment'])) {
			$user['payment'] = array();
		}
		
		if (!isset($user['credits'])) {
			$user['credits'] = $this->config_manager->get('credits');
		}
		
		$dataForUser = $data;
		$dataForUser['purchase'] = $user['currentTrans'];
		$dataForUser['creditsOld'] = $user['credits'];
		$dataForUser['started'] = new \MongoDate();
		
		$user['payment'][] = $dataForUser;
		
		
		return array($data, $user);
	}
	
	
	public function registerPayment($data)
	{
		$url = $this->config_manager->get('sagepay|PaymentUrl');
		$postParams = $this->makePostString($data);
		
		$client = new HttpGetter();
		$client->setUrl($url);
		$client->execute($postParams);
		
		return $client->getResponse();
	}
	
	
	public function makePostString($post)
	{
		$postUrl = '';
		$postPairs = array();
		foreach ($post as $key => $value) {
			$postPairs[] = sprintf('%s=%s', $key, urlencode($value));
		}
		$postUrl = implode('&', $postPairs);
		return $postUrl;
	}
	
	
	public function processResult($sageResult)
	{
		$response = array();
		
		$sageResultLines = explode("\n", $sageResult);
		foreach($sageResultLines as $sageResultLine) {
			// if line is not empty and contains the '=' character
			if (!empty($sageResultLine) && (strpos($sageResultLine, '=') !== false)) {
				list($key, $value) = explode("=", $sageResultLine, 2);
				$response[$key] = trim($value);
			}
		}
		
		return $response;
	}
    		
}