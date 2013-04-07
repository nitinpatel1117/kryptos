<?php
namespace Kryptos\KryptosBundle\Model;

class Banking
{
    public $type;

    public $file;

    public $user;

    public $direction;

    public $country;

    public $bban1;
    
    public $bban2;
    
    public $bban3;
     
    public $bban4;
    
    public $bban5;

    public $iban;

    public $bic;

    public $bank;

    public $branch;
    
    public $address;
    
    public $valid;
    
    public $sepaDebit;
    
    public $sepaCredit;
    


    public function __construct()
    {
    }
    
    function __call($method, $arguments)
    {
    	#Is this a get or a set
    	$prefix = substr($method, 0, 3);
    	
    	#What is the get/set class attribute
    	$property = substr($method, 3);
    	$property[0] = strtolower($property[0]);
    	 
    	if (empty($prefix) || empty($property)) { #Did not match a get/set call
    		throw New Exception("Calling a non get/set method that does not exist: $method");
    	}
    	
    	// check property exists in class
    	if (!isset($this->$property)) {
    		throw new Exception("Calling a get/set method that does not exist: $property");
    	}
    	
    	switch ($prefix) {
    		case 'set':
    			$this->$property = $arguments[0];
    			break;
    			
    		case 'get':
    			return $this->$property;
    			break;
    			
    		default: 
    			throw new Exception("Methods not found: $method");
    			break;
    	}
    }
    
    
    
    
    
    
    
    
    
    
    
    
    

    public function setId($id)
    {
    	$this->_id = new \MongoId($id);
    }
    
    public function getId()
    {
    	$id = null;
    	if (isset($this->_id->{'$id'})) {
    		$id = $this->_id->{'$id'};
    	}

    	return $id;
    }
    
    
    public $type;
    public $file
    public $user;
    public $direction;

    
	public function setType($type) {
		$this->$type = $type;
	}
	public function getType() {
		return $this->$type;
	}

	public function setFile($file) {
		$this->$file = $file;
	}
	public function getFile() {
		return $this->$file;
	}

	public function setUser($user) {
		$this->$user = $user;
	}
	public function getUser() {
		return $this->$user;
	}
	
	public function setDirection($direction) {
		$this->$direction = $direction;
	}
	public function getDirection() {
		return $this->$direction;
	}
	
	
	
	public $country;
	
	public $bban1;
	
	public $bban2;
	
	public $bban3;

	public function setDirection($direction) {
		$this->$direction = $direction;
	}
	public function getDirection() {
		return $this->$direction;
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

	public function setSalt($salt) {
		$this->salt = $salt;
	}
	public function getSalt() {
		return $this->salt;
	}

	public function setPassword($password) {
		$this->password = $password;
	}
	public function getPassword() {
		return $this->password;
	}

	
	public $bban4;
	
	public $bban5;
	
	public $iban;
	
	public $bic;
	

	public function setAcceptTerms($acceptTerms) {
		$this->acceptTerms = $acceptTerms;
	}
	public function getAcceptTerms() {
		return $this->acceptTerms;
	}

	public function setActivation(UserAccountActivation $activation) {
		$this->activation = $activation;
	}
	public function getActivation() {
		return $this->activation;
	}
}