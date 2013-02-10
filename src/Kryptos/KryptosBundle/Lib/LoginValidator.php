<?php
namespace Kryptos\KryptosBundle\Lib;


class LoginValidator
{
	const LOGIN_SEESION_PREFIX = 'login_';

	protected $memcacheHandler = null;

	protected $session = null;

	protected $sessionId = null;

	protected $sessionExpire = null;


	public function __construct($memcacheHandler, $session, $sessionExpire)
	{
		$this->memcacheHandler 	= $memcacheHandler;
		$this->session			= $session;
		$this->sessionExpire	= $sessionExpire;

		$session->start();
		$this->sessionId = sprintf('%s%s',self::LOGIN_SEESION_PREFIX, $session->getId());
	}


	public function saveLogin($email)
	{
		$item = $this->makeSaveObject($email);
		$this->write($this->sessionId, $item);
	}


	public function isLoginValid()
	{
		$loginValid = false;

		$item = $this->getLoggedInUserDetails();

		if (is_array($item)) {
			$dateNew = new \DateTime();
	    	$dateNew->modify(sprintf('+%s seconds', $this->sessionExpire));

	    	if ($dateNew > $item['accessed']) {
	    		$loginValid = true;
	    	}
		}

    	return $loginValid;
	}


	public function getLoggedInUserDetails()
	{
		return $this->read($this->sessionId);
	}


	protected function makeSaveObject($email)
	{
		$item = array();
		$item['email'] = $email;
		$item['accessed'] = new \DateTime();

		return $item;
	}


	protected function write($key, $value)
	{
		$this->memcacheHandler->write($key, $value);
	}


	protected function read($key)
	{
		return $this->memcacheHandler->read($key);
	}


	public function logout()
	{
		$this->memcacheHandler->destroy($this->sessionId);
		$this->session->migrate(true);
	}
}