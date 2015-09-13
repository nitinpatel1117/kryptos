<?php
namespace Kryptos\SageBundle\Entity;

use Kryptos\SageBundle\Lib\CountryCodes;

class PaymentBilling
{
	protected $billingSurname;
	protected $billingFirstnames;
	protected $billingAddress1;
	protected $billingAddress2;
	protected $billingCity;
	protected $billingPostCode;
	protected $billingCountry;
	protected $acceptTerms;
	
	protected $deliverySurname;
	protected $deliveryFirstnames;
	protected $deliveryAddress1;
	protected $deliveryAddress2;
	protected $deliveryCity;
	protected $deliveryPostCode;
	protected $deliveryCountry;
	
	protected $deliverySameAsBilling = true;

	public function setBillingSurname($billingSurname) {
		$this->billingSurname = $billingSurname;
	}
	public function getBillingSurname() {
		return $this->billingSurname;
	}

	public function setBillingFirstnames($billingFirstnames) {
		$this->billingFirstnames = $billingFirstnames;
	}
	public function getBillingFirstnames() {
		return $this->billingFirstnames;
	}
	
	public function setBillingAddress1($billingAddress1) {
		$this->billingAddress1 = $billingAddress1;
	}
	public function getBillingAddress1() {
		return $this->billingAddress1;
	}
	
	public function setBillingAddress2($billingAddress2) {
		$this->billingAddress2 = $billingAddress2;
	}
	public function getBillingAddress2() {
		return $this->billingAddress2;
	}
	
	public function setBillingCity($billingCity) {
		$this->billingCity = $billingCity;
	}
	public function getBillingCity() {
		return $this->billingCity;
	}
	
	public function setBillingPostCode($billingPostCode) {
		$this->billingPostCode = $billingPostCode;
	}
	public function getBillingPostCode() {
		return $this->billingPostCode;
	}
	
	public function setBillingCountry($billingCountry) {
		$this->isCountryCodeValid($billingCountry);
		$this->billingCountry = $billingCountry;
	}
	public function getBillingCountry() {
		return $this->billingCountry;
	}
	
	public function setAcceptTerms($acceptTerms) {
		$this->acceptTerms = $acceptTerms;
	}
	public function getAcceptTerms() {
		return $this->acceptTerms;
	}
	

	
	public function setDeliverySurname($deliverySurname) {
		$this->deliverySurname = $deliverySurname;
	}
	public function getDeliverySurname() {
		return $this->deliverySurname;
	}
	
	public function setDeliveryFirstnames($deliveryFirstnames) {
		$this->deliveryFirstnames = $deliveryFirstnames;
	}
	public function getDeliveryFirstnames() {
		return $this->deliveryFirstnames;
	}
	
	public function setDeliveryAddress1($deliveryAddress1) {
		$this->deliveryAddress1 = $deliveryAddress1;
	}
	public function getDeliveryAddress1() {
		return $this->deliveryAddress1;
	}
	
	public function setDeliveryAddress2($deliveryAddress2) {
		$this->deliveryAddress2 = $deliveryAddress2;
	}
	public function getDeliveryAddress2() {
		return $this->deliveryAddress2;
	}
	
	public function setDeliveryCity($deliveryCity) {
		$this->deliveryCity = $deliveryCity;
	}
	public function getDeliveryCity() {
		return $this->deliveryCity;
	}
	
	public function setDeliveryPostCode($deliveryPostCode) {
		$this->deliveryPostCode = $deliveryPostCode;
	}
	public function getDeliveryPostCode() {
		return $this->deliveryPostCode;
	}
	
	public function setDeliveryCountry($deliveryCountry) {
		$this->isCountryCodeValid($deliveryCountry);
		$this->deliveryCountry = $deliveryCountry;
	}
	public function getDeliveryCountry() {
		return $this->deliveryCountry;
	}

	
	public function updateDelivery()
	{
		if (true == $this->deliverySameAsBilling) {
			$this->setDeliverySurname(		$this->getBillingSurname());
			$this->setDeliveryFirstnames(	$this->getBillingFirstnames());
			$this->setDeliveryAddress1(		$this->getBillingAddress1());
			$this->setDeliveryAddress2(		$this->getBillingAddress2());
			$this->setDeliveryCity(			$this->getBillingCity());
			$this->setDeliveryPostCode(		$this->getBillingPostCode());
			$this->setDeliveryCountry(		$this->getBillingCountry());
		}
	}
	
	
	public function isCountryCodeValid($code)
	{
		$countryCodes = new CountryCodes();
		if (!$countryCodes->isValid($code)) {
			throw new \Exception('Invalid country code supplied.');
		}
	}
	
	
	
	public function toArray()
	{
		$data = array();
		$data['BillingSurname'] 	= $this->getBillingSurname();
		$data['BillingFirstnames'] 	= $this->getBillingFirstnames();
		$data['BillingAddress1'] 	= $this->getBillingAddress1();
		$data['BillingAddress2'] 	= $this->getBillingAddress2();
		$data['BillingCity'] 		= $this->getBillingCity();
		$data['BillingPostCode'] 	= $this->getBillingPostCode();
		$data['BillingCountry'] 	= $this->getBillingCountry();
		$data['AcceptTerms'] 	 	= $this->getAcceptTerms();
		 
		$data['DeliverySurname'] 	= $this->getDeliverySurname();
		$data['DeliveryFirstnames'] = $this->getDeliveryFirstnames();
		$data['DeliveryAddress1'] 	= $this->getDeliveryAddress1();
		$data['DeliveryAddress2'] 	= $this->getDeliveryAddress2();
		$data['DeliveryCity'] 		= $this->getDeliveryCity();
		$data['DeliveryPostCode'] 	= $this->getDeliveryPostCode();
		$data['DeliveryCountry'] 	= $this->getDeliveryCountry();
		
		return $data;
	}
}
