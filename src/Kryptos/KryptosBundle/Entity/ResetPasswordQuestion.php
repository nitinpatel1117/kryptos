<?php
namespace Kryptos\KryptosBundle\Entity;

class ResetPasswordQuestion
{
    protected $password;

    public function setPassword($password) {
		$this->password = $password;
	}
	public function getPassword() {
		return $this->password;
	}
}
