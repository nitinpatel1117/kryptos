<?php
namespace Kryptos\KryptosBundle\Model\Manager;

use Kryptos\KryptosBundle\Model\Manager\BaseManager;
use Kryptos\KryptosBundle\Model\User;
use Kryptos\KryptosBundle\Lib\Encryption;

class UserManager extends BaseManager
{
    const COLLECTION = 'User';
    
    
    public function __construct($mongoConnection)
    {
    	$dbCollection = $mongoConnection->connectToCollection(self::COLLECTION);
    	
    	$this->setMongoCollection($dbCollection);
    	$this->setNameOfCollection(self::COLLECTION);
    }
    
    
    public function createUserFrom($formData)
    {
    	$user = new User();
    	$user->setUsername(		$formData->getUsername());
    	$user->setFirstName(	$formData->getFirstName());
		$user->setLastName(		$formData->getLastName());
		$user->setJobTitle(		$formData->getJobTitle());
		$user->setCompany(		$formData->getCompany());
		$user->setLocation(		$formData->getLocation());
		$user->setEmail(		$formData->getEmail());
		$user->setAcceptTerms(	$formData->getAcceptTerms());
		
		$encryption = new Encryption();
		list($salt, $password) = $encryption->generateSaltAndPassword($formData->getPassword());
		
		$user->setSalt(			$salt);
		$user->setPassword(		$password);
		
		return $user;
    }
    
    
    /**
     * Fucntion checks if the suppllied username is taken. By default we return true in case we couldn't access mongo for any reason
     * 
     * @param $username string		The username that we want to search for
     * @return bool
     */
    public function isUsernameTaken($username)
    {
    	$usernameTaken = true;
    	$user = $this->getUserByUsername($username);
    	if (is_null($user)) {
    		$usernameTaken = false;
    	}
    	
    	return $usernameTaken;
    }
    
	/**
     * Fucntion checks if the suppllied email is taken. By default we return true in case we couldn't access mongo for any reason
     * 
     * @param $email string		The email that we want to search for
     * @return bool
     */
    public function isEmailTaken($email)
    {
    	$emailTaken = true;
    	$user = $this->getUserByEmail($email);
    	if (is_null($user)) {
    		$emailTaken = false;
    	}
    	
    	return $emailTaken;
    }
    
    
	public function getUserByUsername($username)
    {
    	$object = array('username' => $username);
    	return $this->getMongoCollection()->findOne($object);
    }
    
    
	public function getUserByEmail($email)
    {
    	$object = array('email' => $email);
    	return $this->getMongoCollection()->findOne($object);
    }
    
    
    public function checkSignin($formData)
    {
    	$valid = false;
    	$userArray = $this->getUserByUsername($formData->getUsername());
    	
    	if (!is_null($userArray)) {
	    	$user = new User();
			foreach ($userArray as $key => $value) {
				$user->$key = $value;
			}
			
    		$encryption = new Encryption();
    		$valid = $encryption->isPasswordValid($formData->getPassword(), $user);
    	}
    	
    	return $valid;
    }
}