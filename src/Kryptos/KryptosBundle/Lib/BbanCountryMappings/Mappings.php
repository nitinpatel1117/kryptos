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
	 * The mapping of each country code to its full country name
	 * 
	 * @var array
	 */
	protected $countries = array(
	#	'AL' => 'Albania',
		'AD' => 'Andorra',
		'AT' => 'Austria',
	#	'AZ' => 'Azerbaijan',
	
	#	'BH' => 'Bahrain',
		'BE' => 'Belgium',
		'BA' => 'Bosnia and Herzegovina',
		'BG' => 'Bulgaria',
		
		'HR' => 'Croatia',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		
		'DK' => 'Denmark',
		
		'EE' => 'Estonia',
		
		'FI' => 'Finland',
		'FR' => 'France',

		'DE' => 'Germany',
		'GR' => 'Greece',
		
		'HU' => 'Hungary',
		
		'IS' => 'Iceland',
		'IT' => 'Italy',
		
		'LV' => 'Latvia',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		
		'MT' => 'Malta',
		'ME' => 'Montenegro',
		
		'NL' => 'The Netherlands',
		'NO' => 'Norway',

		'PL' => 'Poland',
		'PT' => 'Portugal',
		
		'IE' => 'Republic of Ireland',
		'RO' => 'Romania',
		
		'RS' => 'Serbia',
		'SK' => 'Slovakia',
		'SI' => 'Slovenia',
		'ES' => 'Spain',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		
		'TN' => 'Tunisia',
		
		'GB' => 'United Kingdom',
		
		#'VG' => 'Virgin Islands, British',
	);
	
	
	/**
	 * The mapping of each country code to its bban fields
	 * 
	 * @var array
	 */
	protected $bbanMappings = array(
		'AD' => array(
			'bban1' => 'Bank code',
			'bban2' => 'Branch code',
			'bban3' => 'Account no.',
		),
		'AT' => array(
			'bban1' => 'Account No.',
			'bban2' => 'BLZ',
		),
		
		'BE' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
			'bban3' => 'Check digits',
		),
		'BA' => array(
			'bban1' => 'Bank code',
			'bban2' => 'Branch code',
			'bban3' => 'Account no.',
			'bban4' => 'Check digit',
		),
		'BG' => array(
			'bban1' => 'Account no.',

		),
		
		'HR' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
		),
		'CY' => array(
			'bban1' => 'Bank code',
			'bban2' => 'Branch code',
			'bban3' => 'Account no.',
		),
		'CZ' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Prefix',
			'bban3' => 'Basic',
		),
		
		'DK' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
		),
		
		'EE' => array(
			'bban1' => 'Bank code',
			'bban2' => 'Account no.',
		),
		
		'FI' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
		),
		'FR' => array(
			'bban1' => 'Bank code',
			'bban2' => 'Branch code',
			'bban3' => 'Account no.',
			'bban4' => 'Check digit',
		),
		
		'DE' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
		),
		'GR' => array(
			'bban1' => 'Bank code',
			'bban2' => 'Branch code',
			'bban3' => 'Account no.',
		),
		
		'HU' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account part 1',
			'bban3' => 'Account part 2',
		),
		
		'IS' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account type',
			'bban3' => 'Account no.',
			'bban4' => 'Identification no.',
		),
		'IT' => array(
			'bban1' => 'Bank code',
			'bban2' => 'Branch code',
			'bban3' => 'Account no.',
			'bban4' => 'Check digit',
		),
		
		'LV' => array(
			'bban1' => 'Account no.',
		),
		'LI' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
		),
		'LT' => array(
			'bban1' => 'Account no.',
		),
		'LU' => array(
			'bban1' => 'Account no.',
		),
		
		'MT' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
		),
		'ME' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
			'bban3' => 'Check digit',
		),
		
		'NL' => array(
			'bban1' => 'Bank identifier',
			'bban2' => 'Account no.',
		),
		'NO' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
		),
		
		'PL' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
			'bban3' => 'Check digit',
		),
		'PT' => array(
			'bban1' => 'Bank code',
			'bban2' => 'Branch code',
			'bban3' => 'Account no.',
			'bban4' => 'Check digit',
		),
		
		'IE' => array(
			'bban1' => 'Sort code',
			'bban2' => 'Account no.',
		),
		'RO' => array(
			'bban1' => 'Account no.',
		),
		
		'RS' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
			'bban3' => 'Check digit',
		),
		'SK' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Prefix',
			'bban3' => 'Basic',
		),
		'SI' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
		),
		'ES' => array(
			'bban1' => 'Bank code',
			'bban2' => 'Branch code',
			'bban3' => 'Account no.',
			'bban4' => 'Check digit',
		),
		'SE' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
		),
		'CH' => array(
			'bban1' => 'Bank-branch code',
			'bban2' => 'Account no.',
		),
		
		'TN' => array(
			'bban1' => 'Bank code',
			'bban2' => 'Branch code',
			'bban3' => 'Account no.',
			'bban4' => 'Check digit',
		),
		
		'GB' => array(
			'bban1' => 'Sort Code',
			'bban2' => 'Account no.',
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