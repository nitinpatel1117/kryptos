<?php
namespace Kryptos\KryptosBundle\Lib;

use Kryptos\KryptosBundle\Model\User;

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


	public function isPasswordValid($userPassword, User $user)
	{
		return $this->validatePassword($userPassword, $user->getSalt(), $user->getPassword());
	}


	public function makeActivationCodes(User $user)
	{
		$salt= $this->generateRandomString();
		$salt2 = $this->generateRandomString();
		$email = $user->getEmail();

		$code1 = $salt;
		$code2 = hash('sha512', $salt.$email.$salt2);
		$code2 = substr($code2, 0, 20);

		return array($code1, $code2);
	}
	
	
	public function makePrivateKey($length = 32)
	{
		return $this->generateRandomString($length);
	}
}