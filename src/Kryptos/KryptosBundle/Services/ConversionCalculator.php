<?php

namespace Kryptos\KryptosBundle\Services;


class ConversionCalculator
{
	protected $configManager = null;
	
	
	public function __construct($configManager)
	{
		$this->configManager = $configManager;
	}
	
	
	
	public function calcRates($conversionAmount)
	{
		$data = array();
		 
		$conversionRate = $this->configManager->get('purchase_conversions|conversion_rate');
		$vatRate = $this->configManager->get('purchase_conversions|vat_rate');
		 
		$error = false;
		$error_msg = '';
		 
		if (is_numeric($conversionAmount)) {
			$conversionAmount = (int) $conversionAmount;
		}else {
			$error = true;
			$error_msg = 'No. of Conversions must be entered as a number';
		}
		 
		if (is_numeric($conversionRate)) {
			$conversionRate = (float) $conversionRate;
		}else {
			$error = true;
		}
		 
		// make user VAT rate is between [0 - 100] inclusive
		if (is_numeric($vatRate)) {
			$vatRate = (float) $vatRate;
			if (0 > $vatRate || $vatRate > 100) {
				$error = true;
			}
		}else {
			$error = true;
		}
		 
		if (false == $error){
			$cost = round ($conversionAmount * $conversionRate, 2);
			$vat  = round ($cost * ($vatRate / 100), 2);
		
			$data['body'] = array('cost' => $cost, 'vat' => $vat);
			if (1 > $cost + $vat) {
				$currency = $this->configManager->get('sagepay|CurrencySymbol');
				$currency = utf8_encode(html_entity_decode($currency));
				$data['body']['error'] = sprintf('Increase conversions |Total cost is less than %s1. Please increase the No. of conversions to meet the minimum total of %s1', $currency, $currency);
			}
			else if ($cost + $vat > 100000) {
				$currency = $this->configManager->get('sagepay|CurrencySymbol');
				$currency = utf8_encode(html_entity_decode($currency));
				$data['body']['error'] = sprintf('Increase conversions |Total cost is greater than %s100,000. Please decrease the No. of conversions to meet the maximum expenture total of %s100,000', $currency, $currency);
			}
		
		}
		else {
			$data['body'] = array('error' => $error_msg);
		}
		 
		return $data;
	}
	
	
}