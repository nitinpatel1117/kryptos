<?php
namespace Kryptos\KryptosBundle\Entity;

class Signin
{
	#protected $username;
    
    protected $email;
    
    protected $password;

    protected $staySignedIn;
    
    /*
	public function setUsername($username) {
		$this->username = $username;
	}
	public function getUsername() {
		return $this->username;
	}
	*/
	
	public function setEmail($email) {
		$this->email = $email;
	}
	public function getEmail() {
		return $this->email;
	}

	public function setPassword($password) {
		$this->password = $password;
	}
	public function getPassword() {
		return $this->password;
	}
	
	public function setStaySignedIn($staySignedIn) {
		$this->staySignedIn = $staySignedIn;
	}
	public function getStaySignedIn() {
		return $this->staySignedIn;
	}
}
