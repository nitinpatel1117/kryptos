<?php
namespace Kryptos\KryptosBundle\Entity;

class SettingsUserDetails
{
	protected $title;
	
    protected $firstName;
    
    protected $lastName;
    
    protected $jobTitle;
    
    protected $company;
    
    protected $location;
    
    protected $email;
    
    
    public function setTitle($title) {
    	$this->title = $title;
    }
    public function getTitle() {
    	return $this->title;
    }
    
	public function setFirstName($firstName) {
		$this->firstName = $firstName;
	}
	public function getFirstName() {
		return $this->firstName;
	}
	
	public function setLastName($lastName) {
		$this->lastName = $lastName;
	}
	public function getLastName() {
		return $this->lastName;
	}
	
	public function setJobTitle($jobTitle) {
		$this->jobTitle = $jobTitle;
	}
	public function getJobTitle() {
		return $this->jobTitle;
	}
	
	public function setCompany($company) {
		$this->company = $company;
	}
	public function getCompany() {
		return $this->company;
	}
	
	public function setLocation($location) {
		$this->location = $location;
	}
	public function getLocation() {
		return $this->location;
	}
	
	public function setEmail($email) {
		$this->email = $email;
	}
	public function getEmail() {
		return $this->email;
	}
	

	public function makeFromArray(array $data)
	{
		if (isset($data['title'])) {
			$this->setTitle($data['title']);
		}
		
		if (isset($data['firstName'])) {
			$this->setFirstName($data['firstName']);
		}
		
		if (isset($data['lastName'])) {
			$this->setLastName($data['lastName']);
		}
		
		if (isset($data['jobTitle'])) {
			$this->setJobTitle($data['jobTitle']);
		}
		
		if (isset($data['company'])) {
			$this->setCompany($data['company']);
		}
		
		if (isset($data['location'])) {
			$this->setLocation($data['location']);
		}
		
		if (isset($data['email'])) {
			$this->setEmail($data['email']);
		}
		
		return $this;
	}
	
	
	public function toArray()
	{
		$data = array();
		$data['title'] 		= $this->getTitle();
		$data['firstName'] 	= $this->getFirstName();
		$data['lastName']	= $this->getLastName();
		$data['jobTitle'] 	= $this->getJobTitle();
		$data['company'] 	= $this->getCompany();
		$data['location'] 	= $this->getLocation();
		$data['email'] 		= $this->getEmail();
		
		return $data;
	}
}
