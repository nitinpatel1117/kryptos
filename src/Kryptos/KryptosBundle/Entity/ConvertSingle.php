<?php
namespace Kryptos\KryptosBundle\Entity;

class ConvertSingle
{
    protected $country;
    protected $bban1;
    protected $bban2;
    protected $bban3;
    protected $bban4;
    protected $bban5;
    
	public function setCountry($country) {
		$this->country = $country;
	}
	public function getCountry() {
		return $this->country;
	}
	
	public function setBban1($bban1) {
		$this->bban1 = $bban1;
	}
	public function getBban1() {
		return $this->bban1;
	}
	
	public function setBban2($bban2) {
		$this->bban2 = $bban2;
	}
	public function getBban2() {
		return $this->bban2;
	}
	
	public function setBban3($bban3) {
		$this->bban3 = $bban3;
	}
	public function getBban3() {
		return $this->bban3;
	}
	
	public function setBban4($bban4) {
		$this->bban4 = $bban4;
	}
	public function getBban4() {
		return $this->bban4;
	}
	
	public function setBban5($bban5) {
		$this->bban5 = $bban5;
	}
	public function getBban5() {
		return $this->bban5;
	}
}
