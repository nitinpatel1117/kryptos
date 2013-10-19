<?php

namespace Kryptos\KryptosBundle\Services;


class ConversionCalculator
{
	protected $configManager = null;
	protected $translator = null;
	
	
	public function __construct($configManager, $translator)
	{
		$this->configManager = $configManager;
		$this->translator = $translator;
	}
	
	
	
	public function calcRates($conversionAmount)
	{
		$data = array();
		
		$error = false;
		$error_msg = '';
		
		$conversionRate = $this->getConversionRate($conversionAmount);
		$vatRate = $this->configManager->get('purchase_conversions|vat_rate');
		 
		if (is_numeric($conversionAmount)) {
			$conversionAmount = (int) $conversionAmount;
		}else {
			$error = true;
			
			$error_msg = $this->translator->trans('msg_desc_conversions_must_be_number');
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
				
				$error_title 	= $this->translator->trans('msg_title_increase_conversions');
				$error_message 	= $this->translator->trans('msg_desc_increase_conversions', array('{{ currency }}' => $currency));
				
				$data['body']['error'] = $error_title.'|'.$error_message;
			}
			else if ($cost + $vat > 100000) {
				$currency = $this->configManager->get('sagepay|CurrencySymbol');
				$currency = utf8_encode(html_entity_decode($currency));
				
				$error_title 	= $this->translator->trans('msg_title_decrease_conversions');
				$error_message 	= $this->translator->trans('msg_desc_decrease_conversions', array('{{ currency }}' => $currency));
				
				$data['body']['error'] = $error_title.'|'.$error_message;
			}
		
		}
		else {
			$data['body'] = array('error' => $error_msg);
		}
		 
		return $data;
	}
	
	
	public function getConversionRate($conversionAmount)
	{
		$conversionRate = 1;
		
		$conversionType = $this->configManager->get('purchase_conversions|conversion_type');
		switch($conversionType) {
			case 'linear':
				$conversionRate = $this->getLinearConversionRate();
				break;
		
			case 'graph':
				$conversionRate = $this->getGraphConversionRate($conversionAmount);
				break;
		
			default:
				break;
		}
		
		return $conversionRate;
	}
	
	
	public function getLinearConversionRate()
	{
		return $this->configManager->get('purchase_conversions|conversion_rate');
	}
	
	
	public function getGraphConversionRate($conversionAmount)
	{
		$conversionRate = 1;
		
		$conversionBandRates = $this->configManager->get('purchase_conversions|conversion_band_rates');
		
		$lastRate = null;
		$rateFound = false;
		foreach ($conversionBandRates as $amount => $rate)
		{
			$lastRate = $rate;
			
			if ($conversionAmount <= (int) $amount) {
				$conversionRate = $rate;
				$rateFound = true;
				break;
			}
		}
		
		if (false == $rateFound) {
			$conversionRate = $lastRate;
		}
		
		return $conversionRate;
	}
	
	
}