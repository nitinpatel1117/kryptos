<?php
namespace Kryptos\KryptosBundle\Entity;

class SettingsUserPassword
{
    protected $oldPassword;
    
    protected $password;
    
    
    public function setOldPassword($oldPassword) {
    	$this->oldPassword = $oldPassword;
    }
    public function getOldPassword() {
    	return $this->oldPassword;
    }
    
    public function setPassword($password) {
		$this->password = $password;
	}
	public function getPassword() {
		return $this->password;
	}
	

	public function makeFromArray(array $data)
	{
		if (isset($data['oldPassword'])) {
			$this->setOldPassword($data['oldPassword']);
		}
		
		if (isset($data['password'])) {
			$this->setPassword($data['password']);
		}
		
		return $this;
	}
	
	
	public function toArray()
	{
		$data = array();
		$data['oldPassword'] 	= $this->getOldPassword();
		$data['password'] 		= $this->getPassword();
		
		return $data;
	}
}
