<?php
namespace Kryptos\KryptosBundle\Entity;

class PurchaseConversions
{
	protected $conversions;
    
    protected $cost;

    protected $vat;

	
	public function setConversions($conversions) {
		$this->conversions = $conversions;
	}
	public function getConversions() {
		return $this->conversions;
	}

	public function setCost($cost) {
		$this->cost = $cost;
	}
	public function getCost() {
		return $this->cost;
	}
	
	public function setVat($vat) {
		$this->vat = $vat;
	}
	public function getVat() {
		return $this->vat;
	}
}
