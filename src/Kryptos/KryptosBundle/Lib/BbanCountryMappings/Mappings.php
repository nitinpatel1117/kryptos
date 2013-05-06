<?php
namespace Kryptos\KryptosBundle\Lib\BbanCountryMappings;


/**
 * This class is used on the single conversion page.
 * 
 * Its main purpose is to provide config data, suuch as the country codes and what BBANS
 * are used in which country.
 * 
 * WARNING: The country listings in this class are not to be used as the country listing that are used by the payment gateway
 * 
 * @author Nitin
 *
 */
class Mappings
{
	/**
	 * Expected columns in each row
	 * 
	 * @var array
	 */
	protected $countries = array(
		'AL' => 'Albania',
		'AD' => 'Andorra',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BH' => 'Bahrain',
		'BE' => 'Belgium',
		'GB' => 'United Kingdom',
		'VG' => 'Virgin Islands, British',
	);
	
	
	/**
	 * Variable to store bank account numbers while they are being read. 
	 * We store 500 entries at a time, before batch saving. 
	 * 
	 * @var array
	 */
	protected $bbanMappings = array(
		'AL' => array(
			'bban1' => 'Bank Code',
			'bban2' => 'Branch code',
			'bban3' => 'Check Digit',
			'bban4' => 'Account number',
		),
		'AD' => array(
			'bban1' => 'bban1',
			'bban2' => 'bban2',
			'bban3' => 'bban3',
		),
		'AT' => array(
			'bban1' => 'Bank Code',
			'bban2' => 'Account number',
		),
		'AZ' => array(
			'bban1' => 'bban1',
			'bban2' => 'bban2',
		),
		'BH' => array(
			'bban1' => 'bban1',
			'bban2' => 'bban2',
		),
		'BE' => array(
			'bban1' => 'bban1',
			'bban2' => 'bban2',
			'bban3' => 'bban3',
			'bban4' => 'bban4',
		),
		'GB' => array(
			'bban1' => 'Sort Code',
			'bban2' => 'Account Number',
		),
		'VG' => array(
			'bban1' => 'bban1',
			'bban2' => 'bban2',
			'bban3' => 'bban3',
		),
	);

	
	
	
	public function getCountries()
	{
		return $this->countries;
	}
	
	
	public function getBbanMappings($countryCode = null)
	{
		$data = $this->bbanMappings;
		
		if (!is_null($countryCode)) {
			$data = null;
			if (isset($this->bbanMappings[$countryCode])) {
				$data = $this->bbanMappings[$countryCode];
			}
		}
		
		return $data;
	}
}