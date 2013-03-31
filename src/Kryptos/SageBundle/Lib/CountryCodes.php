<?php

namespace Kryptos\SageBundle\Lib;

class CountryCodes
{
    protected $countryCodes = array(
		'AT' => 'Austria',
		'BE' => 'Belgium',
		'BG' => 'Bulgaria',
		'HR' => 'Croatia',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'FI' => 'Finland',
		'FR' => 'France',
		'DE' => 'Germany',
		'GR' => 'Greece',
		'GG' => 'Guernsey',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IE' => 'Ireland',
		'IM' => 'Isle Of Man',
		'IT' => 'Italy',
		'JE' => 'Jersey',
		'LV' => 'Latvia',
		'LU' => 'Luxembourg',
		'MT' => 'Malta',
		'MC' => 'Monaco',
		'NL' => 'Netherlands',
		'NO' => 'Norway',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'RO' => 'Romania',
		'SM' => 'San Marino',
		'ES' => 'Spain',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'TR' => 'Turkey',
		'UA' => 'Ukraine',
		'GB' => 'United Kingdom',
		'US' => 'United States',
		'VG' => 'Virgin Islands, British',
    );
    
    protected $countryCodesTop = array(
    	'GB' => 'United Kingdom',
    	'US' => 'United States',
    );
    
    

    public function __construct()
    {
    	return $this;
    }
   
    public function getList()
    {
        return $this->countryCodes;
    }
    
    public function getTopList()
    {
    	return $this->countryCodesTop;
    }
    
    
    public function isValid($code)
    {
    	return in_array($code, array_keys($this->countryCodes));
    }
}