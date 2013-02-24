<?php
namespace Kryptos\KryptosBundle\Entity;

class ResetPasswordEmail
{
    protected $email;

	public function setEmail($email) {
		$this->email = $email;
	}
	public function getEmail() {
		return $this->email;
	}
}
