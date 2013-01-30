<?php
namespace Kryptos\KryptosBundle\Entity;

class Register
{
	protected $username;
	
    protected $firstName;
    
    protected $lastName;
    
    protected $jobTitle;
    
    protected $company;
    
    protected $location;
    
    protected $email;
    
#    protected $confirmEmail;
    
    protected $password;
    
#    protected $confirmPassword;

    protected $acceptTerms;
    
    
	public function setUsername($username) {
		$this->username = $username;
	}
	public function getUsername() {
		return $this->username;
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
	/*
	public function setConfirmEmail($confirmEmail) {
		$this->confirmEmail = $confirmEmail;
	}
	public function getConfirmEmail() {
		return $this->confirmEmail;
	}
	*/
	public function setPassword($password) {
		$this->password = $password;
	}
	public function getPassword() {
		return $this->password;
	}
	/*
	public function setConfirmPassword($confirmPassword) {
		$this->confirmPassword = $confirmPassword;
	}
	public function getConfirmPassword() {
		return $this->confirmPassword;
	}
	*/
	
	public function setAcceptTerms($acceptTerms) {
		$this->acceptTerms = $acceptTerms;
	}
	public function getAcceptTerms() {
		return $this->acceptTerms;
	}
}
