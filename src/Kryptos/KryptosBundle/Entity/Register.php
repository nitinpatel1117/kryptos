<?php
namespace Kryptos\KryptosBundle\Entity;

class Register
{
    protected $firstName;
    
    protected $lastName;
    
    protected $jobTitle;
    
    protected $company;
    
    protected $location;
    
    protected $email;
    
    
    
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
}
