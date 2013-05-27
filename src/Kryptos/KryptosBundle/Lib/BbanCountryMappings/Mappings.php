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
	
	
	
	
	
	/**
	 * The mapping of each country code to its bban fields
	 *
	 * @var array
	 */
	protected $conversionBankDetailsMappings = array(
		'AD' => array(
			'bank_name' 	=> array(1003),
			'bank_address'	=> array(1006),
			'branch_name'	=> array(1007),
			'post_code'		=> array(1008),
			'city'			=> array(1009),
			'location'		=> array(1010),
			'country'		=> array(1011),
		),
		'AT' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
	
		'BE' => array(
			'bank_name' 	=> array(1002, 1003),
			'bank_address'	=> array(1004, 1005),
			'branch_name'	=> array(),
			'post_code'		=> array(1006),
			'city'			=> array(1009, 1010),
			'location'		=> array(),
			'country'		=> array(1011),
		),
		'BA' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'BG' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
	
		),
	
		'HR' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		
		'CY' => array(
			'bank_name' 	=> array(1003),
			'bank_address'	=> array(1006),
			'branch_name'	=> array(1007),
			'post_code'		=> array(1008),
			'city'			=> array(1009),
			'location'		=> array(1010),
			'country'		=> array(1011),
		),
		'CZ' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
	
		'DK' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
	
		'EE' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
	
		'FI' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'FR' => array(
			'bank_name' 	=> array(1004),
			'bank_address'	=> array(1013, 1014, 1015),
			'branch_name'	=> array(1005),
			'post_code'		=> array(1016),
			'city'			=> array(),
			'location'		=> array(),
			'country'		=> array(),
		),
	
		'DE' => array(
			'bank_name' 	=> array(1007),
			'bank_address'	=> array(),
			'branch_name'	=> array(1007),
			'post_code'		=> array(1009),
			'city'			=> array(1010),
			'location'		=> array(),
			'country'		=> array(),
		),
		'GR' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
	
		'HU' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
	
		'IS' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'IT' => array(
			'bank_name' 	=> array(1019),
			'bank_address'	=> array(1003, 1004),
			'branch_name'	=> array(1009),
			'post_code'		=> array(1006),
			'city'			=> array(1005),
			'location'		=> array(1007, 1008),
			'country'		=> array(),
		),
	
		'LV' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'LI' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'LT' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'LU' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
	
		'MT' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'ME' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
	
		'NL' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'NO' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
	
		'PL' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'PT' => array(
			'bank_name' 	=> array(1003),
			'bank_address'	=> array(1006),
			'branch_name'	=> array(1007),
			'post_code'		=> array(1008),
			'city'			=> array(1009),
			'location'		=> array(1010),
			'country'		=> array(1011),
		),
	
		'IE' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1004, 1005, 1006, 1007),
			'branch_name'	=> array(1003),
			'post_code'		=> array(),
			'city'			=> array(),
			'location'		=> array(),
			'country'		=> array(),
		),
		'RO' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
	
		'RS' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'SK' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'SI' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'ES' => array(
			'bank_name' 	=> array(1003),
			'bank_address'	=> array(1006),
			'branch_name'	=> array(1007),
			'post_code'		=> array(1008),
			'city'			=> array(1009),
			'location'		=> array(1010),
			'country'		=> array(1011),
		),
		'SE' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
		'CH' => array(
			'bank_name' 	=> array(1002),
			'bank_address'	=> array(1005),
			'branch_name'	=> array(1006),
			'post_code'		=> array(1007),
			'city'			=> array(1008),
			'location'		=> array(1009),
			'country'		=> array(1010),
		),
	
		'TN' => array(
			'bank_name' 	=> array(),
			'bank_address'	=> array(),
			'branch_name'	=> array(),
			'post_code'		=> array(),
			'city'			=> array(),
			'location'		=> array(),
			'country'		=> array(),
		),
	
		'GB' => array(
			'bank_name' 	=> array(1007, 1008),
			'bank_address'	=> array(1059, 1060, 1061, 1062),
			'branch_name'	=> array(1005),
			'post_code'		=> array(1065, 1066),
			'city'			=> array(1063),
			'location'		=> array(1051),
			'country'		=> array(),
		),
	);
	
	
	public function getConversionBankDetailsMappings($countryCode = null)
	{
		$data = $this->conversionBankDetailsMappings;
	
		if (!is_null($countryCode)) {
			$data = null;
			if (isset($this->conversionBankDetailsMappings[$countryCode])) {
				$data = $this->conversionBankDetailsMappings[$countryCode];
			}
		}
	
		return $data;
	}
	
}