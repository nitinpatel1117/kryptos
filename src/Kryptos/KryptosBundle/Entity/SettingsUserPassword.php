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
}
