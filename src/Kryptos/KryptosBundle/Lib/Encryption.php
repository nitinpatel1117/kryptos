<?php
namespace Kryptos\KryptosBundle\Lib;


class Encryption
{
	public function generateSaltAndPassword($userPassword)
	{
		$salt = $this->generateRandomString();
		$password = $this->makePassword($salt, $userPassword);
		
		return array($salt, $password);
	}
	
	
	private function generateRandomString($length = 10)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $randomString;
	}
	
	
	private function makePassword($salt, $userPassword)
	{
		return hash('sha512', $salt.$userPassword.$salt);
	}
	
	
	public function validatePassword($userPassword, $salt_DB, $password_DB)
	{
		if ($this->makePassword($salt_DB, $userPassword) === $password_DB) {
			return true;
		}
		
		 return false;
	}
}